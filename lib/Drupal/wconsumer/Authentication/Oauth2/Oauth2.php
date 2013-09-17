<?php
namespace Drupal\wconsumer\Authentication\Oauth2;

use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Exception as WconsumerException;
use Drupal\wconsumer\Authentication\Base as AuthencationBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth2\Plugin as Oauth2Plugin;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;

/**
 * OAuth2 Authentication Class
 *
 * @package wconsumer
 * @subpackage request
 */
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
   * Guzzle client to make HTTP requests to oauth provider
   *
   * @var \Guzzle\Http\Client
   */
  public $client;



  public function signRequest(Client $client, $user = NULL)
  {
    $userId = (isset($user) ? $user->uid : NULL);
    $accessToken = $this->service->requireCredentials($userId)->secret;
    $client->addSubscriber(new Oauth2Plugin($accessToken));
  }

  /**
   * Authenticate the user and set them up for OAuth Authentication
   *
   * @param object $user The user object
   * @param array  $scopes
   */
  public function authenticate($user, array $scopes = array())
  {
    $callback = $this->service->callback();
    $serviceCredentials = $this->service->requireServiceCredentials();

    $state = array(
      'key' => uniqid('state_', true),
      'scopes' => $scopes,
    );

    $this->state($state);

    $url =
      $this->authorizeURL . '?' .
      http_build_query(array(
        'client_id'     => $serviceCredentials->token,
        'redirect_uri'  => $callback,
        'scope'         => join(',', $scopes),
        'state'         => $state['key'],
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

    $serviceCredentials = $this->service->requireServiceCredentials();

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
        'code'          => $values['code'],
      )
    );

    $response = $this->client->send($request);
    if ($response->isError()) {
      throw new WconsumerException('Unknown error on OAuth 2 callback: '.print_r($response, true));
    }

    $response = $response->json();
    if (!empty($response['error'])) {
      throw new WconsumerException("Error while requesting access_token: '{$response['error']}'");
    }

    if (empty($response['access_token'])) {
      throw new WconsumerException("Invalid access token response: '".var_export($response, true)."'");
    }

    $credentials = new Credentials('dummy', $response['access_token'], $state['scopes']);
    $this->service->setCredentials($credentials, $user->uid);
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
    return TRUE;
  }
}