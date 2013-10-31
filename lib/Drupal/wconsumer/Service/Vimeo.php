<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Vimeo extends Service {
  protected $name = 'vimeo';
  protected $apiUrl = 'http://vimeo.com/api/rest/v2/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->consumerKeyLabel    = 'Client ID';
    $meta->consumerSecretLabel = 'Client Secret';
    $meta->registerAppUrl      = 'https://developer.vimeo.com/apps/new';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenUrl  = 'https://vimeo.com/oauth/request_token';
    $auth->authorizeUrl     = 'https://vimeo.com/oauth/authorize';
    $auth->accessTokenUrl   = 'https://vimeo.com/oauth/access_token';

    return $auth;
  }

  protected function unauthorizedRequestHandler() {
    return new UnauthorizedResponseHandler\Vimeo($this);
  }
}