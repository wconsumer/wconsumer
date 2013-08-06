<?php
namespace Drupal\wconsumer\Rest;

use Drupal\wconsumer\ServiceBase;



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
}
