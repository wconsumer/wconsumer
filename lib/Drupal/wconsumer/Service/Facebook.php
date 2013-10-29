<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Facebook extends Service {
  protected $name = 'facebook';
  protected $apiUrl = 'https://graph.facebook.com/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->niceName             = 'Facebook';
    $meta->consumerKeyLabel     = 'App ID';
    $meta->consumerSecretLabel  = 'App Secret';
    $meta->registerAppUrl       = 'https://developers.facebook.com/apps';

    return $meta;
  }

  public function validateServiceCredentials(Credentials $credentials) {
    // Disable service credentials validation since Facebook always returns 400 Bad Request error
    // and it's not possible to detect if that's due to invalid credentials, invalid code or broken request.
    return TRUE;
  }

  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeUrl   = 'https://www.facebook.com/dialog/oauth';
    $auth->accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';

    return $auth;
  }
}
