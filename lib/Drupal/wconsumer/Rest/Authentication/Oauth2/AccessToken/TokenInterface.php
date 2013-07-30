<?php
  namespace Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken;



  interface TokenInterface
  {
    public function buildAuthorizationHeader();
  }
