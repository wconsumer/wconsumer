<?php
namespace Drupal\wconsumer\Authentication;

use Drupal\wconsumer\Service\Base as ServiceBase;
use Drupal\wconsumer\Util\Serialize;
use Drupal\wconsumer\Wconsumer;


class Base {
  protected $service;



  public function __construct(ServiceBase $instance) {
    $this->service = $instance;
  }

  public function getService() {
    return $this->service;
  }

  public static function getClass() {
    return get_called_class();
  }

  protected function session($key, $value = NULL) {
    $args = func_get_args();
    $args[0] = "{$this->service->getName()}:{$key}";

    $result = call_user_func_array(array(Wconsumer::instance(), 'session'), $args);

    if (func_num_args() < 2  && !isset($result)) {
      throw new \BadMethodCallException('
        Auth data not found in the current session.
        Please make sure you call auth methods in a correct order.
      ');
    }

    return $result;
  }
}
