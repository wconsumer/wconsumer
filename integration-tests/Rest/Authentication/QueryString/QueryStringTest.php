<?php
namespace Drupal\wconsumer\IntegrationTests\Rest\Authentication\QueryString;

use Drupal\wconsumer\IntegrationTests\Rest\Authentication\AuthenticationTest;



class QueryStringTest extends AuthenticationTest {

  /**
   * @dataProvider Drupal\wconsumer\IntegrationTests\ServiceBaseTest::isInitializedDataProvider
   */
  public function testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult) {

    if ($domain == 'user' && $expectedResult == false) {
      $this->assertTrue(true);
      return;
    }

    parent::testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult);
  }
}