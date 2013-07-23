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
  public $needsUsername = false;

  /**
   * Define if they need a password
   * 
   * @var boolean
   */
  public $needsPassword = false;
  
  /**
   * Format Registry Credentials
   * 
   * @param array
   * @return array
   */
  public function formatRegistry($data)
  {
    if ($this->needsUsername AND ( ! isset($data['username']) OR empty($data['username'])))
      throw new WcException('HTTP Auth requires username and is not set or is empty.');

    if ($this->needsPassword AND ( ! isset($data['password']) OR empty($data['password'])))
      throw new WcException('HTTP Auth requires password and is not set or is empty.');
    
    return array(
      'username' => ($this->needsUsername) ? $data['username'] : '',
      'password' => ($this->needsPassword) ? $data['password'] : ''
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
        $registry = $this->_instance->getRegistry();
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
   * @param object Client
   * @access private
   */
  public function sign_request(&$client)
  {
    $registry = $this->_instance->getRegistry();

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
   */
  public function authenticate(&$user) { }
  
  /**
   * Log the user out
   *
   * Not needed for HTTP Auth
   */
  public function logout(&$logout) { }

  /**
   * Callback
   *
   * Not needed for HTTP Auth
   */
  public function onCallback(&$user, $values) { }
}
