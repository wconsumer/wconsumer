<?php
  namespace Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken;

  class MacToken implements TokenInterface
  {
    public function __construct($config)
    {
      $this->config = $config;
    }

    public function buildAuthorizationHeader()
    {
      $macString = 'MAC ';

      foreach ($this->config as $key => $value)
      {
          $macString .= sprintf('%s="%s",'.PHP_EOL, $key, $value);
      }

      $macString = trim($macString, PHP_EOL.",");

      return $macString;
    }
  }
?>