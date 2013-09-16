<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Service\Base;



abstract class AbstractServiceTest extends \PHPUnit_Framework_TestCase {

  public function testMeta() {
    $service = $this->service();
    $meta = $service->getMeta();
    $this->assertNotEmpty($meta->registerAppUrl);
    $this->assertNotEmpty($meta->consumerKeyLabel);
    $this->assertNotEmpty($meta->consumerSecretLabel);
  }

  /**
   * @return Base
   */
  protected abstract function service();
}
