<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Service\Collection;



class CollectionTest extends \PHPUnit_Framework_TestCase {
  public function testConstruction() {
    $github = new \stdClass();
    $collection = new Collection(array('github' => $github));
    $this->assertSame($github, $collection->github);
  }

  public function testGetter() {
    $github = new \stdClass();
    $twitter = new \stdClass();

    $collection = new Collection(array(
      'github' => $github,
      'twitter' => $twitter,
    ));

    $this->assertSame($github, $collection->github);
    $this->assertSame($twitter, $collection->twitter);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testGetterFailsOnNotExistingService() {
    $collection = new Collection(array('vimeo' => new \stdClass()));
    $collection->{'unknown'};
  }

  public function testGet() {
    $vimeo = new \stdClass();
    $github = new \stdClass();

    $collection = new Collection(array(
      'vimeo'  => $vimeo,
      'github' => $github,
    ));

    $this->assertSame($github, $collection->get('github'));
    $this->assertSame($vimeo, $collection->get('vimeo'));
    $this->assertNull($collection->get('unknown'));
  }
}