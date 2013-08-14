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

    $services = $this->wconsumer()->services;

    $this->assertSame($fakeService, $services['fake test service']);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testFailsOnNotExistingService() {
    $this->mockModuleInvokeAllDrupalFunction();
    $this->wconsumer()->services['no'];
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testGetServiceFailsOnNotExistingService() {
    $this->mockModuleInvokeAllDrupalFunction();
    $this->wconsumer()->getService('no', false);
  }

  public function testGetServicesDoesNotFailOnNotExistingServiceIfAsked() {
    $this->mockModuleInvokeAllDrupalFunction();
    $result = $this->wconsumer()->getService('no', true);
    $this->assertNull($result);
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