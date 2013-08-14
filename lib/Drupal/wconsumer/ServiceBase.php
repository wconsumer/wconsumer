<?php
namespace Drupal\wconsumer;
use Drupal\wconsumer\Rest\Authentication\Credentials;

/**
 * Base Class for Services
 *
 * @package wconsumer
 * @subpackage services
 */
abstract class ServiceBase {
  /**
   * Define a way to specify the internal name of the service
   * Optional -- will default to the class name
   *
   * @var string
   * @access protected
   */
  protected $name;

  /**
   * Service Registry Table
   * @var string
   */
  private $serviceRegistry = 'wc_service_registry';

  /**
   * Service Credential Table
   * @var string
   */
  private $serviceCred = 'wc_service_cred';

  /**
   * Options Class
   *
   * @var object|void
   */
  public $options  = NULL;



  public function __construct() {
    if (!isset($this->name)) {
      $this->name = str_replace('\\', '__', get_called_class());
    }

    $this->name = strtolower($this->name);
  }

  public function setServiceCredentials(Credentials $credentials = null) {
    db_merge($this->serviceRegistry)
      ->key(array('service' => $this->name))
      ->fields(array(
        'service'     => $this->name,
        'credentials' => (isset($credentials) ? $credentials->serialize() : NULL),
      ))
    ->execute();
  }

  public function getServiceCredentials() {
    $credentials = null;

    $serializedCredentials = db_select($this->serviceRegistry)
      ->fields($this->serviceRegistry, array('credentials'))
      ->condition('service', $this->name)
    ->execute()
    ->fetchField();

    if ($serializedCredentials) {
      $credentials = Credentials::unserialize($serializedCredentials);
    }

    return $credentials;
  }

  public function setCredentials(Credentials $credentials = null, $user_id = NULL) {
    if ($user_id == NULL) {
      global $user;
      $user_id = $user->uid;
    }

    db_merge($this->serviceCred)
      ->key(array(
        'service' => $this->name,
        'user_id' => $user_id,
      ))
      ->fields(array(
        'service' => $this->name,
        'user_id' => $user_id,
        'credentials' => (isset($credentials) ? $credentials->serialize() : NULL),
      ))
    ->execute();
  }

  /**
   * Retrieve the Service Credential Object
   *
   * Checks the database to see if the credential row exists.
   * If not, returns NULL.
   *
   * @param int|null
   * @return Credentials|null
   * @throws \Drupal\wconsumer\Exception
   */
  public function getCredentials($user_id = NULL) {
    if (!isset($user_id)) {
      global $user;
      $user_id = $user->uid;
    }

    $credentials = null;

    $serializedCredentials =
      db_select($this->serviceCred)
        ->fields($this->serviceCred, array('credentials'))
        ->condition('service', $this->name)
        ->condition('user_id', $user_id)
      ->execute()
      ->fetchField();

    if ($serializedCredentials) {
      $credentials = Credentials::unserialize($serializedCredentials);
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

  /**
   * Instanstiate a new Item Object
   *
   * @return object Item Object
   */
  public function newQueueItem() {
    $i = new Queue\Item();
    $i->service = $this->getName();

    return $i;
  }
}