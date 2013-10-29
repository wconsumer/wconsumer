<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Service\Service as Service;



abstract class AbstractServiceTest extends DrupalTestBase {

  public function testValidateServiceCredentials() {
    $service = $this->service();

    // Both invalid
    $result = $service->validateServiceCredentials(new Credentials('invalid', 'invalid'));
    $this->assertFalse($result);

    // Valid token, invalid secret
    $result = $service->validateServiceCredentials(new Credentials($service->getServiceCredentials()->token, 'invalid_secret'));
    $this->assertFalse($result);

    // Invalid token, valid secret
    $result = $service->validateServiceCredentials(new Credentials('invalid', $service->getServiceCredentials()->secret));
    $this->assertFalse($result);

    // Both valid
    $result = $service->validateServiceCredentials($service->getServiceCredentials());
    $this->assertTrue($result);
  }

  /**
   * @return Service
   */
  protected abstract function service();
}
