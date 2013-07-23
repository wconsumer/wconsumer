<?php
namespace Drupal\wconsumer\Rest;

abstract class Authentication {
  /**
   * Instance of the Service Object
   * 
   * @var object
   */
  protected $_instance;
  
  /**
   * Setup the Service Instance
   *
   * @param object
   */
  public function __construct(&$instance)
  {
    $this->_instance = $instance;
  }
}
