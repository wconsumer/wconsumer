<?php
  namespace Drupal\wconsumer\Tests\Authentication\HttpAuth;

  use Drupal\wconsumer\Rest\Authentication\Credentials;
  use Drupal\wconsumer\Rest\Authentication\HttpAuth\HttpAuth;
  use Drupal\wconsumer\ServiceBase;
  use Drupal\wconsumer\Tests\TestService;



  class HttpAuthTest extends \PHPUnit_Framework_TestCase
  {
    public function testIsInitializedWithAllRequiredCredentials()
    {
      $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
      $service
        ->expects($this->any())
        ->method('getServiceCredentials')
        ->will($this->returnValue(Credentials::fromArray(array(
          'token' => 'johndoe',
          'secret' => 'parole'
        ))));

      $auth = $this->auth($service);
      $this->assertTrue($auth->is_initialized('system'));

      $this->assertTrue($auth->is_initialized('user')); // always true
    }

    public function testIsInitializedWithMissingCredentials()
    {
      $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
      $service
        ->expects($this->any())
        ->method('getServiceCredentials')
        ->will($this->returnValue(null));

      $auth = $this->auth($service);
      $this->assertFalse($auth->is_initialized('system'));

      $this->assertTrue($auth->is_initialized('user')); // always true
    }

    public function testIsInitializedWithUnknownAuthType()
    {
      $auth = $this->auth();
      $this->assertFalse($auth->is_initialized('unknown'));
    }

    public function testSignRequest()
    {
      $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
      $service
        ->expects($this->once())
        ->method('getServiceCredentials')
        ->will($this->returnValue(Credentials::fromArray(array(
          'token' => 'johndoe',
          'secret' => 'der parol'
        ))));

      $client = null;
      {
        $client = $this->getMockBuilder('Guzzle\Http\Client')->disableOriginalConstructor()->getMock();
        $client
          ->expects($this->once())
          ->method('addSubscriber');
      }

      $auth = $this->auth($service);

      $auth->sign_request($client);
    }

    private function auth(ServiceBase $service = null) {
      if (!isset($service)) {
        $service = new TestService();
      }

      $auth = new HttpAuth($service);

      return $auth;
    }
  }
?>