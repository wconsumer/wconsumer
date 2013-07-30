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
  //Guzzle\Plugin\CurlAuth\CurlAuthPlugin as GuzzleHttpAuth,
  Drupal\wconsumer\Exception as WcException,
  Drupal\wconsumer\Rest\Authentication\QueryString\Plugin as GuzzlePlugin,
  Drupal\wconsumer\Exception as ManagerException;

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

  /**
   * Format Registry Credentials
   *
   * @param array
   * @return array
   */
  public function formatRegistry($data)
  {
    $this->getQueryKey($data);

    if (empty($data['query_value']))
    {
      throw new WcException('Query String Auth requires a query value and it is not set or is empty.');
    }

    return array(
      'query_key' => $data['query_key'],
      'query_value' => $data['query_value']
    );
  }

  /**
   * Format the Saved Credentials
   *
   * Not used in Query String Auth API
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
    switch($type)
    {
      case 'system' :
        $registry = $this->_instance->getRegistry();

        if (!isset($registry) || !isset($registry->credentials))
        {
          return false;
        }

        $credentials = $registry->credentials;

        if (!$this->getQueryKey($credentials, true))
        {
          return false;
        }

        if (empty($credentials['query_value']))
        {
          return false;
        }

        return true;
      break;

      case 'user' :
        return true;
      break;

      default :
        return false;
      break;
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

    $key = $this->getQueryKey($registry->credentials);

    $client->addSubscriber(new GuzzlePlugin(array(
      'query_key' => $key,
      'query_value' => $registry->credentials['query_value'],
    )));
  }

  /**
   * Authenticate the User
   *
   * Not needed for Query String Auth
   */
  public function authenticate(&$user) { }

  /**
   * Log the user out
   *
   * Not needed for Query String Auth
   */
  public function logout(&$logout) { }

  /**
   * Callback
   *
   * Not needed for Query String Auth
   */
  public function onCallback(&$user, $values) { }

  private function getQueryKey($credentials, $silent = false)
  {
    if (!empty($this->queryKey))
    {
      return $this->queryKey;
    }

    if (!empty($credentials['query_key']))
    {
      return $credentials['query_key'];
    }

    if (!$silent)
    {
      throw new WcException('Query String Auth requires a query key and that it is not set or is empty.');
    }

    return false;
  }
}
