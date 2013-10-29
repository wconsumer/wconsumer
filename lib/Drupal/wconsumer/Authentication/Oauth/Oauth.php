<?php
namespace Drupal\wconsumer\Authentication\Oauth;

use Drupal\wconsumer\Authentication\Authentication;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Util\Serialize;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Oauth\OauthPlugin as GuzzleOAuth;



class Oauth extends Authentication implements AuthInterface {

  public $requestTokenUrl;
  public $authorizeUrl;
  public $accessTokenUrl;



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

  public function authorize($user, array $scopes = array()) {
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

    $response = $client->post($this->accessTokenUrl)->send()->getBody(true);

    $accessToken = static::parseTokenResponse($response);
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

  protected static function parseTokenResponse($input) {
    $parameters = self::parseResponse($input);

    if (empty($parameters['oauth_token']) || empty($parameters['oauth_token_secret'])) {
      throw new OAuthException("Failed to parse Access Token response '{$input}'");
    }

    $credentials = new Credentials($parameters['oauth_token'], $parameters['oauth_token_secret']);

    return $credentials;
  }

  private function requestRequestToken(Credentials $serviceCredentials) {
    /** @var Client $client */
    $client = Wconsumer::instance()->container['httpClient'];

    $client->addSubscriber(new GuzzleOAuth(array(
      'consumer_key'    => $serviceCredentials->token,
      'consumer_secret' => $serviceCredentials->secret,
      'callback'        => $this->service->getCallbackUrl(),
    )));

    $response = $client->post($this->requestTokenUrl)->send()->getBody(true);

    $requestToken = static::parseTokenResponse($response);

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
    if ((string)parse_url($this->authorizeUrl, PHP_URL_QUERY) !== '') {
      $delimiter = '&';
    }

    $url = $this->authorizeUrl . $delimiter . 'oauth_token='.urlencode($token);

    return $url;
  }
}