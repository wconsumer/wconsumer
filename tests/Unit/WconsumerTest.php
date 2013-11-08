<?php
namespace Drupal\wconsumer\Tests\Unit;

use Drupal\wconsumer\Service\Github;
use Drupal\wconsumer\Service\Linkedin;
use Drupal\wconsumer\Service\Twitter;
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

  public function testServicesShorthands() {
    $this->assertInstanceOf(Github::getClass(), Wconsumer::$github);
    $this->assertInstanceOf(Twitter::getClass(), Wconsumer::$twitter);
    $this->assertInstanceOf(Linkedin::getClass(), Wconsumer::$linkedin);
  }

  public function testSession() {
    $wconsumer = $this->wconsumer();
    $this->assertNull($wconsumer->session('client', 'key'));
    $this->assertSame('123', $wconsumer->session('client', 'key', '123'));
    $this->assertSame('123', $wconsumer->session('client', 'key'));
    $this->assertNull($wconsumer->session('another client', 'key'));
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