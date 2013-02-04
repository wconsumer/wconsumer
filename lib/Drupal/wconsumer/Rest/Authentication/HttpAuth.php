<?php
namespace Drupal\wconsumer\Rest\Authentication;

/**
 * HTTP Authentication
 *
 * Used for services that require a specific HTTP username and/or password
 *
 * @package wconsumer
 * @subpackage request
 */
class HttpAuth
{
  /**
   * Instance of the Service Object
   * 
   * @var object
   */
  private $_instance;

  /**
   * Setup the Service Instance
   * 
   * @param object
   */
  public function __construct($instance)
  {
    $this->_instance = $instance;
  }
}
