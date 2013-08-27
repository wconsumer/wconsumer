<?php
namespace Drupal\wconsumer;

use Drupal\wconsumer\Service\Collection;
use Drupal\wconsumer\Service\Github;
use Drupal\wconsumer\Service\Twitter;
use Drupal\wconsumer\Service\Linkedin;
use Drupal\wconsumer\Service\Meetup;
use Drupal\wconsumer\Service\Vimeo;
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

  /**
   * @var Vimeo
   */
  public static $vimeo;

  private $services;
  private $container;
  private static $instance;



  public static function instance() {
    if (!isset(static::$instance)) {
      static::$instance = new static();
    }

    /** @var static $wconsumer */
    $wconsumer = static::$instance;

    return $wconsumer;
  }

  public function __get($property) {
    return $this->{$property};
  }

  public function session($key, $value = NULL) {
    $key = "wconsumer:{$key}";

    if (func_num_args() > 1) {
      $_SESSION[$key] = $value;
    }

    return @$_SESSION[$key];
  }

  protected function __construct() {
    $this->setupServices();
    $this->setupContainer();
  }

  private function setupServices() {
    $this->services = new Collection(array(
      'github'   => new Github(),
      'twitter'  => new Twitter(),
      'linkedin' => new Linkedin(),
      'meetup'   => new Meetup(),
      'vimeo'    => new Vimeo(),
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