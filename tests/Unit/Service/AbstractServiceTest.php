<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

use Drupal\wconsumer\Service\Service;



abstract class AbstractServiceTest extends \PHPUnit_Framework_TestCase {

  public function testMeta() {
    $service = $this->service();
    $meta = $service->getMeta();

    $this->assertNotEmpty($meta->niceName);
    $this->assertNotEmpty($meta->registerAppUrl);
    $this->assertNotEmpty($meta->consumerKeyLabel);
    $this->assertNotEmpty($meta->consumerSecretLabel);
  }

  /**
   * @return Service
   */
  protected abstract function service();
}
