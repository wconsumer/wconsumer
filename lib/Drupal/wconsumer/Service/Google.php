<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Google extends Base {
  protected $name = 'google';
  protected $apiUrl = 'https://www.googleapis.com/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->niceName             = 'Google';
    $meta->consumerKeyLabel     = 'Client ID';
    $meta->consumerSecretLabel  = 'Client Secret';
    $meta->registerAppUrl       = 'https://cloud.google.com/console';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeURL   = 'https://accounts.google.com/o/oauth2/auth';
    $auth->accessTokenURL = 'https://accounts.google.com/o/oauth2/token';

    return $auth;
  }
}
