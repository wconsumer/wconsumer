<?php
namespace Drupal\wconsumer\Service;

use Drupal\wconsumer\Queue;
use Drupal\wconsumer\Authentication\AuthInterface;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Exception\AdditionalScopesRequired;
use Drupal\wconsumer\Service\Exception\NotLoggedInUser;
use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Drupal\wconsumer\Service\Exception\ServiceInactive;
use Drupal\wconsumer\Util\Serialize;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;


/**
 * Base Class for Services
 *
 * @package wconsumer
 * @subpackage services
 */
abstract class Base {
  /**
   * Define a way to specify the internal name of the service
   * Optional -- will default to the class name
   *
   * @var string
   */
  protected $name;

  /**
   * Options Class
   *
   * @var object|void
   */
  public $options  = NULL;

  /**
   * @var AuthInterface
   */
  public $authentication = NULL;

  /**
   * Base API url
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * Services table
   *
   * @var string
   */
  private $servicesTable = 'wc_service';

  /**
   * Services' users' table
   *
   * @var string
   */
  private $usersTable = 'wc_user';



  public function __construct() {
    $this->name = $this->initName();
    $this->authentication = $this->initAuthentication();
  }

  public function api($userId = NULL, array $scopes = array()) {
    $user = new \stdClass();
    $user->uid = (isset($userId) ? $userId : $GLOBALS['user']->uid);

    if (empty($user->uid)) {
      throw new NotLoggedInUser("User is not logged in");
    }

    if (!$this->getServiceCredentials()) {
      throw new ServiceInactive("'{$this->name}' service is currently inactive");
    }

    $credentials = $this->getCredentials($userId);
    if (!$credentials) {
      throw new NoUserCredentials("User not yet authorized access to his '{$this->name}' service profile");
    }

    if (count(array_diff($scopes, $credentials->scopes)) > 0) {
      throw new AdditionalScopesRequired("Additional scopes/permissions required. Need to re-authorize user with '{$this->name}' service.");
    }

    /** @var Client $client */
    $client = Wconsumer::instance()->container['httpClient'];
    $client->setBaseUrl($this->apiUrl);
    $this->authentication->signRequest($client, $user);
    return $client;
  }

  public function isActive() {
    return $this->checkAuthentication('system');
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

  public function setCredentials(Credentials $credentials = NULL, $user_id = NULL) {
    if ($user_id == NULL) {
      global $user;
      $user_id = $user->uid;
    }

    if (!$user_id) {
      throw new \InvalidArgumentException("Can't set credentials for guest visitor");
    }

    db_merge($this->usersTable)
      ->key(array(
        'service' => $this->name,
        'user_id' => $user_id,
      ))
      ->fields(array(
        'service' => $this->name,
        'user_id' => $user_id,
        'credentials' => Serialize::serialize($credentials),
      ))
    ->execute();
  }

  public function getCredentials($user_id = NULL) {
    if (!isset($user_id)) {
      global $user;
      $user_id = $user->uid;
    }

    $credentials = null;

    $serializedCredentials =
      db_select($this->usersTable)
        ->fields($this->usersTable, array('credentials'))
        ->condition('service', $this->name)
        ->condition('user_id', $user_id)
      ->execute()
      ->fetchField();

    if ($serializedCredentials) {
      $credentials = Serialize::unserialize($serializedCredentials, Credentials::getClass());
    }

    return $credentials;
  }

  public function requireCredentials($userId = NULL) {
      $credentials = $this->getCredentials($userId);

      if (!isset($credentials)) {
          throw new \BadMethodCallException("Please connect your account with service before using it");
      }

      return $credentials;
  }

  /**
   * Can the current user access this service
   *
   * @todo Implement Permissions
   * @return bool
   */
  public function canAccess() {
    return TRUE;
  }

  /**
   * See if they are authenticated on a system/user basis
   *
   * @param string $basis On what basis are they being check for (system/user)
   * @param int|null $user_id The user ID (default to NULL for the current user)
   * @return bool
   */
  public function checkAuthentication($basis = 'user', $user_id = NULL) {
    switch ($basis) {
      case 'user':    return ($this->getCredentials($user_id) !== null);
      case 'system':  return ($this->getServiceCredentials() !== null);
      default:        return FALSE;
    }
  }

  /**
   * Retrieve a callback URL
   *
   * @return string
   */
  public function callback() {
    global $base_url;
    return $base_url.'/wconsumer/callback/'.$this->name;
  }

  /**
   * Get Service Name
   *
   * @return string
   */
  public function getName() {
    return $this->name;
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
}