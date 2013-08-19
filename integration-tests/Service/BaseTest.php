<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\IntegrationTests\TestService;



class BaseTest extends DrupalTestBase {

  public function testApi() {
    $service = new TestService();
    $service->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $service->setCredentials(new Credentials('dummy', 'dummy', array('user', 'profile')), 91);
    $api = $service->api(91, array('user'));
    $this->assertInstanceOf('Guzzle\Http\Client', $api);
    $this->assertSame('http://url.example', $api->getBaseUrl());
  }

  /**
   * @expectedException \Drupal\wconsumer\Service\Exception\ServiceInactive
   */
  public function testApiFailsIfServiceIsNotConfigured() {
    $service = new TestService();
    $service->api(91);
  }

  /**
   * @expectedException \Drupal\wconsumer\Service\Exception\NoUserCredentials
   */
  public function testApiFailsIfNoUserCredentialsStored() {
    $service = new TestService();
    $service->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $service->api(91);
  }

  /**
   * @expectedException \Drupal\wconsumer\Service\Exception\AdditionalScopesRequired
   */
  public function testApiFailsIfAdditionalScopesRequired() {
    $service = new TestService();
    $service->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $service->setCredentials(new Credentials('dummy', 'dummy', array('user', 'profile')), 91);
    $service->api(91, array('user', 'friends'));
  }

  /**
   * @expectedException \Drupal\wconsumer\Service\Exception\NotLoggedInUser
   */
  public function testApiFailsOnNotLoggedInUser() {
    $service = new TestService();
    $service->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $service->setCredentials(new Credentials('dummy', 'dummy', array('user', 'profile')), 91);
    $service->api(0, array('user', 'profile'));
  }

  public function testCredentialsGettingSetting() {
    $service = new TestService();

    $GLOBALS['user'] = (object)array('uid' => 5);

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

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testSetCredentialsFailsIfSpecifiedUserIsNotLoggedIn() {
    $service = new TestService();
    $service->setCredentials(new Credentials('mynickname', 'mypassword'), 0);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testSetCredentialsFailsOnGlobalUserIsNotLoggedIn() {
    $service = new TestService();
    $service->setCredentials(new Credentials('mynickname', 'mypassword'));
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testRequireCredentialsFailsIfNoCredentialsSet() {
    $service = new TestService();
    $service->requireCredentials();
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

  /**
   * @expectedException \BadMethodCallException
   */
  public function testRequireServiceCredentialsFailsIfNoCredentialsSet() {
    $service = new TestService();
    $service->requireServiceCredentials();
  }

  /**
   * @dataProvider isInitializedDataProvider
   */
  public function testCheckAuthentication($serviceCredentials, $userCredentials, $domain, $expectedResult) {
    $service = new TestService();

    $GLOBALS['user'] = (object)array('uid' => 100);

    $service->setServiceCredentials($serviceCredentials);
    $service->setCredentials($userCredentials);

    $this->assertSame($expectedResult, $service->checkAuthentication($domain));
  }

  public function testCheckAuthenticationForSpecifiedUser() {
    $credentials = new Credentials('usernmae', 'password');

    $GLOBALS['user'] = (object)array('uid' => 66);

    $service = new TestService();
    $this->assertFalse($service->checkAuthentication('user'));
    $this->assertFalse($service->checkAuthentication('user', 77));

    $service->setCredentials($credentials);
    $this->assertTrue($service->checkAuthentication('user'));
    $this->assertFalse($service->checkAuthentication('user', 77));

    $service->setCredentials($credentials, 77);
    $this->assertTrue($service->checkAuthentication('user'));
    $this->assertTrue($service->checkAuthentication('user', 77));

    $service->setCredentials(null);
    $this->assertFalse($service->checkAuthentication('user'));
    $this->assertTrue($service->checkAuthentication('user', 77));

    $service->setCredentials(null, 77);
    $this->assertFalse($service->checkAuthentication('user'));
    $this->assertFalse($service->checkAuthentication('user', 77));
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

}