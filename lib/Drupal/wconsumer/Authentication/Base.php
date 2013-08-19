<?php
namespace Drupal\wconsumer\Authentication;

use Drupal\wconsumer\Service\Base as ServiceBase;



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

  public static function getClass() {
    return get_called_class();
  }

  protected function session($key, $value = null) {
    $key = "wconsumer:{$this->service->getName()}:{$key}";

    if (func_num_args() > 1) {
      $_SESSION[$key] = $value;
    }
    else {
      if (!isset($_SESSION[$key])) {
        throw new \BadMethodCallException('
          Auth data not found in the current session.
          Please make sure you call auth methods in a correct order.
        ');
      }
    }

    return $_SESSION[$key];
  }
}
