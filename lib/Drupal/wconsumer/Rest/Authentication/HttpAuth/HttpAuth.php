<?php
/**
 * HTTP Authentication
 *
 * @package wconsumer
 * @subpackage request
 */
namespace Drupal\wconsumer\Rest\Authentication\HttpAuth;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase;
use Drupal\wconsumer\Common\AuthInterface;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin as GuzzleHttpAuth;
use Guzzle\Http\Client;


/**
 * HTTP Authentication
 *
 * Used for services that require a specific HTTP username and password
 *
 * @package wconsumer
 * @subpackage request
 */
class HttpAuth extends AuthencationBase implements AuthInterface {

  public function is_initialized($type = 'user') {
    if ($type == 'user') {
      return TRUE;
    }

    return parent::is_initialized($type);
  }

  public function sign_request($client) {
    $credentials = $this->_instance->getServiceCredentials();

    /** @var $client Client */
    $client->addSubscriber(new GuzzleHttpAuth($credentials->token, $credentials->secret));
  }

  /**
   * @codeCoverageIgnore
   */
  public function authenticate($user) {
  }

  /**
   * @codeCoverageIgnore
   */
  public function logout($user) {
  }

  /**
   * @codeCoverageIgnore
   */
  public function onCallback(&$user, $values) {
  }
}
