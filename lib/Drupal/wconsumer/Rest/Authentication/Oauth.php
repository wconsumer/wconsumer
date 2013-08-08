<?php
namespace Drupal\wconsumer\Rest\Authentication;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase;
use Drupal\wconsumer\Common\AuthInterface;
use Drupal\wconsumer\Service;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin as GuzzleOAuth;

// OAuth Classes
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthConsumer;
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException;
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthRequest;
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthUtil;

/**
 * OAuth Authentication Class
 *
 * @package wconsumer
 * @subpackage request
 */
class Oauth extends AuthencationBase implements AuthInterface {

  /**
   * @var string
   */
  public $requestTokenURL;

  /**
   * @var string
   */
  public $authorizeURL;

  /**
   * @var string
   */
  public $accessTokenURL;

  /**
   * @var string
   */
  public $authenticateURL;



  public function formatServiceCredentials($data)
  {
    return $this->requireKeys(
      array('consumer_key', 'consumer_secret'),
      $data,
      'OAuth Consumer Key/Secret not set or is empty in formatting pass'
    );
  }

  public function formatCredentials($data)
  {
    return $this->requireKeys(
      array('oauth_token', 'oauth_token_secret'),
      $data,
      'OAuth Access Token/Secret not set or is empty in formatting pass'
    );
  }

  public function is_initialized($type = 'user') {
    switch ($type) {
      case 'user':
        $registry = $this->_instance->getCredentials();
        if (!$registry || !isset($registry->credentials)) {
          return FALSE;
        }

        $credentials = $registry->credentials;
        if (empty($credentials['access_token']) || empty($credentials['access_token_secret'])) {
          return FALSE;
        }

        return TRUE;
      break;

      case 'system':
        $registry = $this->_instance->getServiceCredentials();
        if (!$registry || !isset($registry->credentials)) {
          return FALSE;
        }

        $credentials = $registry->credentials;

        if (empty($credentials['consumer_key']) || empty($credentials['consumer_secret'])) {
          return FALSE;
        }

        return TRUE;
      break;

      default:
        return FALSE;
      break;
    }
  }

  public function sign_request(&$client)
  {
    $registry = $this->_instance->getServiceCredentials();
    $credentials = $this->_instance->getCredentials();

    if (!isset($registry) || !isset($registry->credentials)) {
      throw new \BadMethodCallException("Service credentials not set");
    }

    if (!isset($credentials) || !isset($credentials->credentials)) {
      throw new \BadMethodCallException("No stored user credentials found");
    }

    /** @var $client Client */
    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $registry->credentials['consumer_key'],
      'consumer_secret' => $registry->credentials['consumer_secret'],
      'token'           => $credentials->credentials['access_token'],
      'token_secret'    => $credentials->credentials['access_token_secret'],
    )));
  }

  public function authenticate(&$user)
  {
    // Retrieve the OAuth request token
    $callback = $this->_instance->callback();

    $registry = $this->_instance->getServiceCredentials();
    if (!$registry ||
        !isset($registry->credentials) ||
        empty($registry->credentials['consumer_key']) ||
        empty($registry->credentials['consumer_secret'])) {
      throw new \BadMethodCallException("Service credentials should be set prior to calling authenticate()");
    }

    $client = Service::createHttpClient();
    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $registry->credentials['consumer_key'],
      'consumer_secret' => $registry->credentials['consumer_secret'],
      'callback'        => $callback,
    )));

    $response = $client->post($this->requestTokenURL)->send()->getBody(true);
    $tokens = OAuthUtil::parse_parameters($response);
    if (empty($tokens['oauth_token']) || empty($tokens['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Request Token response '{$response}'");
    }

    $service = $this->_instance->getName();
    $_SESSION["{$service}:oauth_token"] = $tokens['oauth_token'];
    $_SESSION["{$service}:oauth_token_secret"] = $tokens['oauth_token_secret'];

    $authorizeUrl = $this->createAuthorizeURL($tokens['oauth_token']);
    drupal_goto($authorizeUrl, array('external' => TRUE));
  }

  /**
   * Log the User out of the System
   *
   * @uses ServiceBase Removes their credentials
   */
  public function logout(&$user) {
    return $this->_instance->setCredentials(null, $user->uid);
  }

  public function onCallback(&$user, $values) {
    // Find the Old Stuff
    $token = (isset($_SESSION[$this->_instance->getName().':oauth_token'])) ? $_SESSION[$this->_instance->getName().':oauth_token'] : null;
    $token_secret = (($_SESSION[$this->_instance->getName().':oauth_token_secret'])) ? $_SESSION[$this->_instance->getName().':oauth_token_secret'] : null;

    if (empty($token) || empty($token_secret)) {
      throw new \BadMethodCallException('Request token/secret not found in user\'s session. Seems authenticate() has not been called prior to onCallback()?');
    }

    $this->createConnection(null, null, $token, $token_secret);
    $access_tokens = $this->getAccessToken($_REQUEST['oauth_verifier']);

    $access_tokens = $this->formatCredentials($access_tokens);

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
      $registry = (array) $this->_instance->getServiceCredentials();

      // Retrieve them from the registry
      if (
          count($registry) == 0 OR ! isset($registry['credentials'])
        OR
          empty($registry['credentials']['consumer_key'])
        OR
          empty($registry['credentials']['consumer_secret'])
        )
      {
        throw new \BadMethodCallException('Consumer key/secret not set in registry: '.print_r($registry, TRUE));
      }

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

    $response = $this->oAuthRequest($this->getRequestTokenURL(), 'GET', $parameters);

    $token = OAuthUtil::parse_parameters($response);
    if (empty($token['oauth_token']) || empty($token['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Request Token response '{$response}'");
    }

    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);

    return $token;
  }

  private function createAuthorizeURL($token) {
    $delimiter = '?';
    if ((string)parse_url($this->authorizeURL, PHP_URL_QUERY) !== '') {
      $delimiter = '&';
    }

    $url = $this->authorizeURL . $delimiter . 'oauth_token='.urlencode($token);

    return $url;
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

    $request = $this->oAuthRequest($this->accessTokenURL, 'GET', $parameters);
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

    if (curl_errno($ci)) {
      throw new OAuthException('CURL Error: '.curl_errno($ci));
    }

    if ($this->http_code != 200) {
      throw new OAuthException("API returns HTTP error '{$this->http_code}'");
    }

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