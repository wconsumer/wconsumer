<?php
namespace Drupal\wconsumer\IntegrationTests;

use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\IntegrationTests\TestService;


class ServiceBaseTest extends DrupalTestBase {

  public function testCredentialsGettingSetting() {
    $service = new TestService();

    // Initial state
    $this->assertNull($service->getCredentials());

    // Insert new credentials
    $credentials = new Credentials('123', 'abc');
    $service->setCredentials($credentials);
    $this->assertEquals($credentials, $service->getCredentials());

    // Update existing credentials
    $credentials = new Credentials('321', 'abc');
    $service->setCredentials($credentials);
    $this->assertEquals($credentials, $service->getCredentials());

    // Remove credentials
    $service->setCredentials(null);
    $this->assertNull($service->getCredentials());
  }

  public function testGetCredentialsDoesntFailIfServiceCredentialsAreNotSet() {
    $service = new TestService();

    $exception = null;
    try {
      $service->getCredentials();
    }
    catch (\Exception $e) {
      $exception = $e;
    }

    $this->assertNull($exception);
  }

  public function testServiceCredentialsGettingSetting() {
    $service = new TestService();

    // Initial state
    $this->assertNull($service->getServiceCredentials());

    // Insert new credentials
    $credentials = new Credentials('123', 'abc');
    $service->setServiceCredentials($credentials);
    $this->assertEquals($credentials, $service->getServiceCredentials());

    // Update existing credentials
    $credentials = new Credentials('321', 'abc');
    $service->setServiceCredentials($credentials);
    $this->assertEquals($credentials, $service->getServiceCredentials());

    // Remove credentials
    $service->setServiceCredentials(null);
    $this->assertNull($service->getServiceCredentials());
  }
}