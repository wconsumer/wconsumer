<?php
  namespace Drupal\wconsumer\Tests\Authentication\HttpAuth;

  use Drupal\wconsumer\Rest\Authentication\HttpAuth\HttpAuth;
  use Drupal\wconsumer\ServiceBase;
  use Drupal\wconsumer\Tests\TestService;


  class HttpAuthTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsValidationFailsOnEmptyUsernameIfItsRequired()
    {
      $auth = $this->auth(null, true, false);
      $auth->formatServiceCredentials(array('username' => ''));
    }

    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsValidationFailsOnEmptyPasswordIfItsRequired()
    {
      $auth = $this->auth(null, false, true);
      $auth->formatServiceCredentials(array('password' => null));
    }

    public function testServiceCredentialsValidation()
    {
      $auth = $this->auth(null, true, false);
      $result = $auth->formatServiceCredentials(array('username' => 'john doe', 'password' => 'dummy'));
      $this->assertSame(array('username' => 'john doe', 'password' => null), $result);
    }

    public function testUserCredentialsValidationIgnoresAnyPassedData()
    {
      $auth = $this->auth();
      $result = $auth->formatCredentials(array('some' => 'value'));
      $this->assertSame(array(), $result);
    }

    public function testIsInitializedWithAllRequiredCredentials()
    {
      $service = null;
      {
        $credentials = new \stdClass();
        $credentials->credentials = array('username' => 'johndoe', 'password' => 'parole');

        $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
        $service
          ->expects($this->any())
          ->method('getServiceCredentials')
          ->will($this->returnValue($credentials));
      }

      $auth = $this->auth($service, true, true);
      $this->assertTrue($auth->is_initialized('system'));

      $this->assertTrue($auth->is_initialized('user')); // should be always true
    }

    public function testIsInitializedWithMissingCredentials()
    {
      $service = null;
      {
        $credentials = new \stdClass();
        $credentials->credentials = array('username' => 'johndoe', 'password' => '');

        $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
        $service
          ->expects($this->any())
          ->method('getServiceCredentials')
          ->will($this->returnValue($credentials));
      }

      $auth = $this->auth($service, true, true);
      $this->assertFalse($auth->is_initialized('system'));

      $this->assertTrue($auth->is_initialized('user')); // should be always true
    }

    public function testIsInitializedWithUnknownAuthType()
    {
      $auth = $this->auth();
      $this->assertFalse($auth->is_initialized('unknown'));
    }

    public function testSignRequest()
    {
      $service = null;
      {
        $credentials = new \stdClass();
        $credentials->credentials = array('username' => 'johndoe', 'password' => 'dummy');

        $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
        $service
          ->expects($this->once())
          ->method('getServiceCredentials')
          ->will($this->returnValue($credentials));
      }

      $client = null;
      {
        $client = $this->getMockBuilder('Guzzle\Http\Client')->disableOriginalConstructor()->getMock();
        $client
          ->expects($this->once())
          ->method('addSubscriber');
      }

      $auth = $this->auth($service, true, false);

      $auth->sign_request($client);
    }

    private function auth(ServiceBase $service = null, $requireUsername = false, $requirePassword = false) {
      if (!isset($service)) {
        $service = new TestService();
      }

      $auth = new HttpAuth($service, $requireUsername, $requirePassword);

      return $auth;
    }
  }
?>