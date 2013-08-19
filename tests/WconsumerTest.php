<?php
namespace Drupal\wconsumer\Tests;

use Drupal\wconsumer\Service\Github;
use Drupal\wconsumer\Wconsumer;



class WconsumerTest extends \PHPUnit_Framework_TestCase {
  public function testHttpClientDefined() {
    $client = $this->wconsumer()->container['httpClient'];

    $this->assertNotNull($client);
    $this->assertInstanceOf('\Guzzle\Http\Client', $client);
  }

  public function testServicesLoading() {
    $services = $this->wconsumer()->services;
    $this->assertInstanceOf(Github::getClass(), $services->github);
  }

  public function testInstance() {
    $wconsumer = Wconsumer::instance();
    $this->assertInstanceOf('\Drupal\wconsumer\Wconsumer', $wconsumer);
  }

  private function wconsumer() {
    return new WconsumerTestChild();
  }
}

class WconsumerTestChild extends Wconsumer {
  public function __construct() {
    return call_user_func_array(array('parent', '__construct'), func_get_args());
  }
}