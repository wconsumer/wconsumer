<?php
namespace Drupal\wconsumer\Rest\Authentication\Oauth2;

use Drupal\wconsumer\Rest\Authentication as AuthencationBase,
  Drupal\wconsumer\Common\AuthInterface,
  Drupal\wconsumer\Rest\Authentication\Oauth2\Plugin as Oauth2Plugin,
  Drupal\wconsumer\Exception as ManagerException;
use Drupal\wconsumer\Service;

/**
 * OAuth Authentication Class
 *
 * @todo Refactor
 * @package wconsumer
 * @subpackage request
 */
class Manager extends AuthencationBase implements AuthInterface {
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
   * Guzzle client to make HTTP requests to oauth provider
   *
   * @var \Guzzle\Http\Client
   */
  public $client;


  /**
   * Process the Registry Information to be in the format to be saved properly
   *
   * @return array
   * @param array
   * @throws \Drupal\wconsumer\Exception
   */
  public function formatServiceCredentials($d)
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
   * @throws \Drupal\wconsumer\Exception
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
        $registry = $this->_instance->getCredentials();

        if (!$registry || !isset($registry->credentials)) {
          return FALSE;
        }

        if (!isset($registry->credentials['access_token'])) {
          return FALSE;
        }

        // Access token/secret exist
        return TRUE;
      break;

      case 'system' :
        $registry = $this->_instance->getServiceCredentials();

        if (!$registry || !isset($registry->credentials)) {
          return FALSE;
        }

        if (!isset($registry->credentials['consumer_key']) || !isset($registry->credentials['consumer_secret'])) {
          return FALSE;
        }

        // Consumer key and secret exist
        // TODO: Add in additional authentication by checking the key/secret against the API
        return TRUE;
      break;

      // Unknown to check for
      default :
        return FALSE;
      break;
    }
  }

  /**
   * Sign the request with the authentication parameters
   *
   * @param \Guzzle\Http\Client $client Guzzle Client Passed by reference
   * @return void
   */
  public function sign_request(&$client)
  {
    $accessToken = $this->_instance->getCredentials()->credentials['access_token'];
    $client->addSubscriber(new Oauth2Plugin($accessToken));
  }

  /**
   * Authenticate the user and set them up for OAuth Authentication
   *
   * @param object $user The user object
   */
  public function authenticate(&$user)
  {
    // Retrieve the OAuth request token
    $callback = $this->_instance->callback();
    $registry = $this->_instance->getServiceCredentials();

    $url =
      $this->authorizeURL .
      http_build_query(array(
        'client_id'     => $registry->credentials['consumer_key'],
        'redirect_uri'  => $callback,
        'scope'         => implode(',', $this->scopes),
        'satte'         => 'wconsumer',
      ), null, '&');

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
   * @param array $values The array of values passed
   *
   * @throws ManagerException
   */
  public function onCallback(&$user, $values) {
    // Check the state
    if (!isset($values[0]['state']) || $values[0]['state'] !== 'wconsumer') {
      throw new ManagerException('State for OAuth2 Interface not matching');
    }

    if (empty($values[0]['code'])) {
      throw new ManagerException('No code passed to OAuth2 Interface');
    }

    $registry = $this->_instance->getServiceCredentials();

    // @codeCoverageIgnoreStart
    if (!isset($this->client)) {
      $this->client = Service::createHttpClient();
    }
    // @codeCoverageIgnoreEnd

    $request = $this->client->post(
      $this->accessTokenURL,
      array(
        'Accept' => 'application/json'
      ),
      array(
        'client_id'     => $registry->credentials['consumer_key'],
        'client_secret' => $registry->credentials['consumer_secret'],
        'code'          => $values[0]['code'],
      )
    );

    $response = $request->send();
    if ($response->isError()) {
      throw new ManagerException('Unknown error on OAuth 2 callback: '.print_r($response, true));
    }

    $response = $response->json();
    if (!empty($response['error'])) {
      throw new ManagerException("Error while requesting access_token: '{$response['error']}'");
    }

    $tokens = $this->formatCredentials($response);
    $this->_instance->setCredentials($tokens, $user->uid);
  }
}
