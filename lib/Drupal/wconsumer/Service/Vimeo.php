<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Vimeo extends Base {
  protected $name = 'vimeo';
  protected $apiUrl = 'https://api.twitter.com/1.1/';
  public $registerAppUrl = 'https://developer.vimeo.com/apps/new';


  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenURL  = 'https://vimeo.com/oauth/request_token';
    $auth->authorizeURL     = 'https://vimeo.com/oauth/authorize';
    $auth->accessTokenURL   = 'https://vimeo.com/oauth/access_token';

    return $auth;
  }
}