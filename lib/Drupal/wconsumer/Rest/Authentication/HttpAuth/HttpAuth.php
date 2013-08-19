<?php
/**
 * HTTP Authentication
 *
 * @package wconsumer
 * @subpackage request
 */
namespace Drupal\wconsumer\Rest\Authentication\HttpAuth;

use Drupal\wconsumer\Rest\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Rest\Authentication\AuthInterface;
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

  public function isInitialized($type, $user = NULL) {
    if ($type == 'user') {
      return TRUE;
    }

    return parent::isInitialized($type, $user);
  }

  public function signRequest(Client $client, $user = NULL) {
    $credentials = $this->service->requireServiceCredentials();
    $client->addSubscriber(new GuzzleHttpAuth($credentials->token, $credentials->secret));
  }

  /**
   * @codeCoverageIgnore
   *
   * @param       $user
   * @param array $scopes
   */
  public function authenticate($user, array $scopes = array()) {
  }

  /**
   * @codeCoverageIgnore
   */
  public function logout($user) {
  }

  /**
   * @codeCoverageIgnore
   */
  public function onCallback($user, $values) {
  }
}
