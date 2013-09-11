<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Github extends Base {
  protected $name = 'github';
  protected $apiUrl = 'https://api.github.com/';
  public $registerAppUrl = 'https://github.com/settings/applications/new';




  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeURL = 'https://github.com/login/oauth/authorize';
    $auth->accessTokenURL = 'https://github.com/login/oauth/access_token';

    return $auth;
  }
}