<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Rest\Authentication\Oauth\Oauth;



class Meetup extends Base {
  protected $name = 'meetup';
  protected $apiUrl = 'http://api.meetup.com/';



  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenURL  = 'https://api.meetup.com/oauth/request/';
    $auth->authorizeURL     = 'http://www.meetup.com/authorize/';
    $auth->accessTokenURL   = 'https://api.meetup.com/oauth/access/';
    $auth->authenticateURL  = 'http://www.meetup.com/authenticate/';

    return $auth;
  }
}