<?php
  namespace Drupal\wconsumer\Tests\Authentication\HttpAuth;

  use Drupal\wconsumer\Rest\Authentication\HttpAuth\HttpAuth;



  class HttpAuthTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsValidationFailsOnEmptyUsernameIfItsRequired()
    {
      $auth = new HttpAuth(null, true, false);
      $auth->formatServiceCredentials(array('username' => ''));
    }

    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsValidationFailsOnEmptyPasswordIfItsRequired()
    {
      $auth = new HttpAuth(null, false, true);
      $auth->formatServiceCredentials(array('password' => null));
    }

    public function testServiceCredentialsValidation()
    {
      $auth = new HttpAuth(null, true, false);
      $result = $auth->formatServiceCredentials(array('username' => 'john doe', 'password' => 'dummy'));
      $this->assertSame(array('username' => 'john doe', 'password' => null), $result);
    }

    public function testUserCredentialsValidationIgnoresAnyPassedData()
    {
      $auth = new HttpAuth();
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

      $auth = new HttpAuth($service, true, true);
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

      $auth = new HttpAuth($service, true, true);
      $this->assertFalse($auth->is_initialized('system'));

      $this->assertTrue($auth->is_initialized('user')); // should be always true
    }

    public function testIsInitializedWithUnknownAuthType()
    {
      $auth = new HttpAuth();
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

      $auth = new HttpAuth($service, true, false);

      $auth->sign_request($client);
    }
  }
?>