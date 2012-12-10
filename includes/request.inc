<?php
/**
 * @file
 * Handling the Requests of APIs
 *
 * @package wc_core
 *
 */

/**
 * Add an item to the Core Queue
 *
 * We are going to add this item to a queue or we are going to process it instantaniously
 *
 * @param    string
 * @param    string The Endpoint on the API to request
 * @param    string The HTTP method (get, put, post, delete)
 * @param    int The time to perform the request (if = 0, will be performed right now)
 * @param    mixed The callback to be performed, see {@link http://php.net/manual/en/function.call-user-func-array.php}
 * @return   int The request ID for the database
 */
function wc_perform_request($service, $endPoint = NULL, $http_method = 'get', $time = 0, $callback = NULL)
{

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

}

/**
 * Perform a request from the queue
 *
 * This is used internally to perform a request from the queue
 *
 * @access  private
 * @param   int The request ID
 * @param   bool Force the request to be run even if it hasn't met it's scheduled time
 * @return  object The `wc_retrieve_request` for the request
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
