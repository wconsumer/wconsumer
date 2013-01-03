<?php
/**
 * @file
 * Handling the Requests of APIs
 *
 * @package wc_core
 */

/**
 * A request base to centralize requests made for Consumer Services
 *
 * This class will facilitate the requests for the services. It also needs to handle
 * different authentication methods. You can look at the `oAuthRequester` or
 * `apiRequester` classes for the specific authentication method implementation.
 * 
 * @package wc_core
 */
class ServiceRequest {
  // The name of the service
  public $service;

  // The internal ID of the service
  public $serviceId = 0;

  // Internal configuration for the service
  public $serviceInfo;

  /**
   *  Constructor
   */
  public function __construct($service)
  {
    $this->service   = $service;
    $this->serviceId = _wc_core_getServiceId($service);

    if ($this->serviceId == 0)
      throw new WConsumerException("Service {$this->service} is not registered.");

    // Retrieve Service Information
    $services = wc_get_services();

    if (! isset($services['service'][$service]))
      throw new WConsumerException("Service {$this->service} cannot be found in the internal configuration.");

    $this->serviceInfo = $services['service'][$service];
  }

  /**
   * Ensure the user and the application are setup
   *
   * @return bool
   * @param int
   */
  public function isSetup($uid = 0)
  {
    if ($uid == 0):
      global $user;
      $uid = (int) $user->uid;
    else :
      $uid = (int) $uid;
    endif;

    // Sanitize the User ID
    if ($uid == 0) return FALSE;

    // Check the application
    if (! wc_check_service($this->service))
      return FALSE;

    if (!wc_check_service_auth($this->service, $uid))
      return FALSE;

    return TRUE;
  }

  /**
   * Perform a Request on a Service
   *
   * We are going to add this item to a queue or we are going to process it instantaneously.
   *
   * For the callbacks, we pass them three arguments:
   *  - mixed  The Raw Response
   *  - bool   Is it an error?
   *  - array  The original Request Data
   *
   * You can pass any callback function that would work with {@link http://php.net/call_user_func_array}.
   *
   * This is an intermediary to the Authentication Specific Classes. We will pass it off to the API Auth/OAuth/etc class
   * to handle the request.
   *
   * @param   string
   * @param   string The Endpoint on the API to request
   * @param   array Optional Arguments to pass (GET would have them passed as a query argument, POST would be posted arguments, etc)
   * @param   string The HTTP method (get, put, post, delete)
   * @param   int The time to perform the request (if = 0, will be performed right now)
   * @param   mixed The callback to be performed, see {@link http://php.net/manual/en/function.call-user-func-array.php}
   * @param   string The Response Data format it will be in (json, xml, serialized, or raw for just returning the response)
   * @return   int|bool The request ID for the database or FALSE if it is failed
   * @throws  WConsumerException
   */
  public function request($endPoint = NULL, $arguments = array(), $http_method = 'GET', $time = 0, $callback = NULL, $responseFormat = 'json')
  {
    // Sanitize the arguments
    if ($callback !== NULL && !is_callable($callback))
      throw new WConsumerException('Unknown callback ' . print_r($callback, TRUE));

    $http_method = (is_null($http_method)) ? 'get' : strtoupper($http_method);
    $time = (int) $time;

    if ($time < 0) $time = 0;

    // Get the user
    global $user;
    $uid = (int) $user->uid;

    // Formulate the request
    $request = compact('endPoint', 'arguments', 'http_method', 'callback', 'uid', 'responseFormat');

    // Store the request
    try {
      $insert = db_insert(WC_CORE_T_REQUEST)
        ->fields(array(
          'suid' => $this->serviceId,
          'request' => serialize($request),
          'time' => $time,
          'response_data' => NULL,
          'status' => 'pending',
          'moderate' => 'N',
          'created_date' => time()
        ))
        ->execute();
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
    if (! $this->isSetup())
      throw new WConsumerException("Service {$this->service} is not setup yet and cannot be requested upon.");

    if (! isset($this->serviceInfo['authMethod']))
      $this->serviceInfo['authMethod'] = 'oauth';

      // We're going to lowercase it for simplicity
      switch(strtolower($this->serviceInfo['authMethod'])) :
        case 'apikey' :
          $object = new apiRequester($this);
          return $object->perform($id, $force);

          break;

        case 'oauth' :
          $object = new oAuthRequester($this);
          return $object->perform($id, $force);

          break;

        default :
          throw new WConsumerException("Unknown authentication method for {$this->service}: ".$this->serviceInfo['authMethod']);
      endswitch;
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
    if ($id <= 0) return NULL;

    $request = db_select(WC_CORE_T_REQUEST)
      ->fields(WC_CORE_T_REQUEST, array(
        'request',
        'response_data',
        'time',
        'status'
      ))
      ->condition('rid', $id)
      ->condition('suid', $this->serviceId)
      ->execute()
      ->fetchAssoc();

    if ($request == FALSE) return NULL;

    // Automatically unserialize some things
    if (!is_null($request['request']))
      $request['request'] = unserialize($request['request']);

    if (!is_null($request['response_data']))
      $request['response_data'] = unserialize($request['response_data']);

    return $request;
  }


  /**
   * Interpret the Response from an API Request
   *
   * We support multiple formats and will attempt to process them and decode them.
   *
   * @param   mixed The raw response
   * @param   string The format the response will be in
   * @throws  WConsumerException
   * @return  mixed
   * @access  private
   */
  public function interpretResponse($rawResponse, $format = 'json')
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
          throw new WConsumerException('Error decoding response to serialized format');
        }

        return $data;
        break;

      // raw format
      default:
        return $data;
    endswitch;
  }
}

/**
 * Class Built upon to make Authentication Specific Request Classes
 *
 * We didn't want to rewrite code so we will extend this
 *
 * @package  wc_core
 */
abstract class ServiceRequestExtender
{
  protected $service = NULL;
  protected $serviceId = 0;
  protected $ServiceRequest;

  /**
   *  Constructor
   *
   * @param  object The Request Base class object
   */
  public function __construct($object)
  {
    if (!$object instanceof ServiceRequest)
      throw new WConsumerException('Unknown service requester base object passed to constructor.');

    $this->ServiceRequest = $object;
  }

  /**
   * Placeholder for the perform method which will interact with the API
   */
  abstract public function perform($id, $force = FALSE);
}

/**
 * OAuth API Requester Class
 *
 * @package wc_core
 * @uses  ServiceRequestExtender Extends the base class
 */
class oAuthRequester extends ServiceRequestExtender
{
  /**
   * Perform a request from the queue
   *
   * This is used internally to perform a request from the queue
   *
   * @access  private
   * @param   int The request ID
   * @param   bool Force the request to be run even if it hasn't met it's scheduled time or was already executed
   * @return  object|void The response for the request or NULL when request not found
   */
  public function perform($id, $force = FALSE)
  {
    $request = db_select(WC_CORE_T_REQUEST)
      ->fields(WC_CORE_T_REQUEST, array(
        'suid',
        'request',
        'time',
        'response_data',
        'status'
      ))
      ->condition('rid', $id)
      ->condition('suid', $this->ServiceRequest->serviceId)
      ->execute()
      ->fetchAssoc();

    // Nothing Found
    if (!$request) return NULL;

    // Tear apart the variables
    $requestData = unserialize($request['request']);

    // It is already complete
    if (($request->status == 'completed' || $request->status == 'error') && !$force)
      return $this->ServiceRequest->retrieve($id);

    // Check the time
    if ($request->time !== 0 && $request->time > time() && !$force)
      return $this->ServiceRequest->retrieve($id);

    // Get the service
    $config = $this->ServiceRequest->serviceInfo;
    $serviceCreds = _wc_core_getServiceCreds($this->ServiceRequest->service);
    $userCreds    = _wc_core_getUserCreds($this->ServiceRequest->service);

    $accessTokenURL  = $config['service'][$this->service]['accessTokenURL'];
    $authenticateURL = $config['authenticateURL'];
    $authorizeURL    = $config['authorizeURL'];
    $requestTokenURL = $config['requestTokenURL'];
    $consumerKey     = $serviceCreds['consumer_key'];
    $consumerSecret  = $serviceCreds['consumer_secret'];

    if (!class_exists('oauthConnectionDrupal')) require_once(dirname(__FILE__) . '/oauth-class.inc');

    $object = new oauthConnectionDrupal($accessTokenURL, $authenticateURL, $authorizeURL, $requestTokenURL);
    $object->createConnection($consumerKey, $consumerSecret, $userCreds['access_token'], $userCreds['access_token_secret']);
    $object->host = $this->ServiceRequest->serviceInfo['apiBase'];

    // Make the Request
    $url = $this->ServiceRequest->serviceInfo['apiBase'] . $requestData['endPoint'];
    $isError = FALSE;
    $requestData['error'] = NULL;

    try {
      $perform = $object->oAuthRequest($url, strtoupper($requestData['http_method']), $requestData['arguments']);
    }
    catch (OAuthException $e) {
      $isError              = TRUE;
      $requestData['error'] = $e;
    }

    // Process the Response
    $response = $this->ServiceRequest->interpretResponse($perform, $requestData['responseFormat']);

    // Request Callback
    if ($requestData['callback'] !== NULL)
      call_user_func_array($requestData['callback'], array(
        $response,
        $isError,
        $requestData
      ));

    // Update the database
    try {
      $update = db_update(WC_CORE_T_REQUEST)
        ->fields(array(
          'request' => serialize($requestData),
          'response_data' => serialize($response),
          'status' => ($isError) ? 'error' : 'completed'
        ))
        ->condition('rid', $id)
        ->execute();
    }
    catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
    }

    return $response;
  }
}

/**
 * API Requester to interface with API Auth services
 *
 * @package  wc_core
 * @uses  ServiceRequestExtender
 */
class apiRequester extends ServiceRequestExtender
{
  /**
   * Internal API Auth Service Requester
   * 
   * @param  int  $id The request ID
   * @param  boolean $force Force the request again
   * @return object|void The response for the request or NULL when request not found
   * @access  private
   */
  public function perform($id, $force = FALSE)
  {
    $request = db_select(WC_CORE_T_REQUEST)
      ->fields(WC_CORE_T_REQUEST, array(
        'suid',
        'request',
        'time',
        'response_data',
        'status'
      ))
      ->condition('rid', $id)
      ->condition('suid', $this->ServiceRequest->serviceId)
      ->execute()
      ->fetchAssoc();

    // Nothing Found
    if (!$request) return NULL;

    // Tear apart the variables
    $requestData = unserialize($request['request']);
    $http_method = strtolower($requestData['http_method']);

    // It is already complete
    if (($request->status == 'completed' || $request->status == 'error') && !$force)
      return $this->ServiceRequest->retrieve($id);

    // Check the time
    if ($request->time !== 0 && $request->time > time() && !$force)
      return $this->ServiceRequest->retrieve($id);

    // Get the service
    $config = $this->ServiceRequest->serviceInfo;
    $serviceCreds = _wc_core_getServiceCreds($this->ServiceRequest->service);
    $userCreds    = _wc_core_getUserCreds($this->ServiceRequest->service);

    // Formulate the request
    $url = $this->ServiceRequest->serviceInfo['apiBase'] . $requestData['endPoint'];

    // Query Arguments
    if ($http_method == 'get' AND is_array($requestData['arguments']) AND count($requestData['arguments']) > 0)
      $url .= '?'.http_build_query($requestData['arguments'], NULL, '&');

    if (!class_exists('Curl')) require_once(dirname(__FILE__) . '/curl-class.inc');

    $object = new Curl($url);
    
    // Username/password
    if (isset($config['auth_username']) AND isset($config['auth_password']))
      $object->http_login($config['auth_username'], $config['auth_password']);
    elseif(isset($config['auth_username']))
      $object->http_login($config['auth_username']);
    elseif(isset($config['auth_password']))
      $object->http_login('', $config['auth_password']);

    // cURL Options
    if (isset($config['curl_options'])) :
      foreach($config['curl_options'] as $k => $v) $object->option($k, $v);
    endif;

    // Specific HTTP headers
    if (isset($config['http_headers'])) :
      foreach($config['http_headers'] as $k => $v) $object->http_header($k, $v);
    endif;

    // Arguments
    if ($http_method !== 'get')
      $object->{$http_method}($requestData['arguments']);

    // Make the Request
    $isError = FALSE;
    $requestData['error'] = NULL;

    // This returns FALSE on error or the raw response
    $perform = $object->execute();

    if ($object->error_code !== NULL) :
      $isError = TRUE;
      $requestData['error']  = $object->error_string;
    endif;

    // Process the Response
    $response = $this->ServiceRequest->interpretResponse($perform, $requestData['responseFormat']);

    // Request Callback
    if ($requestData['callback'] !== NULL)
      call_user_func_array($requestData['callback'], array(
        $response,
        $isError,
        $requestData
      ));

    // Update the database
    try {
      $update = db_update(WC_CORE_T_REQUEST)
        ->fields(array(
          'request' => serialize($requestData),
          'response_data' => serialize($response),
          'status' => ($isError) ? 'error' : 'completed'
        ))
        ->condition('rid', $id)
        ->execute();
    }
    catch (Exception $e) {
      watchdog_exception(WC_CORE_EXCEPTION_CATEGORY, $e);
    }

    return $response;
  }
}
