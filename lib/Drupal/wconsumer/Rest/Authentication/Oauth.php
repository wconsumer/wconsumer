<?php
namespace Drupal\wconsumer\Rest\Authentication;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase;
use Drupal\wconsumer\Common\AuthInterface;
use Drupal\wconsumer\Service;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin as GuzzleOAuth;

// OAuth Classes
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException;
use Drupal\wconsumer\Rest\Authentication\Oauth\OAuthRequest;

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
    $tokens = static::parse_parameters($response);
    if (empty($tokens['oauth_token']) || empty($tokens['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Request Token response '{$response}'");
    }

    $this->useRequestToken($tokens);

    $authorizeUrl = $this->createAuthorizeURL($tokens['oauth_token']);
    drupal_goto($authorizeUrl, array('external' => TRUE));
  }

  public function logout(&$user) {
    return $this->_instance->setCredentials(null, $user->uid);
  }

  public function onCallback(&$user, $values) {
    $registry = $this->_instance->getServiceCredentials();
    if (!$registry ||
      !isset($registry->credentials) ||
      empty($registry->credentials['consumer_key']) ||
      empty($registry->credentials['consumer_secret'])) {
      throw new \BadMethodCallException("Service credentials should be set prior to calling authenticate()");
    }

    $requestToken = $this->useRequestToken();

    $client = Service::createHttpClient();
    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $registry->credentials['consumer_key'],
      'consumer_secret' => $registry->credentials['consumer_secret'],
      'token'           => $requestToken['oauth_token'],
      'token_secret'    => $requestToken['oauth_token_secret'],
      'verifier'        => @$values[0]['oauth_verifier'],
    )));

    $response = $client->post($this->accessTokenURL)->send()->getBody(true);
    $accessToken = static::parse_parameters($response);
    if (empty($accessToken['oauth_token']) || empty($accessToken['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Access Token response '{$response}'");
    }

    $accessToken = $this->formatCredentials($accessToken);

    // Save them in the service
    $this->_instance->setCredentials(
      array(
        'access_token'        => $accessToken['oauth_token'],
        'access_token_secret' => $accessToken['oauth_token_secret']
      ),
      $user->uid
    );

    return true;
  }

  private function useRequestToken($value = null) {
    $key = "{$this->_instance->getName()}:oauth_user_credentials";

    if (func_num_args() > 0) {
      $_SESSION[$key] = $value;
    }
    else {
      if (!isset($_SESSION[$key])) {
        throw new \BadMethodCallException('Request token data not found in current session');
      }
    }

    return $_SESSION[$key];
  }

  private function createAuthorizeURL($token) {
    $delimiter = '?';
    if ((string)parse_url($this->authorizeURL, PHP_URL_QUERY) !== '') {
      $delimiter = '&';
    }

    $url = $this->authorizeURL . $delimiter . 'oauth_token='.urlencode($token);

    return $url;
  }

  // This function takes a input like a=b&a=c&d=e and returns the parsed
  // parameters like this
  // array('a' => array('b','c'), 'd' => 'e')
  private static function parse_parameters($input) {
    if (!isset($input) || !$input) {
      return array();
    }

    $pairs = explode('&', $input);
    $parsed_parameters = array();

    foreach ($pairs as $pair) {
      $split = explode('=', $pair, 2);
      $parameter = urldecode($split[0]);
      $value = isset($split[1]) ? urldecode($split[1]) : '';

      if (isset($parsed_parameters[$parameter])) {

        // We have already recieved parameter(s) with this name, so add to the list
        // of parameters with this name
        if (is_scalar($parsed_parameters[$parameter])) {
          // This is the first duplicate, so transform scalar (string) into an array
          // so we can add the duplicates
          $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
        }

        $parsed_parameters[$parameter][] = $value;
      }
      else {
        $parsed_parameters[$parameter] = $value;
      }
    }

    return $parsed_parameters;
  }
}