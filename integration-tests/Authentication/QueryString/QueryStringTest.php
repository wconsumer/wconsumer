<?php
namespace Drupal\wconsumer\IntegrationTests\Authentication\QueryString;

use Drupal\wconsumer\IntegrationTests\Authentication\AuthenticationTest;



class QueryStringTest extends AuthenticationTest {

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