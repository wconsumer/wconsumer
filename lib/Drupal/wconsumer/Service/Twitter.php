<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Twitter extends Base {
  protected $name = 'twitter';
  protected $apiUrl = 'https://api.twitter.com/1.1/';
  public $registerAppUrl = 'https://dev.twitter.com/apps/new';


  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenURL  = 'https://api.twitter.com/oauth/request_token';
    $auth->authorizeURL     = 'https://api.twitter.com/oauth/authorize';
    $auth->accessTokenURL   = 'https://api.twitter.com/oauth/access_token';
    $auth->authenticateURL  = 'https://api.twitter.com/oauth/authenticate';

    return $auth;
  }
}