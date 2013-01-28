<?php
namespace Drupal\wconsumer;

class Service {
  /**
   * Return Active Services
   *
   * Static Method
   *
   * @param array Include your own services, optional
   * @return array
   * @access public
   */
  public static function services($services = array())
  {
    return module_invoke_all('wconsumer_config', $services);
  }
}
