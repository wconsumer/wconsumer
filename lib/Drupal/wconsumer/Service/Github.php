<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Rest\Authentication\Oauth2\Oauth2;
use Drupal\wconsumer\Service\Base;



class Github extends Base {
  protected $name = 'github';
  protected $apiUrl = 'https://api.github.com/';



  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeURL = 'https://github.com/login/oauth/authorize';
    $auth->accessTokenURL = 'https://github.com/login/oauth/access_token';

    return $auth;
  }
}