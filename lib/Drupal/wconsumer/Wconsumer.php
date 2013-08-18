<?php
namespace Drupal\wconsumer;

use Drupal\wconsumer\Service\Base;
use Drupal\wconsumer\Services\Github;
use Guzzle\Http\Client;
use Pimple;


/**
 * @property-read Base[] $services
 * @property-read Pimple $container
 * @property-read Github $github
 */
class Wconsumer {
  private $services;
  private $container;
  private static $instance;


  /**
   * @return static
   */
  public static function instance() {
    if (!isset(static::$instance)) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  public static function api($service, $method, $url, array $parameters = array()) {

  }

  /**
   * @param string $name
   * @param bool $silent
   * @return Base|null
   */
  public function getService($name, $silent = true) {
    $services = $this->__get('services');

    if ($silent && !isset($services[$name])) {
      return null;
    }

    return $services[$name];
  }

  protected function __construct() {
    $this->services = null;

    $this->container = new Pimple();
    $this->container['httpClient'] = function() {
      $client = new Client(null, array(
        'timeout' => 30,
        'verify'  => true,
      ));

      $client->setUserAgent('Web Consumer Manager', true);

      return $client;
    };
  }

  public function __get($property) {
    if ($property == 'services' && !isset($this->services)) {
      $services = module_invoke_all('wconsumer_config');
      $this->services = new Pimple($services);
    }

    return $this->{$property};
  }
}