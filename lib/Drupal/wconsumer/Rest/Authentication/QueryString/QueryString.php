<?php
/**
 * Query String Authentication
 *
 * @package wconsumer
 * @subpackage request
 */
namespace Drupal\wconsumer\Rest\Authentication\QueryString;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase,
  Drupal\wconsumer\Common\AuthInterface,
  Guzzle\Plugin\CurlAuth\CurlAuthPlugin as GuzzleHttpAuth,
  Drupal\wconsumer\Exception as WcException;

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
   * Format Registry Credentials
   * 
   * @param array
   * @return array
   */
  public function formatRegistry($data)
  {
    if ( $this->queryKey !== NULL AND ( ! isset($data['query_key']) OR empty($data['query_key']) ))
      throw new WcException('Query String Auth requires a query key and that it is not set or is empty.');

    if ( ( ! isset($data['query_value']) OR empty($data['query_value'])))
      throw new WcException('HTTP Auth requires a query value and it is not set or is empty.');
    
    return array(
      'query_key' => ($this->queryKey !== NULL) ? $data['query_key'] : $this->queryKey,
      'password' => $data['query_value']
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

        if ($this->needsUsername AND empty($registry->credentials['query_key']))
          return FALSE;

        if ($this->needsPassword AND empty($registry->credentials['query_value']))
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
