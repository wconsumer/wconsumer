<?php
namespace Drupal\wconsumer;

use Drupal\wconsumer\Service\Collection;
use Drupal\wconsumer\Service\Github;
use Drupal\wconsumer\Service\Linkedin;
use Drupal\wconsumer\Service\Twitter;
use Guzzle\Http\Client;
use Pimple;



/**
 * @property-read Collection $services
 * @property-read Pimple $container
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
      $services = array(); {
        $services['github'] = new Github();
        $services['twitter'] = new Twitter();
        $services['linkedin'] = new Linkedin();
      }

      $this->services = new Collection($services);
    }

    return $this->{$property};
  }
}