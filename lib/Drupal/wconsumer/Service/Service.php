<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Exception\AdditionalScopesRequired;
use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Drupal\wconsumer\Service\Exception\ServiceInactive;
use Drupal\wconsumer\Util\Serialize;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;



abstract class Service extends Base {

  public $authentication;

  protected $name;
  protected $apiUrl;

  private $servicesTable = 'wc_service';
  private $usersTable = 'wc_user';



  public function __construct() {
    $this->name = $this->initName();
    $this->authentication = $this->initAuthentication();
  }

  /**
   * @param string|null  $userId
   * @param array        $scopes
   * @return Client
   *
   * @throws Exception\ServiceInactive
   * @throws Exception\NoUserCredentials
   * @throws Exception\AdditionalScopesRequired
   */
  public function api($userId = NULL, array $scopes = array()) {
    $user = new \stdClass();
    $user->uid = (isset($userId) ? $userId : $GLOBALS['user']->uid);

    $this->checkApiAvailability($user, $scopes);

    /** @var Client $client */
    $client = Wconsumer::instance()->container['httpClient'];
    $client->setBaseUrl($this->apiUrl);
    $this->authentication->signRequest($client, $user);
    $client->addSubscriber($this->unauthorizedRequestHandler());

    return $client;
  }

  public function isActive() {
    return $this->isEnabled() && $this->getServiceCredentials();
  }

  public function getName() {
    return $this->name;
  }

  public function isEnabled() {
    $result = db_select($this->servicesTable)
      ->fields($this->servicesTable, array('enabled'))
      ->condition('service', $this->name)
    ->execute()
    ->fetchField();

    if ($result === false) {
      $result = '1';
    }

    return (bool)$result;
  }

  public function setEnabled($value) {
    db_merge($this->servicesTable)
      ->key(array('service' => $this->name))
      ->fields(array(
        'service' => $this->name,
        'enabled' => (int)(bool)$value,
      ))
    ->execute();
  }

  public function setServiceCredentials(Credentials $credentials = null) {
    db_merge($this->servicesTable)
      ->key(array('service' => $this->name))
      ->fields(array(
        'service'     => $this->name,
        'credentials' => Serialize::serialize($credentials),
      ))
    ->execute();
  }

  public function getServiceCredentials() {
    /** @var Credentials $credentials */
    $credentials = null;

    $serializedCredentials = db_select($this->servicesTable)
      ->fields($this->servicesTable, array('credentials'))
      ->condition('service', $this->name)
    ->execute()
    ->fetchField();

    if ($serializedCredentials) {
      $credentials = Serialize::unserialize($serializedCredentials, Credentials::getClass());
    }

    return $credentials;
  }

  public function requireServiceCredentials() {
    $credentials = $this->getServiceCredentials();

    if (!isset($credentials)) {
      throw new \BadMethodCallException("Please set up service credentials before using it");
    }

    return $credentials;
  }

  public function validateServiceCredentials(Credentials $credentials) {
    return $this->authentication->validateServiceCredentials($credentials);
  }

  public function setCredentials(Credentials $credentials = NULL, $user_id = NULL) {
    if ($user_id == NULL) {
      global $user;
      $user_id = $user->uid;
    }

    $serializedCredentials = Serialize::serialize($credentials);

    if (!$user_id) {
      Wconsumer::instance()->session($this->getName(), 'user_credentials', $serializedCredentials);
    }
    else {
      db_merge($this->usersTable)
        ->key(array(
          'service' => $this->name,
          'user_id' => $user_id,
        ))
        ->fields(array(
          'service' => $this->name,
          'user_id' => $user_id,
          'credentials' => $serializedCredentials,
        ))
      ->execute();
    }
  }

  public function getCredentials($user_id = NULL) {
    if (!isset($user_id)) {
      global $user;
      $user_id = $user->uid;
    }

    $serializedCredentials = NULL;
    if ($user_id) {
      $serializedCredentials =
        db_select($this->usersTable)
          ->fields($this->usersTable, array('credentials'))
          ->condition('service', $this->name)
          ->condition('user_id', $user_id)
        ->execute()
        ->fetchField();
    }
    else {
      $serializedCredentials = Wconsumer::instance()->session($this->getName(), 'user_credentials');
    }

    $credentials = null;
    if ($serializedCredentials) {
      $credentials = Serialize::unserialize($serializedCredentials, Credentials::getClass());
    }

    /** @var Credentials $credentials */
    return $credentials;
  }

  public function requireCredentials($userId = NULL) {
      $credentials = $this->getCredentials($userId);

      if (!isset($credentials)) {
          throw new \BadMethodCallException("Please connect your account with service before using it");
      }

      return $credentials;
  }

  public function getMeta() {
    $meta = new Meta();
    $meta->niceName = ucfirst($this->getName());
    return $meta;
  }

  public function getCallbackUrl() {
    global $base_url;
    return $base_url.'/wconsumer/callback/'.$this->name;
  }

  public static function getClass() {
    return get_called_class();
  }

  /**
   * @return AuthInterface
   */
  protected function initAuthentication() {
    return NULL;
  }

  protected function initName() {
    $name = $this->name;

    if (!isset($name)) {
      $name = str_replace('\\', '__', get_called_class());
    }

    $name = strtolower($name);

    return $name;
  }

  protected function unauthorizedRequestHandler() {
    return new UnauthorizedResponseHandler\Common();
  }

  private function checkApiAvailability($user, array $scopes) {
    if (!$this->isActive()) {
      throw new ServiceInactive(
        "{$this->getMeta()->niceName} service integration which is required ".
        "for this function to work is currently deactivated."
      );
    }

    $credentials = $this->getCredentials($user->uid);
    if (!$credentials) {
      throw new NoUserCredentials("Please {$this->connectLink()}.");
    }

    if (count(array_diff($scopes, $credentials->scopes)) > 0) {
      throw new AdditionalScopesRequired(
        "We need additional permissions with your {$this->getMeta()->niceName} account. ".
        "Please {$this->connectLink(TRUE)}."
      );
    }
  }

  private function connectLink($reConnect = FALSE) {
    $url = check_plain(url('wconsumer/auth/'.rawurlencode($this->name), array('query' => drupal_get_destination())));
    $contents = ($reConnect ? "re-connect" : "connect") . " with {$this->getMeta()->niceName}";
    $link = "<a href=\"{$url}\">{$contents}</a>";
    return $link;
  }
}