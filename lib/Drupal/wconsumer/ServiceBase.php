<?php
namespace Drupal\wconsumer;

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
  protected $_service;

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

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct()
  {
    // Identity the name of the service
    if (! isset($this->_service))
      $this->_service = strtolower(get_called_class());
    else
      $this->_service = strtolower($this->_service);
  }

  /**
   * Set the Service Registry Credentials
   * 
   * @param mixed Will be serialized regardless
   * @return int The Service ID for inserting or the number of rows affected for update
   */
  public function setRegistry($config = array())
  {
    $object = $this->getRegistry();

    if (! $object) :
      return db_insert($this->serviceRegistry)
        ->fields(array(
          'service' => $this->_service,
          'credentials' => serialize($config)
        ))
        ->execute();
    else :
      return db_update($this->serviceRegistry)
        ->fields(array(
          'credentials' => serialize($config)
        ))
        ->condition('service_id', $object->id)
        ->execute();
    endif;
  }

  /**
   * Retrieve the Service Registry Object
   *
   * Checks the database to see if the registry row exists.
   * If not, returns FALSE.
   * 
   * @return object|bool
   */
  public function getRegistry()
  {
    $data = db_select($this->serviceRegistry)
      ->fields($this->serviceRegistry)
      ->condition('service', $this->_service)
      ->execute()->fetchObject();

    $this->unserializeCredentials($data);
    return $data;
  }

  /**
   * Set Credentials for the Service
   *
   * @param mixed The user's credentials (will be serialized)
   * @param int The optional user id
   * @return object|bool
   */
  public function setCredentials($credentials, $user_id = NULL)
  {
    if ($user_id == NULL) :
      global $user;
      $user_id = $user->id;
    endif;

    if ($this->getCredentials($user_id)) :
      // Update
      return db_update($this->serviceCred)
        ->fields(array(
          'credentials' => serialize($credentials)
        ))
        ->condition('service_id', $service->service_id)
        ->condition('user_id', $user_id)
        ->execute();
    else :
      // Insert
      return db_insert($this->serviceCred)
        ->fields(array(
          'service_id' => $service->service_id,
          'user_id' => $user_id,
          'credentials' => serialize($credentials)
        ))
        ->execute();
    endif;
  }

  /**
   * Retrieve the Service Credential Object
   *
   * Checks the database to see if the credential row exists.
   * If not, returns NULL.
   *
   * @param int
   * @return object|null
   * @throws Drupal/wconsumer/Exception
   */
  public function getCredentials($user_id = NULL)
  {
    // We need to retrieve the service ID first
    $object = $this->retrieveRegistry();
    if ($object == NULL)
      throw new Exception('Service registry not initialized: '.$this->_service);

    if ($user_id == NULL) :
      global $user;
      $user_id = $user->id;
    endif;

    // Lift off!
    $data = db_select($this->serviceCred)
      ->fields($this->serviceCred)
      ->condition('service_id', $object->service_id)
      ->condition('user_id', $user_id)
      ->execute()->fetchObject();

    $this->unserializeCredentials($data);
    return $data;
  }

  /**
   * Internal Function to unserialize the credentials for us
   * Do not pass variables by reference
   * 
   * @param object|array
   * @return mixed
   */
  private function unserializeCredentials(&$data)
  {
    if (is_object($data) AND isset($data->credentials) AND $data->credentials !== '')
      $data->credentials = unserialize($data->credentials);
    elseif(is_array($data) AND isset($data['credentials']) AND $data['credentials'] !== '')
      $data['credentials'] = unserialize($data['credentials']);

    return $data;
  }
}
