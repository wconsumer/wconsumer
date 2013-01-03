<?php
/**
 * @file
 * Handling the Requests of APIs
 *
 * @package wc_core
 */

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
   */
  public function __construct($service)
  {
    $this->service   = $service;
    $this->serviceId = _wc_core_getServiceId($service);
  }

  /**
   * Retrieve a request from the queue
   *
   * We can see if the request was performed and retrieve information about the request.
   *
   * @param  int The request ID
   * @return  array|void A database row with the request and response data unserialized
   **/
  public function retrieve($id)
  {
    // Sanitize
    $id = (int) $id;
    if ($id <= 0)
      return NULL;

    $request = db_select(WC_CORE_T_REQUEST)->fields(WC_CORE_T_REQUEST, array(
      'request',
      'response_data',
      'time',
      'status'
    ))->condition('rid', $id)->condition('suid', $this->serviceId)->execute()->fetchAssoc();

    if ($request == FALSE)
      return NULL;

    // Automatically unserialize some things
    if (!is_null($request['request']))
      $request['request'] = unserialize($request['request']);

    if (!is_null($request['response_data']))
      $request['response_data'] = unserialize($request['response_data']);

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
    if (!isset($services['service'][$this->service]))
      throw new WConsumerException('Unknown service passed: ' . $service);

    if ($callback !== NULL && !is_callable($callback))
      throw new WConsumerException('Unknown callback ' . print_r($callback, TRUE));

    $http_method = (is_null($http_method)) ? 'get' : strtolower($http_method);
    $time = (int) $time;
    if ($time < 0)
      $time = 0;

    // Get the user
    global $user;
    $uid = (int) $user->uid;

    // Formulate the request
    $request = compact('endPoint', 'arguments', 'http_method', 'callback', 'uid', 'responseFormat');

    // Store the request
    try {
      $insert = db_insert(WC_CORE_T_REQUEST)->fields(array(
        'suid' => $this->serviceId,
        'request' => serialize($request),
        'time' => $time,
        'response_data' => NULL,
        'status' => 'pending',
        'moderate' => 'N',
        'created_date' => time()
      ))->execute();
    }
    catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);

      throw new WConsumerException($e->getMessage());
      return FALSE;
    }

    // It returns the insert ID as a string
    $insert = (int) $insert;

    // Are we going to perform it right now?
    return ($time == 0) ? $this->perform($insert) : $insert;
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
    $request = db_select(WC_CORE_T_REQUEST)->fields(WC_CORE_T_REQUEST, array(
      'suid',
      'request',
      'time',
      'response_data',
      'status'
    ))->condition('rid', $id)->condition('suid', $this->serviceId)->execute()->fetchAssoc();

    // Nothing Found
    if (!$request)
      return FALSE;

    // Tear apart the variables
    $requestData = unserialize($request['request']);

    // It is already complete
    if (($request->status == 'completed' || $request->status == 'error') && !$force)
      return $this->retrieve($id);

    // Check the time
    if ($request->time > time() && !$force)
      return $this->retrieve($id);

    // Get the service
    $config       = wc_get_services();
    $serviceCreds = _wc_core_getServiceCreds($this->service);
    $userCreds    = _wc_core_getUserCreds($this->service);

    $accessTokenURL  = $config['service'][$this->service]['accessTokenURL'];
    $authenticateURL = $config['service'][$this->service]['authenticateURL'];
    $authorizeURL    = $config['service'][$this->service]['authorizeURL'];
    $requestTokenURL = $config['service'][$this->service]['requestTokenURL'];
    $consumerKey     = $serviceCreds['consumer_key'];
    $consumerSecret  = $serviceCreds['consumer_secret'];

    if (!class_exists('oauthConnectionDrupal'))
      require_once(dirname(__FILE__) . '/oauth-class.inc');

    $object = new oauthConnectionDrupal($accessTokenURL, $authenticateURL, $authorizeURL, $requestTokenURL);
    $object->createConnection($consumerKey, $consumerSecret, $userCreds['access_token'], $userCreds['access_token_secret']);

    // Make the Request
    $url                  = $config['service'][$this->service]['apiBase'] . $requestData['endPoint'];
    $isError              = FALSE;
    $requestData['error'] = NULL;

    try {
      $perform = $object->oAuthRequest($url, strtoupper($requestData['http_method']), $requestData['arguments']);
    }
    catch (OAuthException $e) {
      $isError              = TRUE;
      $requestData['error'] = $e;
    }

    // Process the Response
    $response = $this->interpretResponse($perform, $requestData['responseFormat']);

    // Request Callback
    if ($requestData['callback'] !== NULL)
      call_user_func_array($requestData['callback'], array(
        $response,
        $isError,
        $requestData
      ));

    // Update the database
    try {
      $update = db_update(WC_CORE_T_REQUEST)->fields(array(
        'request' => serialize($requestData),
        'response_data' => serialize($response),
        'status' => ($isError) ? 'error' : 'completed'
      ))->condition('rid', $id)->execute();
    }
    catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
    }

    return $response;
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
   * @access  private
   */
  private function interpretResponse($rawResponse, $format = 'json')
  {
    switch (strtolower($format)):
      case 'json':
        try {
          $data = json_decode($rawResponse);
        }
        catch (Exception $e) {
          throw new WConsumerException('Error decoding response to JSON');
        }

        return $data;
        break;

      case 'serialize':
        try {
          $data = unserialize($rawResponse);
        }
        catch (Exception $e) {
          throw new WConsumerException('Error decoding response to serialzied format');
        }

        return $data;
        break;

      // raw format
      default:
        return $data;
    endswitch;
  }

  /**
   *  Ensure the user and the application are setup
   *
   * @return bool
   * @param int
   */
  public function isSetup($uid = 0)
  {
    if ($uid == 0):
      global $user;
      $uid = $user->uid;
    endif;

    if ($uid == 0)
      return FALSE;

    // Check the application
    if (!wc_check_service($this->service))
      return FALSE;

    if (!wc_check_service_auth($this->service, $uid))
      return FALSE;

    return TRUE;
  }
}
