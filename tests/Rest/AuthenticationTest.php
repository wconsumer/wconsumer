<?php
namespace Drupal\wconsumer\Tests\Rest;

use Drupal\wconsumer\Rest\Authentication;
use Drupal\wconsumer\Tests\TestService;


class AuthentiationTest extends \PHPUnit_Framework_TestCase {

  public function testConstruction() {
    $service = new TestService();
    $auth = new Authentication($service);
    $this->assertSame($service, $auth->getService());
  }
}