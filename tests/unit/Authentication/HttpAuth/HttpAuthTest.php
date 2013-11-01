<?php
  namespace Drupal\wconsumer\Tests\Unit\Authentication\HttpAuth;

  use Drupal\wconsumer\Authentication\Credentials;
  use Drupal\wconsumer\Authentication\HttpAuth\HttpAuth;
  use Drupal\wconsumer\Service\Service;
  use Drupal\wconsumer\Tests\Unit\TestService;



  class HttpAuthTest extends \PHPUnit_Framework_TestCase {

    public function testSignRequest() {
      $service = $this->getMockBuilder(Service::getClass())->disableOriginalConstructor()->getMock();
      $service
        ->expects($this->once())
        ->method('requireServiceCredentials')
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

    private function auth(Service $service = null) {
      if (!isset($service)) {
        $service = new TestService();
      }

      $auth = new HttpAuth($service);

      return $auth;
    }
  }
?>