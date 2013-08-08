<?php
namespace Drupal\wconsumer\IntegrationTests;

use Drupal\wconsumer\ServiceBase;



class ServiceBaseTest extends DrupalTestBase {

  public function testCredentialsGettingSetting() {
    $service = new TestService();

    // Initial state
    $this->assertNull($service->getCredentials());

    // Insert new credentials
    $service->setCredentials(array('key' => '123', 'secret' => 'abc'));
    $this->assertSame(array('key' => '123', 'secret' => 'abc'), $service->getCredentials()->credentials);

    // Update existing credentials
    $service->setCredentials(array('key' => '321', 'secret' => 'abc'));
    $this->assertSame(array('key' => '321', 'secret' => 'abc'), $service->getCredentials()->credentials);

    // Remove credentials
    $service->setCredentials(null);
    $this->assertNull($service->getCredentials()->credentials);
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
}

class TestService extends ServiceBase {}