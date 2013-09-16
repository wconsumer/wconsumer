<?php
namespace Drupal\wconsumer\Authentication\QueryString;

use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Exception as WcException;
use Drupal\wconsumer\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\QueryString\Plugin as GuzzlePlugin;
use Guzzle\Http\Client;


/**
 * Query String Authentication
 *
 * Used for services that require a query string parameter for requests
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



  public function signRequest(Client $client, $user = NULL) {
    $credentials = $this->service->requireServiceCredentials();

    $client->addSubscriber(new GuzzlePlugin(array(
      'query_key' => $this->getQueryKey($credentials),
      'query_value' => $credentials->secret,
    )));
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
