<?php
namespace Drupal\wconsumer;

use Drupal\wconsumer\Exception as ServiceException;
use Guzzle\Common\Collection;
use Guzzle\Http\Client;

/**
 * Service Manager Class
 *
 * Controller over the general function of the services
 * Provides an interface to connect with the services and retains
 * a global object about each service to prevent duplication.
 * A different take on a singleton.
 *
 * @package wconsumer
 * @subpackage services
 */
class Service {
  /**
   * Internal Service Registry
   *
   * @var array
   * @access private
   */
  private static $_services = NULL;

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
    if (self::$_services == NULL)
      self::$_services = module_invoke_all('wconsumer_config', $services);

    return self::$_services;
  }

  /**
   * Retrieve the Object for a Service
   *
   * @param string Service
   * @return object
   * @throws \Exception
   */
  public static function getObject($service, $services = array())
  {
    $services = self::services($services);
    if (!isset($services[$service]))
      throw new ServiceException('Unknown service: '.$service);

    return $services[$service];
  }

  public static function createHttpClient($baseUrl = null, array $config = array()) {
    $config = Collection::fromConfig($config, array(
      'timeout' => 30,
      'verify'  => true,
    ));

    $client = new Client($baseUrl, $config);

    $client->setUserAgent('Web Consumer Manager', true);

    return $client;
  }
}
