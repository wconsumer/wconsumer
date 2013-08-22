<?php
namespace Drupal\wconsumer;

use Drupal\wconsumer\Service\Collection;
use Drupal\wconsumer\Service\Github;
use Drupal\wconsumer\Service\Twitter;
use Drupal\wconsumer\Service\Linkedin;
use Drupal\wconsumer\Service\Meetup;
use Guzzle\Http\Client;
use Pimple;


// Instantiate wconsumer to get static veriables initialized
Wconsumer::instance();


/**
 * @property-read Collection $services
 * @property-read Pimple $container
 */
class Wconsumer {
  /**
   * @var Github
   */
  public static $github;

  /**
   * @var Twitter
   */
  public static $twitter;

  /**
   * @var Linkedin
   */
  public static $linkedin;

  /**
   * @var Meetup
   */
  public static $meetup;


  private $services;
  private $container;
  private static $instance;



  public static function instance() {
    if (!isset(static::$instance)) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  protected function __construct() {
    $this->setupServices();
    $this->setupContainer();
  }

  public function __get($property) {
    return $this->{$property};
  }

  private function setupServices() {
    $this->services = new Collection(array(
      'github'   => new Github(),
      'twitter'  => new Twitter(),
      'linkedin' => new Linkedin(),
      'meetup'   => new Meetup(),
    ));

    $class = new \ReflectionClass(get_class($this));
    foreach ($this->services as $name => $service) {
      $class->setStaticPropertyValue($name, $service);
    }
  }

  private function setupContainer() {
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
}