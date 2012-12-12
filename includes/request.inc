<?php
/**
 * @file
 * Handling the Requests of APIs
 *
 * @package wc_core
 */

/**
 * Perform a request on a Service
 *
 * We are going to add this item to a queue or we are going to process it instantaniously
 *
 * For the callbacks, we pass them three arguments:
 *  - mixed  The Raw Response
 *  - bool   Is it an error?
 *  - array  The original Request Data
 * You can pass any callback function that would work with {@link http://php.net/call_user_func_array}.
 *
 * @param   string
 * @param   string The Endpoint on the API to request
 * @param   array Optional Arguments to pass
 * @param   string The HTTP method (get, put, post, delete)
 * @param   int The time to perform the request (if = 0, will be performed right now)
 * @param   mixed The callback to be performed, see {@link http://php.net/manual/en/function.call-user-func-array.php}
 * @param   string The Response Data format it will be in (json, xml, serialized, or raw for just returning the response)
 * @return  int|bool The request ID for the database or FALSE if it is failed
 * @throws  WConsumerException
 */
function wc_perform_request($service, $endPoint = NULL, $arguments = array(), $http_method = 'get', $time = 0, $callback = NULL, $responseFormat = 'json')
{
  $services = wc_get_services();

  // Sanitize the arguments
  if (! isset($services['service'][$service])) :
      throw new WConsumerException('Unknown service passed: '.$service);
      return FALSE;
  endif;

  if ($callback !== NULL && ! is_callable($callback)) :
      throw new WConsumerException('Unknown callback '.print_r($callback, TRUE));
      return FALSE;
  endif;

  $http_method = (is_null($http_method)) ? 'get' : strtolower($http_method);
  $time = (int) time;
  if ($time < 0) $time = 0;

  // Get the user
  global $user;
  $uid = $user->uid;

  // Formulate the request
  $request = compact($endPoint, $arguments, $http_method, $callback, $uid, $responseFormat);

  // Store the request
  try {
      db_insert(WC_CORE_T_REQUEST)
      ->fields(array(
          'sid'               => _wc_core_getServiceId($service),
          'request'           => serialize($request),
          'time'              => $time,
          'response_data'     => NULL,
          'status'            => 'pending',
      ))->execute();
  }
  catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
      return FALSE;
  }

  $request_id = db_last_insert_id(WC_CORE_T_REQUEST, 'rid');

  // Are we going to perform it right now?
  return ($time == 0)  ? _wc_perform_request($request_id) : $request_id;
}

/**
 * Retrieve a request from the queue
 *
 * We can see if the request was performed and retrieve information about the request.
 *
 * @param   int The request ID
 * @return  object|void
**/
function wc_retrieve_request($id)
{
  // Sanitize
  $id = (int) $id;
  if ($id <= 0)
      return NULL;

  $request = db_select(WC_CORE_T_REQUEST)
  ->fields(WC_CORE_T_REQUEST, array(
      'rid'
  ))
  ->condition('service_name', $service_name)
  ->execute()->fetchAssoc();

  if ($request == FALSE)
      return NULL;

  // Automatically unserialize some things
  if (!is_null($request->request))
      $request->request = unserialize($request->request);

  if (!is_null($request->response_data))
      $request->request = unserialize($request->response_data);
  
  return $request;
}

/**
 * Perform a request from the queue
 *
 * This is used internally to perform a request from the queue
 *
 * @access  private
 * @param   int The request ID
 * @param   bool Force the request to be run even if it hasn't met it's scheduled time or was already executed
 * @return  object The `wc_retrieve_request` for the request
 */
function _wc_perform_request($id, $force = FALSE)
{
  $request = db_select(WC_CORE_T_REQUEST)
  ->fields(WC_CORE_T_REQUEST, array(
    'suid', 'request', 'time', 'response_data'
  ))
  ->condition('service_name', $service_name)
  ->execute()->fetchAssoc();

  // Nothing Found
  if (! $request)
    return FALSE;

  // Tear apart the variables
  $requestData = $request->request;

  // It is already complete
  if (($request->status == 'completed' || $request->status == 'error') && ! $force)
    return wc_retrieve_request($id);

  // Check the time
  if ($request->time > time() AND ! $force)
    return wc_retrieve_request($id);

  // Get the service
  $service = _wc_core_getServiceName($request->suid);
  if (! $service)
    throw new WConsumerException("Unknown service '{$request->suid}' for request '{$id}'.")

  $config = wc_get_services();
  $serviceCreds = _wc_core_getServiceCreds($service);

  $accessTokenURL  = $config['service'][$service]['accessTokenURL'];
  $authenticateURL = $config['service'][$service]['authenticateURL'];
  $authorizeURL    = $config['service'][$service]['authorizeURL'];
  $requestTokenURL = $config['service'][$service]['requestTokenURL'];
  $consumerKey     = $serviceCreds['consumer_key'];
  $consumerSecret  = $serviceCreds['consumer_secret'];

  $object = new oauthConnectionDrupal($accessTokenURL, $authenticateURL, $authorizeURL, $requestTokenURL);
  $object->createConnection($consumerKey, $consumerSecret);

  // Make the Request
  $url = $config['service'][$service]['apiBase'].$requestData['endPoint'];
  $isError = FALSE;
  $requestData['error'] = NULL;

  try {
    $perform = $object->oAuthRequest($url, strtoupper($requestData['http_method']), $requestData['arguments']);
  } catch(OAuthException $e) {
    $isError = TRUE;
    $requestData['error'] = $e;
  }

  // Request Callback
  if ($requestData['callback'] !== NULL)
    call_user_func_array($requestData['callback'], array($perform, $isError, $requestData));

  // Update the database
  try {
    $update = db_update(WC_CORE_T_REQUEST)
    ->fields(array(
      'response_data'   => $perform,
      'status'          => ($isError) ? 'completed' : 'error'
    ))
    ->condition('rid', $request->rid)
    ->execute();
  } catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
  }

  return _wc_interpret_response($perform, $requestData['responseFormat']);
}

/**
 * Get an API Requestor Object
 *
 * @access  public
 * @param   string The Service Name
 **/
function wc_request_object($service)
{
  $services = wc_get_services();
  
  if (!isset($services['service'][$service]))
    return FALSE;
  
  $config = $services['service'][$service];
  
  // They can override the OAuth Requestor Class in the module config
  if (isset($config['requestorClass'])):
    $class = $config['requestorClass'];
  else:
    $class = 'oauthConnectionDrupal';
  endif;
  
  // Sanitity Check
  if (!class_exists($class)):
    watchdog('wc_core', "Class {$class} doesn't exist to create an object from", array(
      $class
    ), WATCHDOG_WARNING);
    return NULL;
  endif;
  
  // Create a new object here
  // Todo: Add OAuth Creds passing, API key passing, etc
  return new $class();
}

/**
 * Interpret the Response from an API Request
 *
 * We support multiple formats and will attempt to proccess them and decode them.
 *
 * @param   mixed The raw response
 * @param   string The format the response will be in
 * @throws  WConsumerException
 * @return  mixed
 */
function _wc_interpret_response($rawResponse, $format = 'json') {
  switch(strtolower($format)) :
    case 'json' :
      try {
        $data = json_decode($rawResponse);
      } catch(Exception $e) {
        throw new WConsumerException('Error decoding response to JSON');
      }

      return $data;
      break;

    case 'serialize' :
      try {
        $data = unserialize($rawResponse);
      } catch (Exception $e) {
        throw new WConsumerException('Error decoding response to serialzied format');
      }

      return $data;
      break;

    // raw format
    default :
      return $data;
  endswitch;
}

/**
 * API Requestor Class
 * 
 * @package wc_core
 */
class oAuthRequestor
{
  private $service = NULL;
  private $serviceId = 0;

  /**
   *  Constructor
   * 
   */
  public function __construct($service)
  {
    $this->service = $service;
    $this->serviceId = _wc_core_getServiceId($service);
  }

  /**
   * Retrieve a request from the queue
   *
   * We can see if the request was performed and retrieve information about the request.
   *
   * @param  int The request ID
   * @return  object|void
  **/
  public function retrieve($id)
  {
    // Sanitize
    $id = (int) $id;
    if ($id <= 0) return NULL;

    $request = db_select(WC_CORE_T_REQUEST)
      ->fields(WC_CORE_T_REQUEST, array(
        'rid'
      ))
      ->condition('suid', $this->serviceId)
      ->execute()->fetchAssoc();

    if ($request == FALSE)
        return NULL;

    // Automatically unserialize some things
    if (!is_null($request->request))
        $request->request = unserialize($request->request);

    if (!is_null($request->response_data))
        $request->request = unserialize($request->response_data);
    
    return $request;
  }

  /**
   * Perform a Request
   *
   * We are going to add this item to a queue or we are going to process it instantaniously
   *
   * For the callbacks, we pass them three arguments:
   *  - mixed  The Raw Response
   *  - bool   Is it an error?
   *  - array  The original Request Data
   * You can pass any callback function that would work with {@link http://php.net/call_user_func_array}.
   *
   * @param   string
   * @param   string The Endpoint on the API to request
   * @param   array Optional Arguments to pass
   * @param   string The HTTP method (get, put, post, delete)
   * @param   int The time to perform the request (if = 0, will be performed right now)
   * @param   mixed The callback to be performed, see {@link http://php.net/manual/en/function.call-user-func-array.php}
   * @param   string The Response Data format it will be in (json, xml, serialized, or raw for just returning the response)
   * @return   int|bool The request ID for the database or FALSE if it is failed
   * @throws  WConsumerException
   */
  public function request($endPoint = NULL, $arguments = array(), $http_method = 'get', $time = 0, $callback = NULL, $responseFormat = 'json')
  {
    $services = wc_get_services();

    // Sanitize the arguments
    if (! isset($services['service'][$this->service]))
        throw new WConsumerException('Unknown service passed: '.$service);

    if ($callback !== NULL && ! is_callable($callback))
        throw new WConsumerException('Unknown callback '.print_r($callback, TRUE));

    $http_method = (is_null($http_method)) ? 'get' : strtolower($http_method);
    $time = (int) time;
    if ($time < 0) $time = 0;

    // Get the user
    global $user;
    $uid = $user->uid;

    // Formulate the request
    $request = compact($endPoint, $arguments, $http_method, $callback, $uid, $responseFormat);

    // Store the request
    try {
        db_insert(WC_CORE_T_REQUEST)
        ->fields(array(
            'sid'             => $this->serviceId,
            'request'     => serialize($request),
            'time'          => $time,
            'response_data'   => NULL,
            'status'        => 'pending',
        ))->execute();
    }
    catch (Exception $e) {
        watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
        return FALSE;
    }

    $request_id = db_last_insert_id(WC_CORE_T_REQUEST, 'rid');

    // Are we going to perform it right now?
    return ($time == 0)  ? $this->perform($request_id) : $request_id;
  }

  /**
   * Perform a request from the queue
   *
   * This is used internally to perform a request from the queue
   *
   * @access  private
   * @param   int The request ID
   * @param   bool Force the request to be run even if it hasn't met it's scheduled time or was already executed
   * @return  object The `wc_retrieve_request` for the request
   */
  public function perform($id, $force = FALSE)
  {
    $request = db_select(WC_CORE_T_REQUEST)
      ->fields(WC_CORE_T_REQUEST, array(
        'suid', 'request', 'time', 'response_data', 'status'
      ))
      ->condition('suid', $this->serviceId)
      ->execute()->fetchAssoc();

    // Nothing Found
    if (! $request)
      return FALSE;

    // Tear apart the variables
    $requestData = $request->request;

    // It is already complete
    if (($request->status == 'completed' || $request->status == 'error') && ! $force)
      return $this->retrieve($id);

    // Check the time
    if ($request->time > time() && ! $force)
      return $this->retrieve($id);

    // Get the service
    $config = wc_get_services();
    $serviceCreds = _wc_core_getServiceCreds($this->service);

    $accessTokenURL  = $config['service'][$this->service]['accessTokenURL'];
    $authenticateURL = $config['service'][$this->service]['authenticateURL'];
    $authorizeURL    = $config['service'][$this->service]['authorizeURL'];
    $requestTokenURL = $config['service'][$this->service]['requestTokenURL'];
    $consumerKey     = $serviceCreds['consumer_key'];
    $consumerSecret  = $serviceCreds['consumer_secret'];

    $object = new oauthConnectionDrupal($accessTokenURL, $authenticateURL, $authorizeURL, $requestTokenURL);
    $object->createConnection($consumerKey, $consumerSecret);

    // Make the Request
    $url = $config['service'][$this->service]['apiBase'].$requestData['endPoint'];
    $isError = FALSE;
    $requestData['error'] = NULL;

    try {
      $perform = $object->oAuthRequest($url, strtoupper($requestData['http_method']), $requestData['arguments']);
    } catch(OAuthException $e) {
      $isError = TRUE;
      $requestData['error'] = $e;
    }

    // Request Callback
    if ($requestData['callback'] !== NULL)
      call_user_func_array($requestData['callback'], array(
        $this->interpretResponse($perform, $requestData['responseFormat']),
        $isError,
        $requestData
      ));

    // Update the database
    try {
      $update = db_update(WC_CORE_T_REQUEST)
      ->fields(array(
        'response_data'   => $perform,
        'status'                  => ($isError) ? 'completed' : 'error'
      ))
      ->condition('rid', $request->rid)
      ->execute();
    } catch (Exception $e) {
        watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
    }

    return $this->interpretResponse($perform, $requestData['responseFormat']);
  }

  /**
   * Interpret the Response from an API Request
   *
   * We support multiple formats and will attempt to proccess them and decode them.
   *
   * @param   mixed The raw response
   * @param   string The format the response will be in
   * @throws  WConsumerException
   * @return  mixed
   */
  public function interpretResponse($rawResponse, $format = 'json') {
    switch(strtolower($format)) :
      case 'json' :
        try {
          $data = json_decode($rawResponse);
        } catch(Exception $e) {
          throw new WConsumerException('Error decoding response to JSON');
        }

        return $data;
        break;

      case 'serialize' :
        try {
          $data = unserialize($rawResponse);
        } catch (Exception $e) {
          throw new WConsumerException('Error decoding response to serialzied format');
        }

        return $data;
        break;

      // raw format
      default :
        return $data;
    endswitch;
  }
}