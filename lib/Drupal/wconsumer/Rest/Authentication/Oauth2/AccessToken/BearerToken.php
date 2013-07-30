<?php
  namespace Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken;



  class BearerToken implements TokenInterface
  {
    private $accessToken;



    public function __construct($accessToken)
    {
      if (empty($accessToken))
      {
        throw new \InvalidArgumentException("Cannot create a bearer token from an empty access token");
      }

      $this->accessToken = $accessToken;
    }

    public function buildAuthorizationHeader()
    {
      return "Bearer {$this->accessToken}";
    }
  }
?>