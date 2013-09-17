<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Service\Base as Service;



abstract class AbstractServiceTest extends DrupalTestBase {

  public function testValidateServiceCredentials() {
    $service = $this->service();

    $result = $service->validateServiceCredentials(new Credentials('invalid', 'invalid'));
    $this->assertFalse($result);

    $result = $service->validateServiceCredentials($service->getServiceCredentials());
    $this->assertTrue($result);
  }

  /**
   * @return Service
   */
  protected abstract function service();
}
