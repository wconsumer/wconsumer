<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;



class Meetup extends Service {
  protected $name = 'meetup';
  protected $apiUrl = 'http://api.meetup.com/';



  public function getMeta() {
    $meta = parent::getMeta();

    $meta->consumerKeyLabel    = 'Key';
    $meta->consumerSecretLabel = 'Secret';
    $meta->registerAppUrl      = 'http://www.meetup.com/meetup_api/oauth_consumers/create/';

    return $meta;
  }

  protected function initAuthentication() {
    $auth = new Oauth($this);

    $auth->requestTokenUrl  = 'https://api.meetup.com/oauth/request/';
    $auth->authorizeUrl     = 'http://www.meetup.com/authorize/';
    $auth->accessTokenUrl   = 'https://api.meetup.com/oauth/access/';

    return $auth;
  }
}