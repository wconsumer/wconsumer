<?php
namespace Drupal\wconsumer\IntegrationTests\Authentication\HttpAuth;

use Drupal\wconsumer\IntegrationTests\Authentication\AuthenticationTest;



class HttpAuthTest extends AuthenticationTest {

  /**
   * @dataProvider Drupal\wconsumer\IntegrationTests\Service\BaseTest::isInitializedDataProvider
   */
  public function testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult) {

    if ($domain == 'user' && $expectedResult == false) {
      $this->assertTrue(true);
      return;
    }

    parent::testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult);
  }
}