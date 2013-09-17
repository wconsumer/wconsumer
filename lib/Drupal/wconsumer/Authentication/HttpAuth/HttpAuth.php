<?php
namespace Drupal\wconsumer\Authentication\HttpAuth;

use Drupal\wconsumer\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Authentication\AuthInterface;
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

  public function signRequest(Client $client, $user = NULL) {
    $credentials = $this->service->requireServiceCredentials();
    $client->addSubscriber(new GuzzleHttpAuth($credentials->token, $credentials->secret));
  }

  /**
   * @codeCoverageIgnore
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
