<?php
namespace Drupal\wconsumer\Tests\Unit\Authentication;

use Drupal\wconsumer\Authentication\Authentication;
use Drupal\wconsumer\Tests\Unit\TestService;



class BaseTest extends \PHPUnit_Framework_TestCase {

  public function testConstruction() {
    $service = new TestService();
    $auth = new Authentication($service);
    $this->assertSame($service, $auth->getService());
  }
}