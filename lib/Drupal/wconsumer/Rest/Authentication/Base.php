<?php
namespace Drupal\wconsumer\Rest\Authentication;

use Drupal\wconsumer\ServiceBase;
use Drupal\wconsumer\Exception as WconsumerException;



class Base {
  /**
   * Instance of the Service Object
   *
   * @var ServiceBase
   */
  protected $service;



  public function __construct(ServiceBase $instance) {
    $this->service = $instance;
  }

  public function getService() {
    return $this->service;
  }

  public function isInitialized($type, $user = NULL) {
    return $this->service->checkAuthentication($type, (isset($user) ? $user->uid : NULL));
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
