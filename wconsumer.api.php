<?php
/**
 * @file
 * Hooks provided by wconsumer Core
 *
 * The .module file cannot be in a namespace
 */

/**
 * Implements hook_wconsumer_config()
 */
function hook_wconsumer_config($services) {
  // Instantiate a new service object for this service module
  $service_object = new service_object();

  // Setup Request Interface
  // For this module, we're going to be interfacing with a REST based API
  // To implement a custom Requests interface, look at 
  // `lib/Drupal/wconsumer/Rest/Request.php`
  $service_object->request = new Drupal\wconsumer\Rest\Request();

  // Define the base of the REST API URL
  $service_object->request->apiURL = 'http://api.linkedin.com/v1/';

  // Authentication for the Service
  // This scenario would be OAuth1
  $service_object->request->authencation = new Drupal\wconsumer\Rest\Authentication\Oauth($service_object);

  // For the authentication method, you would have to provide settings such as these for OAuth 1
  $service_object->request->authencation->accessTokenURL = 'https://api.linkedin.com/uas/oauth/accessToken';
  $service_object->request->authencation->authenticateURL = 'https://api.linkedin.com/uas/oauth/authenticate';
  $service_object->request->authencation->authorizeURL = 'https://www.linkedin.com/uas/oauth/authenticate';
  $service_object->request->authencation->requestTokenURL = 'https://api.linkedin.com/uas/oauth/requestToken';

  // We have a number of other authentication methods
  // To review, take a look at this wiki page:
  // https://github.com/mywebclass/wconsumer/wiki/Authentication
  
  // Service Specific Options
  // These settings are on a per-service basis such as allow the user to log in and create
  // and account with this service.
  $service_object->options = new Drupal\wconsumer\Common\Options;
  $service_object->options->enable('allow login');
  
  // Register the service into our configuration array
  // The key of the array below, "linkedin", is the internal name used to identify this service
  $services['linkedin'] = $service_object;

  return $services;
}

/**
 * The Dummy Service Object
 */
class service_object extends Drupal\wconsumer\ServiceBase {

}
