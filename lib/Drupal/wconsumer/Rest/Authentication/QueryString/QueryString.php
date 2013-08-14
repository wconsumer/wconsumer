<?php
/**
 * Query String Authentication
 *
 * @package wconsumer
 * @subpackage request
 */
namespace Drupal\wconsumer\Rest\Authentication\QueryString;

use Drupal\wconsumer\Rest\Authentication\AuthInterface;
use Drupal\wconsumer\Exception as WcException;
use Drupal\wconsumer\Rest\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\Rest\Authentication\QueryString\Plugin as GuzzlePlugin;
use Guzzle\Http\Client;


/**
 * Query String Authentication
 *
 * Used for services that require a query string parameter for requests
 *
 * @package wconsumer
 * @subpackage request
 */
class QueryString extends AuthencationBase implements AuthInterface {
  /**
   * The key to be added
   *
   * If this is not set, it will be prompted from the user to set on the
   * administration panel
   *
   * @var boolean
   */
  public $queryKey;

  /**
   * Label for the query key
   *
   * @var string
   */
  public $keyLabel = 'Query Key';

  /**
   * Label for the query value
   *
   * @var string
   */
  public $valueLabel = 'Query Value';



  public function isInitialized($type, $user = NULL) {
    if ($type == 'user') {
      return true;
    }

    return parent::isInitialized($type, $user);
  }

  public function signRequest($client, $user = NULL) {
    $credentials = $this->service->getServiceCredentials();

    /** @var $client Client */
    $client->addSubscriber(new GuzzlePlugin(array(
      'query_key' => $this->getQueryKey($credentials),
      'query_value' => $credentials->secret,
    )));
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
  public function onCallback($user, $values) {
  }

  private function getQueryKey(Credentials $credentials = null) {
    if (!empty($this->queryKey)) {
      return $this->queryKey;
    }

    if (isset($credentials)) {
      return $credentials->token;
    }

    throw new WcException('Query String Auth credentials not set');
  }
}
