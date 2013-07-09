<?php
namespace Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken;

interface TokenInterface
{
    public function __toString();

    public function getFormat();

    public function setFormat($format);
}
