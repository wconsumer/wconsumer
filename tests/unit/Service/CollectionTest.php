<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

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

  public function testIteration() {
    $vimeo = new \stdClass();
    $github = new \stdClass();

    $collection = new Collection(array(
      'vimeo'  => $vimeo,
      'github' => $github,
    ));

    // First check if it supports Traversable interface required for foreach
    $this->assertInstanceOf('\Traversable', $collection);

    // Then perform a real foreach traversal
    $services = array();
    foreach ($collection as $service) {
      $services[] = $service;
    }
    $this->assertSame(array($vimeo, $github), $services);
  }

  public function testCount() {
    $this->assertCount(0, new Collection(array()));
    $this->assertCount(1, new Collection(array(1)));
    $this->assertCount(2, new Collection(array(1, 2)));
  }
}