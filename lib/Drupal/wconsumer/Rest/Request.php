<?php
namespace Drupal\wconsumer\Rest;
use Drupal\wconsumer\Common\RequestInterface;

/**
 * REST Request Class
 *
 * @package wconsumer
 * @subpackage request
 */
class Request implements RequestInterface
{
  /**
   * API Base URL
   *
   * @var string
   */
  public $apiURL;

  private static $instance = NULL;

  /**
   * Call this method to get a instance
   *
   * @return object
   * @access public
   */
  public static function Instance()
  {
    if (static::$instance !== NULL)
      static::$instance = new Request();

    return static::$instance;
  }
}
