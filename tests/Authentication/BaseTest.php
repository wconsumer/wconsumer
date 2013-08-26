<?php
namespace Drupal\wconsumer\Tests\Rest;

use Drupal\wconsumer\Authentication\Base;
use Drupal\wconsumer\Tests\TestService;


class BaseTest extends \PHPUnit_Framework_TestCase {

  public function testConstruction() {
    $service = new TestService();
    $auth = new Base($service);
    $this->assertSame($service, $auth->getService());
  }
}