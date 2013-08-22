<?php
  namespace Drupal\wconsumer\Authentication\Oauth2\AccessToken;



  interface TokenInterface
  {
    public function buildAuthorizationHeader();
  }
