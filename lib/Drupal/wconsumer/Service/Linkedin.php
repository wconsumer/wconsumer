<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Linkedin extends Base {
  protected $name = 'linkedin';
  protected $apiUrl = 'http://api.linkedin.com/v1/';
  public $registerAppUrl = 'https://www.linkedin.com/secure/developer?newapp';


  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->accessTokenURL = 'https://api.linkedin.com/uas/oauth/accessToken';
    $auth->authenticateURL = 'https://api.linkedin.com/uas/oauth/authenticate';
    $auth->authorizeURL = 'https://www.linkedin.com/uas/oauth/authenticate';
    $auth->requestTokenURL = 'https://api.linkedin.com/uas/oauth/requestToken';

    return $auth;
  }
}