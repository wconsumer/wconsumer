<?php
namespace Drupal\wconsumer\IntegrationTests\Rest\Authentication;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\IntegrationTests\TestService;
use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\Rest\Authentication;
use Drupal\wconsumer\ServiceBase;



abstract class AuthenticationTest extends DrupalTestBase {
  /**
   * @dataProvider isInitializedDataProvider
   */
  public function testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult) {
    $auth = $this->auth(new TestService());
    $service = $auth->getService();

    $service->setServiceCredentials($serviceCredentials);
    $service->setCredentials($userCredentials);

    $this->assertSame($expectedResult, $auth->is_initialized($domain));
  }

  public static function isInitializedDataProvider() {
    $credentials = new Credentials('123', 'abc');

    return array(
      array(NULL, NULL, 'user', FALSE),
      array(NULL, NULL, 'system', FALSE),
      array(NULL, NULL, 'unknown', FALSE),
      array($credentials, NULL, 'user', FALSE),
      array($credentials, NULL, 'system', TRUE),
      array($credentials, NULL, 'unknown', FALSE),
      array(NULL, $credentials, 'user', TRUE),
      array(NULL, $credentials, 'system', FALSE),
      array(NULL, $credentials, 'unknown', FALSE),
      array($credentials, $credentials, 'user', TRUE),
      array($credentials, $credentials, 'system', TRUE),
      array($credentials, $credentials, 'unknown', FALSE),
    );
  }

  /**
   * @param ServiceBase $service
   * @return Authentication
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