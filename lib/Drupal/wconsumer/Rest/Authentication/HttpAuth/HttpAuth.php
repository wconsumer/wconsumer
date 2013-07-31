<?php
/**
 * HTTP Authentication
 *
 * @package wconsumer
 * @subpackage request
 */
namespace Drupal\wconsumer\Rest\Authentication\HttpAuth;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase,
  Drupal\wconsumer\Common\AuthInterface,
  Guzzle\Plugin\CurlAuth\CurlAuthPlugin as GuzzleHttpAuth,
  Drupal\wconsumer\Exception as WcException;

use Guzzle\Http\Client;


/**
 * HTTP Authentication
 *
 * Used for services that require a specific HTTP username and/or password
 *
 * @package wconsumer
 * @subpackage request
 */
class HttpAuth extends AuthencationBase implements AuthInterface {
  /**
   * Define if they need a username
   *
   * @var boolean
   */
  private $needsUsername;

  /**
   * Define if they need a password
   *
   * @var boolean
   */
  private $needsPassword;



  public function __construct($instance = null, $needsUsername = false, $needsPassword = false)
  {
    parent::__construct($instance);

    $this->needsUsername = $needsUsername;
    $this->needsPassword = $needsPassword;
  }

  /**
   * Format Registry Credentials
   *
   * @param array $data
   * @return array
   *
   * @throws WcException
   */
  public function formatServiceCredentials($data)
  {
    if ($this->needsUsername && empty($data['username']))
      throw new WcException('HTTP Auth requires username and it is not set or is empty.');

    if ($this->needsPassword && empty($data['password']))
      throw new WcException('HTTP Auth requires password and it is not set or is empty.');

    return array(
      'username' => ($this->needsUsername) ? $data['username'] : null,
      'password' => ($this->needsPassword) ? $data['password'] : null,
    );
  }

  /**
   * Format the Saved Credentials
   *
   * Not used in HTTP Auth API
   *
   * @param array
   * @return array Empty array
   */
  public function formatCredentials($data)
  {
    return array();
  }

  /**
   * Validate if they're setup
   *
   * @param string
   * @return boolean
   */
  public function is_initialized($type = 'user')
  {
    switch($type) {
      case 'system' :
        $registry = $this->_instance->getServiceCredentials();
        if (! $registry OR ! isset($registry->credentials)) return FALSE;

        if ($this->needsUsername AND empty($registry->credentials['username']))
          return FALSE;

        if ($this->needsPassword AND empty($registry->credentials['password']))
          return FALSE;

        return TRUE;
        break;

      case 'user' :
        return TRUE;
        break;

      default :
        return FALSE;
    }
  }

  /**
   * Sign the request before sending it off
   *
   * @param Client $client
   * @access private
   */
  public function sign_request(&$client)
  {
    $registry = $this->_instance->getServiceCredentials();

    // Add the auth plugin to the client object
    $authPlugin = new GuzzleHttpAuth(
      ($this->needsUsername) ? $registry->credentials['username'] : '',
      ($this->needsPassword) ? $registry->credentials['password'] : ''
    );

    $client->addSubscriber($authPlugin);
  }

  /**
   * Authenticate the User
   *
   * Not needed for HTTP Auth
   *
   * @codeCoverageIgnore
   */
  public function authenticate(&$user) { }

  /**
   * Log the user out
   *
   * Not needed for HTTP Auth
   *
   * @codeCoverageIgnore
   */
  public function logout(&$logout) { }

  /**
   * Callback
   *
   * Not needed for HTTP Auth
   *
   * @codeCoverageIgnore
   */
  public function onCallback(&$user, $values) { }
}
