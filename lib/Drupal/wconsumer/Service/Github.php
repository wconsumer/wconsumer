<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Github extends Base {
  protected $name = 'github';
  protected $apiUrl = 'https://api.github.com/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->niceName             = 'GitHub';
    $meta->consumerKeyLabel     = 'Client ID';
    $meta->consumerSecretLabel  = 'Client Secret';
    $meta->registerAppUrl       = 'https://github.com/settings/applications/new';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeUrl = 'https://github.com/login/oauth/authorize';
    $auth->accessTokenUrl = 'https://github.com/login/oauth/access_token';

    return $auth;
  }
}