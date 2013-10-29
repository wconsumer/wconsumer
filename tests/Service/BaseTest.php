<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Service\Service;



class BaseTest extends \PHPUnit_Framework_TestCase {

  public function testServiceName() {
    $object = new FooService();
    $this->assertEquals('drupal__wconsumer__tests__service__fooservice', $object->getName());
  }

  public function testSpecificServiceName() {
    $object = new FooServiceWithName();
    $this->assertEquals('specialservice', $object->getName());
  }

  public function testCallbackUrl() {
    $service = new FooService();
    $this->assertNotEmpty($service->getCallbackUrl());
  }

  public function testMeta() {
    $service = new FooServiceWithName();
    $meta = $service->getMeta();
    $this->assertSame('Specialservice', $meta->niceName);
    $this->assertNotEmpty($meta->consumerKeyLabel);
    $this->assertNotEmpty($meta->consumerSecretLabel);
    $this->assertNull($meta->registerAppUrl);
  }
}

/**
 * @ignore
 */
class FooService extends Service {
}

/**
 * @ignore
 */
class FooServiceWithName extends Service {
  protected $name = 'specialservice';
}