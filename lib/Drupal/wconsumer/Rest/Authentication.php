<?php
namespace Drupal\wconsumer\Rest;

use Drupal\wconsumer\ServiceBase;
use Drupal\wconsumer\Exception as WconsumerException;



abstract class Authentication {
  /**
   * Instance of the Service Object
   *
   * @var ServiceBase
   */
  protected $_instance;



  public function __construct(ServiceBase $instance) {
    $this->_instance = $instance;
  }

  public function getService() {
    return $this->_instance;
  }

  public function is_initialized($type) {
    switch ($type) {
      case 'user':    return ($this->_instance->getCredentials() !== null);
      case 'system':  return ($this->_instance->getServiceCredentials() !== null);
      default:        return FALSE;
    }
  }

  protected function requireKeys(array $keys, array $data, $errorMessage) {
    $requiredData = array_filter(
      array_intersect_key(
        (array)$data,
        array_flip($keys)
      )
    );

    if (count($requiredData) < count($keys)) {
      throw new WconsumerException($errorMessage);
    }

    return $requiredData;
  }
}
