<?php
namespace Drupal\wconsumer\Rest\Authentication;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase,
  Drupal\wconsumer\Common\AuthInterface,
  Guzzle\Plugin\Oauth\OauthPlugin as GuzzleOAuth;

// OAuth Classes
use Drupal\wconsumer\Rest\Authentication\Oauth,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthConsumer,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthRequest,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthServer,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthSignatureMethod,

  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthSignatureMethod_PLAINTEXT,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthSignatureMethod_RSA_SHA1,

  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthToken,
  Drupal\wconsumer\Rest\Authentication\Oauth\OAuthUtil;

/**
 * OAuth Authentication Class
 *
 * @todo Refactor
 * @package wconsumer
 * @subpackage request
 */
class Oauth extends AuthencationBase implements AuthInterface {
  /**
   * Contains the last HTTP status code returned.
   *
   * @var int
   */
  public $http_code;

  /**
   * Contains the last API call.
   *
   * @var string
   */
  public $url;

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
   * Verify SSL Cert.
   *
   * @var bool
   */
  public $ssl_verifypeer = FALSE;

  /**
   * Response format.
   *
   * @var string
   */
  public $format = 'json';

  /**
   * Decode returned json data.
   *
   * @var bool
   */
  public $decode_json = TRUE;

  /**
   * Contains the last HTTP headers returned.
   *
   * @var array
   */
  public $http_info;

  /**
   * HTTP User Agent
   *
   * @var string
   */
  public $useragent = 'Web Consumer Manager';

  /**
   * Immediately retry the API call if the response was not successful
   *
   * @var bool
   * @deprecated
   */
  public $retry = FALSE;

  /**
   * @var string
   */
  public $authenticateURL;

  /**
   * @var string
   */
  public $requestTokenURL;
  
  protected $consumer = NULL;
  protected $token = NULL;

  function getAccessTokenURL()  { return $this->accessTokenURL; }
  function getAuthenticateURL() { return $this->authenticateURL; }
  function getAuthorizeURL()    { return $this->authorizeURL; }
  function getRequestTokenURL() { return $this->requestTokenURL; }

  // Debug Helpers
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

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
      throw new \Drupal\wconsumer\Exception('OAuth Consumer Key/Secret not set in formatting pass.' . print_r($d, TRUE));

    if (empty($d['consumer_key']) OR empty($d['consumer_secret']))
      throw new \Drupal\wconsumer\Exception('OAuth Consumer Key/Secret empty in formatting pass.' . print_r($d, TRUE));

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
    if (! isset($d['oauth_token']) OR ! isset($d['oauth_token_secret']))
      throw new \Drupal\wconsumer\Exception('OAuth Access Token/Secret not set in formatting pass.' . print_r($d, TRUE));

    if (empty($d['oauth_token']) OR empty($d['oauth_token_secret']))
      throw new \Drupal\wconsumer\Exception('OAuth Access Key/Secret empty in formatting pass.' . print_r($d, TRUE));

    $credentials = array();
    $credentials['oauth_token'] = $d['oauth_token'];
    $credentials['oauth_token_secret'] = $d['oauth_token_secret'];
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

        if (! isset($registry['access_token']) OR ! isset($registry['access_token_secret']))
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

    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key' => $registry->credentials['consumer_key'],
      'consumer_secret' => $registry->credentials['consumer_secret'],
      'token' => $credentials->credentials['access_token'],
      'token_secret' => $credentials->credentials['access_token_secret'],
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
    
    try {
      $this->createConnection();
      $request_token = $this->getRequestToken($callback);
    }
    catch (\Exception $e) {
      // Throw this back to the front-end
      throw new \Exception($e->getMessage(), 500, $e);
    }

    // Something went south on the returned request
    if (! isset($request_token['oauth_token']) OR ! isset($request_token['oauth_token_secret']))
      return drupal_set_message('Unknown error with retrieving the request token: '.print_r($request_token, TRUE), 'error');

    // They've got it!
    $_SESSION[$this->_instance->getName().':oauth_token'] = $token = $request_token['oauth_token'];
    $_SESSION[$this->_instance->getName().':oauth_token_secret'] = $request_token['oauth_token_secret'];
    $url = $this->createAuthorizeURL($request_token['oauth_token'], FALSE);

    // Redirect them away!
    drupal_goto($url, array(
      'external' => TRUE
    ));
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
    // Find the Old Stuff
    $token = (isset($_SESSION[$this->_instance->getName().':oauth_token'])) ? $_SESSION[$this->_instance->getName().':oauth_token'] : null;
    $token_secret = (($_SESSION[$this->_instance->getName().':oauth_token_secret'])) ? $_SESSION[$this->_instance->getName().':oauth_token_secret'] : null;

    if ($token == null || $token_secret == null) {
      throw \Exception('Temporary token/secret not found in user\'s session. Cannot complete.');
      return;
    }

    try {
      $this->createConnection(null, null, $token, $token_secret);
      $access_tokens = $this->getAccessToken($_REQUEST['oauth_verifier']);

      $access_tokens = $this->formatCredentials($access_tokens);
    }
    catch (\Exception $e) {
      // Throw this back to the front-end
      throw new \Exception($e->getMessage(), 500, $e);
    }

    // Save them in the service
    $this->_instance->setCredentials(array(
      'access_token' => $access_tokens['oauth_token'],
      'access_token_secret' => $access_tokens['oauth_token_secret']
    ), $user->uid);

    return true;
  }

  /**
   * Create a OAuth Connection for the Service
   *
   * @param string|null A consumer key or NULL to retrieve from registry
   * @param string|null A consumer secret or NULL to retrieve from registry
   * @param string|null OAuth Token or NULL to retrieve creds. from registry
   * @param string|null OAuth Token or NULL to retrieve creds. from registry
   * @return void
   */
  public function createConnection($consumer_key = NULL, $consumer_secret = NULL, $oauth_token = NULL, $oauth_token_secret = NULL)
  {
    // If they didn't pass them
    if ($consumer_key == NULL AND $consumer_secret == NULL) :
      $registry = (array) $this->_instance->getRegistry();

      // Retrieve them from the registry
      if (
          count($registry) == 0 OR ! isset($registry['credentials'])
        OR
          empty($registry['credentials']['consumer_key'])
        OR
          empty($registry['credentials']['consumer_secret'])
        )
        throw new \Exception('Consumer key/secret not set in registry: '.print_r($registry, TRUE));

      $consumer_key = $registry['credentials']['consumer_key'];
      $consumer_secret = $registry['credentials']['consumer_secret'];
    endif;

    $this->sha1_method = new Oauth\OAuthHmacSha1();

    $this->consumer = new OAuthConsumer(
      $consumer_key,
      $consumer_secret
    );

    if (is_null($oauth_token) && is_null($oauth_token_secret)) {
      $credentials = $this->_instance->getCredentials();
      
      if ($credentials !== FALSE && is_array($credentials->credentials)
        AND isset($credentials->credentials->oauth_token)
        AND isset($credentials->credentials->oauth_token_secret)
      ) :
        $oauth_token = $credentials->credentials->oauth_token;
        $oauth_token_secret = $credentials->credentials->$oauth_token_secret;
      endif;
    }

    if (! is_null($oauth_token) && ! is_null($oauth_token_secret))
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    else
      $this->token = NULL;
  }

  /**
   * Get a request_token from Twitter
   *
   * @return array A key/value array containing oauth_token and oauth_token_secret
   */
  public function getRequestToken($oauth_callback = NULL) {
    $parameters = array();
    if (!empty($oauth_callback))
      $parameters['oauth_callback'] = $oauth_callback;

    $request = $this->oAuthRequest($this->getRequestTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);

    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @return string
   */
  public function createAuthorizeURL($token) {
    if (is_array($token)) $token = $token['oauth_token'];

    return $this->getAuthorizeURL() . '?oauth_token='.$token;
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @param bool
   * @return array array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham")
   */
  public function getAccessToken($oauth_verifier = FALSE) {
    $parameters = array();
    if (!empty($oauth_verifier))
      $parameters['oauth_verifier'] = $oauth_verifier;

    $request = $this->oAuthRequest($this->getAccessTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * One time exchange of username and password for access token and secret.
   *
   * Not used in core
   *
   * @return array array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham",
   *                "x_auth_expires" => "0")
   */
  public function getXAuthToken($username, $password) {
    $parameters = array();
    $parameters['x_auth_username'] = $username;
    $parameters['x_auth_password'] = $password;
    $parameters['x_auth_mode'] = 'client_auth';
    $request = $this->oAuthRequest($this->getAccessTokenURL(), 'POST', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   *
   * @param string
   * @param array
   * @return mixed
   */
  public function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);

    if ($this->format === 'json' && $this->decode_json)
      return json_decode($response);

    return $response;
  }

  /**
   * POST wrapper for oAuthRequest.
   *
   * @param string
   * @param array
   * @return mixed
   */
  public function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);

    if ($this->format === 'json' && $this->decode_json)
      return json_decode($response);

    return $response;
  }

  /**
   * DELETE wrapper for oAuthReqeust.
   *
   * @param string
   * @param array
   * @return mixed
   */
  public function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);

    if ($this->format === 'json' && $this->decode_json)
      return json_decode($response);

    return $response;
  }

  /**
   * Format and sign an OAuth / API request
   *
   * @param string
   * @param string HTTP Method
   * @param array
   */
  public function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0)
      $url = "{$this->host}{$url}.{$this->format}";

    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);

    switch ($method) {
      case 'GET':
        return $this->http($request->to_url(), 'GET');
        break;

      default:
        return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

  /**
   * Make an HTTP request
   *
   * @param string
   * @param string HTTP method
   * @param aray
   * @return object API results
   */
  public function http($url, $method, $postfields = NULL) {
    $this->http_info = array();
    $ci = curl_init();

    // Curl settings
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch (strtoupper($method)) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields))
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        break;

      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields))
          $url = "{$url}?{$postfields}";
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    $this->url = $url;

    if (curl_errno($ci))
      throw new OAuthException('CURL Error: '.curl_errno($ci));

    curl_close($ci);
    return $response;
  }

  /**
   * Get the header info to store
   *
   * @param object
   * @param string
   * @return string
   */
  public function getHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->http_header[$key] = $value;
    }

    return strlen($header);
  }
}
