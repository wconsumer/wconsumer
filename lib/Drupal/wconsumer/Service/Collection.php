<?php
namespace Drupal\wconsumer\Service;

use Pimple;
use Drupal\wconsumer\Service\Service;


/**
 * @property-read Github    $github
 * @property-read Twitter   $twitter
 * @property-read Linkedin  $linkedin
 * @property-read Meetup    $meetup
 * @property-read Vimeo     $vimeo
 * @property-read Google    $google
 * @property-read Facebook  $facebook
 * @property-read Dropbox   $dropbox
 * @property-read Flickr    $flickr
 */
class Collection implements \IteratorAggregate, \Countable {
  private $services;


  public function __construct(array $services) {
    $this->services = new Pimple($services);
  }

  /**
   * @param string $service
   * @return Service
   */
  public function get($service) {
    return (isset($this->services[$service]) ? $this->services[$service] : null);
  }

  /**
   * @param string $service
   * @return Service
   */
  public function __get($service) {
    return $this->services[$service];
  }

  public function getIterator() {
    $servicesArray = array();
    foreach ($this->services->keys() as $serviceName) {
      $servicesArray[$serviceName] = $this->services[$serviceName];
    }

    return new \ArrayIterator($servicesArray);
  }

  public function count() {
    return count($this->services->keys());
  }
}