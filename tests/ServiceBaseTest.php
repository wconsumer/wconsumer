<?php
use Drupal\wconsumer\Service;
use Drupal\wconsumer\Exception;

class ServiceBaseTest extends PHPUnit_Framework_TestCase {
  public function testServiceName() {
    $object = new FooService();
    $this->assertEquals('fooservice', $object->getName());
  }

  public function testSpecificServiceName() {
    $object = new FooServiceWithName();
    $this->assertEquals('specialservice', $object->getName());
  }

  public function testNewQueueItem() {
    $object = new FooService();
    $queueItem = $object->newQueueItem();

    $this->assertEquals('Drupal\wconsumer\Queue\Item', get_class($queueItem));

    // Test for the columns in the request queue
    $this->assertEquals(-1, $queueItem->request_id);
    $this->assertNotEmpty($queueItem->service);
    $this->assertEquals(-1, $queueItem->request);
    $this->assertEquals(0, $queueItem->time);
    $this->assertEmpty($queueItem->response);
    $this->assertEquals('pending', $queueItem->status);

    $this->assertEquals(0, $queueItem->moderate);
    $this->assertEquals(0, $queueItem->approver_uid);
    $this->assertEquals(0, $queueItem->created_date);

  }
}

/**
 * @ignore
 */
class FooService extends \Drupal\wconsumer\ServiceBase {

}

/**
 * @ignore
 */
class FooServiceWithName extends \Drupal\wconsumer\ServiceBase {
  protected $_service = 'specialservice';
}
