<?php
namespace Drupal\wconsumer\Tests\Integration\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Tests\Integration\DrupalTestBase;
use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Drupal\wconsumer\Service\Service;



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

  public function testApiWithInvalidUserCredentials() {
    $url = $this->currentUserInfoApiEndpoint();

    $service = $this->service();
    $service->setCredentials(new Credentials('unknown', 'invalid'), 50);
    $api = $service->api(50);

    // 1. Expect NoUserCredentials exception
    $this->expectException(NoUserCredentials::getClass(), function() use($api, $url) {
      $api->get($url)->send();
    });

    // 2. Expect user credentials reset
    $this->assertNull($service->getCredentials(50));
  }

  /**
   * @return string
   */
  protected abstract function currentUserInfoApiEndpoint();

  protected function service() {
    $serviceClass = null; {
      $matches = array();
      preg_match('/(\w+?)Test$/', get_class($this), $matches);
      $serviceClass = $matches[1];
      $serviceClass = substr(Service::getClass(), 0, strrpos(Service::getClass(), '\\')).'\\'.$serviceClass;
    }

    /** @var Service $service */
    $service = new $serviceClass();

    $service->setEnabled(true);
    $service->setServiceCredentials(Credentials::fromArray($this->keys->get($service->getName(), 'app')));

    if ($userCredentials = $this->keys->tryGet($service->getName(), 'user')) {
      $service->setCredentials(Credentials::fromArray($userCredentials));
    }

    return $service;
  }

  private function expectException($exceptionClass, $fromCallback) {
    $exception = null;
    try {
      $fromCallback();
    }
    catch (\Exception $e) {
      $exception = $e;
    }

    $this->assertNotNull($exception);
    $this->assertInstanceOf($exceptionClass, $exception);
  }
}
