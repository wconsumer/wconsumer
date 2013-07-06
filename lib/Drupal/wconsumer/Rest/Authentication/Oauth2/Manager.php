<?php
namespace Drupal\wconsumer\Rest\Authentication\Oauth2;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase,
  Drupal\wconsumer\Common\AuthInterface,
  Drupal\wconsumer\Rest\Authentication\Oauth2\Plugin as GuzzleOAuth2,
  Drupal\wconsumer\Exception as ManagerException;

/**
 * OAuth Authentication Class
 *
 * @todo Refactor
 * @package wconsumer
 * @subpackage request
 */
class Manager extends AuthencationBase implements AuthInterface {
  /**
   * Set up the API root URL.
   *
   * @var string
   */
  public $host = NULL;

  /**
   * Set timeout default.
   *
   * @var int
   */
  public $timeout = 30;

  /**
   * Set connect timeout.
   *
   * @var int
   */
  public $connecttimeout = 30;

  /**
   * Decode returned json data.
   *
   * @var bool
   */
  public $decode_json = TRUE;

  /**
   * HTTP User Agent
   *
   * @var string
   */
  public $useragent = 'Web Consumer Manager';

  /**
   * @var string
   */
  public $authorizeURL;

  /**
   * @var string
   */
  public $accessTokenURL;
  
  /**
   * Scopes to be requested access to
   * 
   * @var array
   */
  public $scopes = array();

  /**
   * Scope delimiter
   * 
   * @var string
   */
  public $scopeDelimiter = ',';


  protected $consumer = NULL;
  protected $token = NULL;

  function getAuthorizeURL()  { return $this->authorizeURL; }
  function getAccessTokenURL() { return $this->accessTokenURL; }

  /**
   * Process the Registry Information to be in the format to be saved properly
   *
   * @return array
   * @param array
   * @throws Drupal\wconsumer\Exception
   */
  public function formatRegistry($d)
  {
    if (! isset($d['consumer_key']) OR ! isset($d['consumer_secret']))
      throw new ManagerException('OAuth2 Consumer Key/Secret not set in formatting pass.' . print_r($d, TRUE));

    if (empty($d['consumer_key']) OR empty($d['consumer_secret']))
      throw new ManagerException('OAuth2 Consumer Key/Secret empty in formatting pass.' . print_r($d, TRUE));

    $credentials = array();
    $credentials['consumer_key'] = $d['consumer_key'];
    $credentials['consumer_secret'] = $d['consumer_secret'];
    return $credentials;
  }

  /**
   * Process the Registry Information to be in the format to be saved properly
   *
   * @return array
   * @param array
   * @throws Drupal\wconsumer\Exception
   */
  public function formatCredentials($d)
  {
    if (! isset($d['access_token']))
      throw new ManagerException('OAuth2 Access Token not set in formatting pass.' . print_r($d, TRUE));

    if (empty($d['access_token']))
      throw new ManagerException('OAuth2 Access Key empty in formatting pass.' . print_r($d, TRUE));

    $credentials = array();
    $credentials['access_token'] = $d['access_token'];
    return $credentials;
  }

  /**
   * Validate the Authentication data to see if they are properly setup
   *
   * @return bool
   * @param string $type 'user' to check the user's info, 'system' to check the system specific info
   */
  public function is_initialized($type = 'user')
  {
    switch ($type)
    {
      case 'user' :
        $credentials = $this->_instance->getCredentials();
        if (! $credentials OR ! isset($registry->credentials)) return FALSE;

        if (! isset($registry['access_token']))
          return FALSE;

        // Access token/secret exist
        return TRUE;
        break;

      case 'system' :
        $registry = $this->_instance->getRegistry();
        if (! $registry OR ! isset($registry->credentials)) return FALSE;

        if (! isset($registry->credentials['consumer_key']) OR ! isset($registry->credentials['consumer_secret']))
          return FALSE;

        // Consumer key and secret exist
        // TODO: Add in additional authentication by checking the key/secret against the API
        return TRUE;
        break;

      // Unknown to check for
      default :
        return FALSE;
    }
  }

  /**
   * Sign the request with the authentication parameters
   * 
   * @param object Guzzle Client Passed by reference
   * @return void
   * @access private
   */
  public function sign_request(&$client)
  {
    $registry = $this->_instance->getRegistry();
    $credentials = $this->_instance->getCredentials();

    $client->addSubscriber(new GuzzleOAuth2(array(
      'consumer_key' => $registry->credentials['consumer_key'],
      'consumer_secret' => $registry->credentials['consumer_secret'],
      'token_type' => 'Bearer',
      'access_token' => $credentials->credentials['access_token'],
    )));
  }

  /**
   * Authenticate the user and set them up for OAuth Authentication
   *
   * @param object the user object
   */
  public function authenticate(&$user)
  {
    // Retrieve the OAuth request token
    $callback = $this->_instance->callback();
    $registry = $this->_instance->getRegistry();

    $url = $this->authorizeURL
      .'?client_id='.$registry->credentials['consumer_key']
      .'&redirect_uri='.urlencode($callback)
      .'&scope='.implode($this->scopeDelimiter, $this->scopes)
      .'&state=wconsumer';

    return drupal_goto($url, array('external' => TRUE));

  }

  /**
   * Log the User out of the System
   *
   * @uses ServiceBase Removes their credentials
   */
  public function logout(&$user) {
    return $this->_instance->setCredentials(null, $user->uid);
  }

  /**
   * Callback for authencation
   *
   * @param object $user The User Object
   * @param object $values The array of values passed
   */
  public function onCallback(&$user, $values) {
    // Check the state
    if (!isset($values[0]['state']) OR $values[0]['state'] !== 'wconsumer')
      throw new \Exception('State for OAuth2 Interface not matching');

    if (! isset($values[0]['code']))
      throw new \Exception('No code passed to OAuth2 Interface');

    $registry = $this->_instance->getRegistry();

    // Make a new request with Guzzle
    $url = $this->accessTokenURL
      .'?client_id='.$registry->credentials['consumer_key']
      .'&client_secret='.$registry->credentials['consumer_secret']
      .'&code='.$values[0]['code'];

    // Make a request to the service
    \Guzzle\Http\StaticClient::mount();
    $response = \Guzzle::post($this->accessTokenURL, array(
        'headers' => array('Accept' => 'application/json'),
        'body'    => array(
          'client_id' => $registry->credentials['consumer_key'],
          'client_secret' => $registry->credentials['consumer_secret'],
          'code' => $values[0]['code'],
        ),
        'query'   => array(),
        'timeout' => $this->timeout,
        'debug'   => true,
        'verify' => false,
    ));

    if ($response->isError())
      throw new ManagerException('Unknown error on OAuth 2 callback: '.print_r($response));

    $tokens = (array) $response->json();

    try {
      $access_tokens = $this->formatCredentials($tokens);
    }
    catch (ManagerException $e) {
      // Throw this back to the front-end
      throw new ManagerException($e->getMessage(), 500, $e);
    }

    // Save them in the service
    $this->_instance->setCredentials($access_tokens, $user->uid);

    return true;
  }
}
