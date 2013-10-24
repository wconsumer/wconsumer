<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



class Linkedin extends Base {
  protected $name = 'linkedin';
  protected $apiUrl = 'https://api.linkedin.com/v1/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->niceName            = 'LinkedIn';
    $meta->consumerKeyLabel    = 'API Key';
    $meta->consumerSecretLabel = 'Secret Key';
    $meta->registerAppUrl      = 'https://www.linkedin.com/secure/developer?newapp';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth2($this);

    $baseurl = "https://www.linkedin.com/uas/oauth2";
    $auth->authorizeUrl = "{$baseurl}/authorization";
    $auth->accessTokenUrl = "{$baseurl}/accessToken";
    $auth->authorizeWithUrlParameter = 'oauth2_access_token';

    return $auth;
  }
}