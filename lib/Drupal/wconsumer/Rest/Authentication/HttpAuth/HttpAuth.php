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
  public function is_initialized($type = 'user')
  {
    switch($type) {
      case 'system' :
        return ($this->_instance->getServiceCredentials() !== null);
      break;

      case 'user' :
        return TRUE;
      break;

      default :
        return FALSE;
      break;
    }
  }

  public function sign_request(&$client)
  {
    $credentials = $this->_instance->getServiceCredentials();
    $client->addSubscriber(new GuzzleHttpAuth($credentials->token, $credentials->secret));
  }

  /**
   * @codeCoverageIgnore
   */
  public function authenticate(&$user) { }

  /**
   * @codeCoverageIgnore
   */
  public function logout(&$logout) { }

  /**
   * @codeCoverageIgnore
   */
  public function onCallback(&$user, $values) { }
}
