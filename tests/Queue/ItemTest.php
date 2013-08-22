<?php
  namespace Drupal\wconsumer\Tests\Queue;

  use Drupal\wconsumer\Queue\Item;


  class ItemTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \Drupal\wconsumer\Queue\QueueException
     */
    public function testItemConstructionFailsIfUnknownFieldPassed()
    {
      new Item(array('some_unknown_field' => 'hello'));
    }

    public function testResponseFieldUnserialized()
    {
      $expectedResponse = array(1, 2, 3, 4 => 5, 7 => 'hello');

      $item = new Item(array('response' => serialize($expectedResponse)));
      $actualResponse = $item->response;
      
      $this->assertSame($expectedResponse, $actualResponse);
    }
  }
?>
