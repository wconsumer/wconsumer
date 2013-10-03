<?php
namespace Drupal\wconsumer\Service;



use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Dropbox extends Base {
  protected $name = 'dropbox';
  protected $apiUrl = 'https://api.dropbox.com/1/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->niceName             = 'Dropbox';
    $meta->consumerKeyLabel     = 'App key';
    $meta->consumerSecretLabel  = 'App secret';
    $meta->registerAppUrl       = 'https://www.dropbox.com/developers/apps/create';

    return $meta;
  }

  public function validateServiceCredentials(Credentials $credentials) {
    // Disable service credentials validation since Dropbox always returns 400 Bad Request error
    // and it's not possible to detect if that's due to invalid credentials, invalid code or broken request.
    return TRUE;
  }

  public function getCallbackUrl() {
    $url = parent::getCallbackUrl();
    $url = preg_replace('|^http://|', 'https://', $url); // Dropbox.com only allows HTTPS callback
    return $url;
  }

  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $auth->authorizeUrl   = 'https://www.dropbox.com/1/oauth2/authorize';
    $auth->accessTokenUrl = 'https://api.dropbox.com/1/oauth2/token';

    return $auth;
  }
}

?>