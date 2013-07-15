<?php
namespace Drupal\wconsumer\Common;

use Drupal\wconsumer\Rest\Request as RestBase;
use Drupal\wconsumer\Rest\Authentication\Oauth as Oauth;
use Drupal\wconsumer\Service;
use Drupal\wconsumer\Queue\Item;

/**
 * Common Functions Used in Testing
 */
class Tests {
  /**
   * Create a mock service
   * 
   * @return Object
   */
  public static function mockService()
  {
    $linkedin = new MockService();

    // Setup Request Interface
    $linkedin->request = new RestBase(); //::Instance();
    $linkedin->request->apiURL = 'http://api.twitter.com/1.1/';

    // Authentication
    $linkedin->request->authencation = new Oauth($linkedin);
    $linkedin->request->authencation->accessTokenURL = 'https://api.linkedin.com/uas/oauth/accessToken';
    $linkedin->request->authencation->authenticateURL = 'https://api.linkedin.com/uas/oauth/authenticate';
    $linkedin->request->authencation->authorizeURL = 'https://www.linkedin.com/uas/oauth/authenticate';
    $linkedin->request->authencation->requestTokenURL = 'https://api.linkedin.com/uas/oauth/requestToken';

    // Service Specific Options
    $linkedin->options = new Drupal\wconsumer\Common\Options;
    $linkedin->options->enable('allow login');
    
    $services['linkedin'] = $linkedin;
    return $services;
  }
}


/**
 * @ignore
 */
class MockService {

}
