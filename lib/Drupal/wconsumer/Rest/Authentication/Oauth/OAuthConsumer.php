<?php
/**
 * @file OAuthConsumer
 */
namespace Drupal\wconsumer\Rest\Authentication\Oauth;

/**
 * OAuthConsumer
 *
 * @package wconsumer
 * @subpackage oauth
 */
class OAuthConsumer {
  public $key;
  public $secret;

  /**
   * Setup the OAuthConsumer
   *
   * @param string $key
   * @param string $secret
   * @param string $callback_url URL to return after getting the consumer key
   */
  public function __construct($key, $secret, $callback_url = NULL) {
    $this->key          = $key;
    $this->secret       = $secret;
    $this->callback_url = $callback_url;
  }

  public function __toString() {
    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
  }
}
