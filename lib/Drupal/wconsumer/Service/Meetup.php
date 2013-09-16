<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Meetup extends Base {
  protected $name = 'meetup';
  protected $apiUrl = 'http://api.meetup.com/';



  public function getMeta() {
    $meta = parent::getMeta();
    $meta->registerAppUrl = 'http://www.meetup.com/meetup_api/oauth_consumers/create/';
    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenURL  = 'https://api.meetup.com/oauth/request/';
    $auth->authorizeURL     = 'http://www.meetup.com/authorize/';
    $auth->accessTokenURL   = 'https://api.meetup.com/oauth/access/';
    $auth->authenticateURL  = 'http://www.meetup.com/authenticate/';

    return $auth;
  }
}