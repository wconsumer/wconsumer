<?php
use Drupal\wconsumer\Service;

class ServiceTest extends PHPUnit_Framework_TestCase {
  /**
   * Trying to get an object for an unknown service
   *
   * @expectedException Drupal\wconsumer\Exception
   */
  public function testUnregisteredServiceException()
  {
    Service::getObject('unknown service');
  }

  /**
   * Test No Services Registered
   */
  public function testNoRegisteredServices()
  {
    $this->assertEquals(count(Service::services()), 0);
  }

  /**
   * Test Retrieving a Service Object
   */
  public function testGlobalInstance()
  {
    $object = new Foo();
    $service = Service::getObject('test service', array(
      'test service' => $object
    ));

    $this->assertSame($object, $service);

    // Try again to ensure it's the same object
    $this->assertSame($object, $service);
  }
}

/**
 * Dummy Class
 *
 * @ignore
 */
class Foo { }
