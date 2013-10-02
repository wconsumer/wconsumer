<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Flickr extends Base {
  protected $name = 'flickr';
  protected $apiUrl = 'http://api.flickr.com/services/rest/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->registerAppUrl = 'http://www.flickr.com/services/apps/create/apply/';
    $meta->consumerKeyLabel = 'Key (API Key)';
    $meta->consumerSecretLabel = 'Secret';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenUrl  = 'http://www.flickr.com/services/oauth/request_token';
    $auth->authorizeUrl     = 'http://www.flickr.com/services/oauth/authorize';
    $auth->accessTokenUrl   = 'http://www.flickr.com/services/oauth/access_token';

    return $auth;
  }
}