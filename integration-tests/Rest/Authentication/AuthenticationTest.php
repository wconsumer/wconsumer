<?php
namespace Drupal\wconsumer\IntegrationTests\Rest\Authentication;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\IntegrationTests\TestService;
use Drupal\wconsumer\Rest\Authentication\Base;
use Drupal\wconsumer\ServiceBase;




abstract class AuthenticationTest extends DrupalTestBase {
  /**
   * @dataProvider Drupal\wconsumer\IntegrationTests\ServiceBaseTest::isInitializedDataProvider
   */
  public function testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult) {
    $auth = $this->auth(new TestService());
    $service = $auth->getService();

    $service->setServiceCredentials($serviceCredentials);
    $service->setCredentials($userCredentials);

    $this->assertSame($expectedResult, $auth->isInitialized($domain));
  }

  /**
   * @param ServiceBase $service
   * @return Base
   */
  protected function auth(ServiceBase $service = null) {
    if (!isset($service)) {
      $service = $this->service();
    }

    $authClass = str_replace('\\IntegrationTests\\', '\\', preg_replace('/Test$/', '', get_class($this)));
    $auth = new $authClass($service);
    return $auth;
  }

  protected function service() {
    return new TestService();
  }
}