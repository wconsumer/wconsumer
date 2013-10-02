<?php
namespace Drupal\wconsumer\Authentication\Oauth2;

use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Exception as WconsumerException;
use Drupal\wconsumer\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth2\Plugin as Oauth2Plugin;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;


class Oauth2 extends AuthencationBase implements AuthInterface {
  /**
   * @var string
   */
  public $authorizeURL;

  /**
   * @var string
   */
  public $accessTokenURL;

  /**
   * @var array
   */
  public $defaultScopes;

  /**
   * Guzzle client to make HTTP requests to oauth provider
   *
   * @var \Guzzle\Http\Client
   */
  public $client;



  public function signRequest(Client $client, $user = NULL) {
    $userId = (isset($user) ? $user->uid : NULL);
    $accessToken = $this->service->requireCredentials($userId)->secret;
    $client->addSubscriber(new Oauth2Plugin($accessToken));
  }

  public function authorize($user, array $scopes = array()) {
    if (isset($this->defaultScopes)) {
      $scopes = array_merge($this->defaultScopes, $scopes);
    }

    $state = array(
      'key' => uniqid('state_', true),
      'scopes' => $scopes,
    );
    $this->state($state);

    $url =
      $this->authorizeURL . '?' .
      http_build_query(array(
        'client_id'     => $this->service->requireServiceCredentials()->token,
        'redirect_uri'  => $this->service->getCallbackUrl(),
        'scope'         => join(',', $scopes),
        'state'         => $state['key'],
        'response_type' => 'code',
      ), null, '&');

    drupal_goto($url, array('external' => TRUE));
  }

  /**
   * Log the User out of the System
   *
   * @uses ServiceBase Removes their credentials
   */
  public function logout($user) {
    $this->service->setCredentials(null, $user->uid);
  }

  /**
   * Callback for authencation
   *
   * @param object $user   The User Object
   * @param array  $values The array of values passed
   *
   * @throws WconsumerException
   */
  public function onCallback($user, $values) {
    $values = $values[0];

    $state = $this->state();

    if (!isset($values['state']) || $values['state'] !== $state['key']) {
      throw new WconsumerException('State for OAuth2 Interface not matching');
    }

    if (empty($values['code'])) {
      throw new WconsumerException('No code passed to OAuth2 Interface');
    }

    $accessTokenResponse = $this->requestAccessToken($this->service->requireServiceCredentials(), $values['code']);

    if (!empty($accessTokenResponse['error'])) {
      throw new WconsumerException("Error while requesting access_token: '{$accessTokenResponse['error']}'");
    }

    if (empty($accessTokenResponse['access_token'])) {
      throw new WconsumerException("Invalid access token response: '".var_export($accessTokenResponse, true)."'");
    }

    $credentials = new Credentials('dummy', $accessTokenResponse['access_token'], $state['scopes']);
    $this->service->setCredentials($credentials, $user->uid);
  }

  private function requestAccessToken(Credentials $serviceCredentials, $code) {
    // @codeCoverageIgnoreStart
    if (!isset($this->client)) {
      $this->client = Wconsumer::instance()->container['httpClient'];
    }
    // @codeCoverageIgnoreEnd

    $request = $this->client->post(
      $this->accessTokenURL,
      array(
        'Accept' => 'application/json'
      ),
      array(
        'client_id'     => $serviceCredentials->token,
        'client_secret' => $serviceCredentials->secret,
        'code'          => $code,
        'redirect_uri'  => $this->service->getCallbackUrl(),
        'grant_type'    => 'authorization_code',
      )
    );

    $response = $this->client->send($request);

    try {
      $responseArray = $response->json();
    }
    catch (RuntimeException $e) {
      if (json_last_error() !== JSON_ERROR_NONE) {
        $responseArray = self::parseResponse($response->getBody(true));
      }
      else {
        throw $e;
      }
    }

    return $responseArray;
  }

  private function state($value = NULL) {
    if (func_num_args() > 0) {
      return $this->session('oauth2_state', $value);
    }
    else {
      return $this->session('oauth2_state');
    }
  }

  public function validateServiceCredentials(Credentials $credentials) {
    try {
      $response = $this->requestAccessToken($credentials, 'dummy');
    }
    catch (ClientErrorResponseException $e) {
      // Invalid/unknown $credentials->token
      if ($e->getResponse()->getStatusCode() == 404) {
        return FALSE;
      }

      throw $e;
    }

    // Invalid $credentials->secret
    if (!empty($response['error']) && $response['error'] === 'incorrect_client_credentials') {
      return FALSE;
    }

    return TRUE;
  }
}