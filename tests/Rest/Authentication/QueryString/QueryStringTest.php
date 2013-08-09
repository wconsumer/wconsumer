<?php
  namespace Drupal\wconsumer\Tests\Authentication\QueryString;

  use Drupal\wconsumer\Rest\Authentication\Credentials;
  use Drupal\wconsumer\Rest\Authentication\QueryString\QueryString;
  use Drupal\wconsumer\ServiceBase;
  use Drupal\wconsumer\Tests\TestService;



  class QueryStringTest extends \PHPUnit_Framework_TestCase
  {
    public function testSignRequestWithPredefinedQueryKey()
    {
      $this->signRequestTest('pass', 'dummy');
    }

    public function testSignRequestWithNoPredefinedQueryKey()
    {
      $this->signRequestTest(null, 'pass');
    }

    private function signRequestTest($predefinedQueryKey = null, $storedQueryKey = null)
    {
      $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
      $service
        ->expects($this->any())
        ->method('getServiceCredentials')
        ->will($this->returnValue(new Credentials($storedQueryKey, 'parole')));

      $client = $this->getMockBuilder('Guzzle\Http\Client')->setMethods(array('send'))->getMock();

      /** @noinspection PhpParamsInspection */
      $auth = $this->auth($service);
      $auth->queryKey = $predefinedQueryKey;

      $auth->sign_request($client);

      /** @var \Guzzle\Http\Client $client */
      $request = $client->createRequest();
      $request->dispatch('request.before_send', array('request' => $request));

      $query = (string)$request->getQuery();

      $this->assertSame('pass=parole', $query);
    }

    private function auth(ServiceBase $service = null)
    {
      if (!isset($service)) {
        $service = new TestService();
      }

      $auth = new QueryString($service);

      return $auth;
    }
  }
?>