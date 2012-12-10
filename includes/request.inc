<?php
/**
 * @file
 * Handling the Requests of APIs
 *
 * @package wc_core
 *
 */

/**
 * Perform a request on a Service
 *
 * We are going to add this item to a queue or we are going to process it instantaniously
 *
 * @param    string
 * @param    string The Endpoint on the API to request
 * @param    array Optional Parameters to pass
 * @param    string The HTTP method (get, put, post, delete)
 * @param    int The time to perform the request (if = 0, will be performed right now)
 * @param    mixed The callback to be performed, see {@link http://php.net/manual/en/function.call-user-func-array.php}
 * @return    int|bool The request ID for the database or FALSE if it is failed
 * @throws   WConsumerException
 */
function wc_perform_request($service, $endPoint = NULL, $http_method = 'get', $time = 0, $callback = NULL)
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

    // Store the request

    // Are we going to perform it right now?
    return ($time == 0)  ? _wc_perform_request($request_id) : $request_id;
}

/**
 * Retrieve a request from the queue
 *
 * We can see if the request was performed and retrieve information about the request.
 *
 * @param  int The request ID
 * @return object
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
        return FALSE;

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
 * @access   private
 * @param   int The request ID
 * @param   bool Force the request to be run even if it hasn't met it's scheduled time
 * @return   object The `wc_retrieve_request` for the request
 */
function _wc_perform_request($id, $force = FALSE)
{
  
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
