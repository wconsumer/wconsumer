<?php
namespace Drupal\wconsumer\Tests;

use Drupal\wconsumer\Wconsumer;



class WconsumerTest extends \PHPUnit_Framework_TestCase {
  public function testHttpClientDefined() {
    $client = $this->wconsumer()->container['httpClient'];

    $this->assertNotNull($client);
    $this->assertInstanceOf('\Guzzle\Http\Client', $client);
  }

  public function testServicesLoading() {
    $fakeService = new \stdClass();
    $this->mockModuleInvokeAllDrupalFunction($fakeService);

    $this->assertSame($fakeService, $this->wconsumer()->services->get('fake test service'));
  }

  public function testInstance() {
    $wconsumer = Wconsumer::instance();
    $this->assertInstanceOf('\Drupal\wconsumer\Wconsumer', $wconsumer);
  }

  private function wconsumer() {
    return new WconsumerTestChild();
  }

  private function mockModuleInvokeAllDrupalFunction($fakeService = null) {
    if (!isset($fakeService)) {
      $fakeService = new \stdClass();
    }

    $php = \PHPUnit_Extension_FunctionMocker::start($this, '\Drupal\wconsumer')
      ->mockFunction('module_invoke_all')
      ->getMock();

    $php
      ->expects($this->once())
      ->method('module_invoke_all')
      ->will($this->returnValue(array('fake test service' => $fakeService)));
  }
}

class WconsumerTestChild extends Wconsumer {
  public function __construct() {
    return call_user_func_array(array('parent', '__construct'), func_get_args());
  }
}