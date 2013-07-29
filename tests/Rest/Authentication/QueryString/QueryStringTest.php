<?php
  namespace Drupal\wconsumer\Tests\Authentication\QueryString;

  use Drupal\wconsumer\Rest\Authentication\QueryString\QueryString;



  class QueryStringTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsValidationFailsIfNoQueryKeyProvided()
    {
      $auth = new QueryString(null);
      $auth->queryKey = null;
      $auth->formatRegistry(array('query_key' => '', 'query_value' => '12345'));
    }

    /**
     * @expectedException \Drupal\wconsumer\Exception
     */
    public function testServiceCredentialsFailsIfNoQueryValueProvided()
    {
      $auth = new QueryString(null);
      $auth->formatRegistry(array('query_key' => 'key', 'query_value' => ''));
    }

    public function testServiceCredentialsValidationWithPredefinedQueryKey()
    {
      $auth = new QueryString(null);
      $auth->queryKey = 'password';
      $result = $auth->formatRegistry(array('query_key' => '', 'query_value' => '12345'));
      $this->assertSame(array('query_key' => '', 'query_value' => '12345'), $result);
    }

    public function testServiceCredentialsValidationWithNoPredefinedKey()
    {
      $auth = new QueryString(null);
      $auth->queryKey = null;
      $result = $auth->formatRegistry(array('query_key' => 'password', 'query_value' => '12345'));
      $this->assertSame(array('query_key' => 'password', 'query_value' => '12345'), $result);
    }

    public function testUserCredentialsValidationIgnoresAnyInput()
    {
      $result = $this->auth()->formatCredentials(array('now' => time()));
      $this->assertSame(array(), $result);
    }

    public function testIsInitialized()
    {
      $service = null;
      {
        $getRegistryResult = new \stdClass();
        $getRegistryResult->credentials = array('query_key' => '', 'query_value' => 'parole');

        $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
        $service
          ->expects($this->any())
          ->method('getRegistry')
          ->will($this->returnValue($getRegistryResult));
      }

      $auth = new QueryString($service);
      $auth->queryKey = 'pass';

      $result = $auth->is_initialized('system');
      $this->assertTrue($result);

      $auth->queryKey = null;

      $result = $auth->is_initialized('system');
      $this->assertFalse($result);

      $result = $auth->is_initialized('user');
      $this->assertTrue($result);

      $result = $auth->is_initialized('unknown');
      $this->assertFalse($result);
    }

    public function testSignRequestWithPredefinedQueryKey()
    {
      $this->signRequestTest('pass', '');
    }

    public function testSignRequestWithNoPredefinedQueryKey()
    {
      $this->signRequestTest(null, 'pass');
    }

    private function signRequestTest($predefinedQueryKey = null, $storedQueryKey = null)
    {
      $service = null;
      {
        $getRegistryResult = new \stdClass();
        $getRegistryResult->credentials = array('query_key' => $storedQueryKey, 'query_value' => 'parole');

        $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
        $service
          ->expects($this->any())
          ->method('getRegistry')
          ->will($this->returnValue($getRegistryResult));
      }

      $client = $this->getMockBuilder('Guzzle\Http\Client')->setMethods(array('send'))->getMock();

      $auth = new QueryString($service);
      $auth->queryKey = $predefinedQueryKey;

      $auth->sign_request($client);

      /** @var \Guzzle\Http\Client $client */
      $request = $client->createRequest();
      $request->dispatch('request.before_send', array('request' => $request));

      $query = (string)$request->getQuery();

      $this->assertSame('pass=parole', $query);
    }

    private function auth()
    {
      return new QueryString(null);
    }
  }
?>