<?php
namespace Drupal\wconsumer\Service;

use Pimple;
use Drupal\wconsumer\Service\Base as Service;



/**
 * @property-read Service[] $services
 *
 * @property-read Github  $github
 * @property-read Twitter $twitter
 * @property-read Vimeo   $vimeo
 */
class Collection
{
  private $services;


  public function __construct(array $services) {
    $this->services = new Pimple($services);
  }

  public function get($service) {
    return (isset($this->services[$service]) ? $this->services[$service] : null);
  }

  public function __get($service) {
    return $this->services[$service];
  }
}