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

  public function testApiWithNotLoggedInUser() {
    $service = new TestService();
    $service->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $service->setCredentials(new Credentials('dummy', 'dummy', array('user', 'profile')), 0);

    $api = $service->api(0, array('user', 'profile'));

    $this->assertInstanceOf('Guzzle\Http\Client', $api);
    $this->assertSame('http://url.example', $api->getBaseUrl());
  }

  /**
   * @dataProvider isActiveDataProvider
   */
  public function testIsActive($serviceCredentials, $enabled, $expectedResult) {
    $service = new TestService();
    $service->setServiceCredentials($serviceCredentials);
    $service->setEnabled($enabled);

    $this->assertSame($expectedResult, $service->isActive());
  }

  public static function isActiveDataProvider() {
    $credentials = new Credentials('dummy', 'dummy');

    return array(
      array($credentials, TRUE, TRUE),
      array(NULL, TRUE, FALSE),
      array(NULL, FALSE, FALSE),
      array($credentials, FALSE, FALSE),
    );
  }

  public function testIsActiveIsFalseByDefault() {
    $service = new TestService();
    $this->assertFalse($service->isActive());
  }

  public function testEnabledGettingSetting() {
    $service = new TestService();

    $this->assertTrue($service->isEnabled());

    $service->setEnabled(FALSE);
    $this->assertFalse($service->isEnabled());

    $service->setEnabled(TRUE);
    $this->assertTrue($service->isEnabled());
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

  public function testSetCredentialsIsAbleToStoreCredentialsIfSpecifiedUserIsNotLoggedIn() {
    $service = new TestService();

    $service->setCredentials(new Credentials('mynickname', 'mypassword'), 0);
    $credentials = $service->getCredentials(0);

    $this->assertInstanceOf(Credentials::getClass(), $credentials);
    $this->assertSame('mynickname', $credentials->token);
    $this->assertSame('mypassword', $credentials->secret);
  }

  public function testSetCredentialsIsAbleToStoreCredentialsIfGlobalUserIsNotLoggedIn() {
    $service = new TestService();

    $service->setCredentials(new Credentials('mynickname', 'mypassword'));
    $credentials = $service->getCredentials();

    $this->assertInstanceOf(Credentials::getClass(), $credentials);
    $this->assertSame('mynickname', $credentials->token);
    $this->assertSame('mypassword', $credentials->secret);
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
}