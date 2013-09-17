<?php
namespace Drupal\wconsumer\Authentication\Oauth;

use Drupal\wconsumer\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Util\Serialize;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Oauth\OauthPlugin as GuzzleOAuth;



class Oauth extends AuthencationBase implements AuthInterface {

  public $requestTokenURL;
  public $authorizeURL;
  public $accessTokenURL;
  public $authenticateURL;



  public function signRequest(Client $client, $user = NULL) {
    $serviceCredentials = $this->service->requireServiceCredentials();
    $userCredentials = $this->service->requireCredentials(isset($user) ? $user->uid : null);

    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $serviceCredentials->token,
      'consumer_secret' => $serviceCredentials->secret,
      'token'           => $userCredentials->token,
      'token_secret'    => $userCredentials->secret,
    )));
  }

  public function authenticate($user, array $scopes = array()) {
    // Fetch request token
    $requestToken = $this->requestRequestToken($this->service->requireServiceCredentials());

    // Save request token
    $this->requestToken($requestToken);

    // Redirect user to the authorization page
    $authorizeUrl = $this->createAuthorizeURL($requestToken->token);
    drupal_goto($authorizeUrl, array('external' => TRUE));
  }

  public function logout($user) {
    $this->service->setCredentials(null, $user->uid);
  }

  public function onCallback($user, $values) {
    $serviceCredentials = $this->service->requireServiceCredentials();
    $requestToken = $this->requestToken();

    /** @var $client \Guzzle\Http\Client */
    $client = Wconsumer::instance()->container['httpClient'];
    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $serviceCredentials->token,
      'consumer_secret' => $serviceCredentials->secret,
      'token'           => $requestToken->token,
      'token_secret'    => $requestToken->secret,
      'verifier'        => @$values[0]['oauth_verifier'],
    )));

    $response = $client->post($this->accessTokenURL)->send()->getBody(true);

    $accessToken = static::parseParameters($response);
    $this->service->setCredentials($accessToken, $user->uid);
  }

  public function validateServiceCredentials(Credentials $credentials) {
    // Try to request request_token. We have invalid service credentials if it fails with 401 Unathorized, either
    // token or secret or both.

    try {
      $this->requestRequestToken($credentials);
    }
    catch (ClientErrorResponseException $e) {
      if ($e->getResponse()->getStatusCode() == 401) {
        return FALSE;
      }

      throw $e;
    }

    return TRUE;
  }

  private function requestRequestToken(Credentials $serviceCredentials) {
    /** @var Client $client */
    $client = Wconsumer::instance()->container['httpClient'];

    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $serviceCredentials->token,
      'consumer_secret' => $serviceCredentials->secret,
      'callback'        => $this->service->callback(),
    )));

    $response = $client->post($this->requestTokenURL)->send()->getBody(true);

    $requestToken = static::parseParameters($response);

    return $requestToken;
  }

  private function requestToken($value = null) {
    if (func_num_args() > 0) {
      $this->session('oauth_request_token', Serialize::serialize($value));
      return $value;
    }
    else {
      return Serialize::unserialize($this->session('oauth_request_token'), Credentials::getClass());
    }
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
  private static function parseParameters($input) {
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

    if (empty($parsed_parameters['oauth_token']) || empty($parsed_parameters['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Access Token response '{$input}'");
    }

    return new Credentials($parsed_parameters['oauth_token'], $parsed_parameters['oauth_token_secret']);
  }
}