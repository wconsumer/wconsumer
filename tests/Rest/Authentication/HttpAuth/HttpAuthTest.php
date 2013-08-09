<?php
  namespace Drupal\wconsumer\Tests\Authentication\HttpAuth;

  use Drupal\wconsumer\Rest\Authentication\Credentials;
  use Drupal\wconsumer\Rest\Authentication\HttpAuth\HttpAuth;
  use Drupal\wconsumer\ServiceBase;
  use Drupal\wconsumer\Tests\TestService;



  class HttpAuthTest extends \PHPUnit_Framework_TestCase
  {
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

      $auth->signRequest($client);
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