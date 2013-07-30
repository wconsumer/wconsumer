<?php
namespace Drupal\wconsumer\Tests\Authentication\Oauth2;

use Drupal\wconsumer\Rest\Authentication\Oauth2\Manager;
use Guzzle\Http\Message\Response;



class ManagerTest extends \PHPUnit_Framework_TestCase {

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testSystemCredentialsValidationFailsOnEmptyConsumerKey() {
    $manager = new Manager(NULL);
    $manager->formatRegistry(array('consumer_key' => NULL, 'consumer_secret' => 'xyz'));
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testSystemCredentialsValidationFailsOnEmptyConsumerSecret() {
    $manager = new Manager(NULL);
    $manager->formatRegistry(array('consumer_key' => 'abc'));
  }

  public function testSystemCredentialsFormatting() {
    $manager = new Manager(NULL);

    $result = $manager->formatRegistry(array(
      'consumer_key' => 'abc',
      'consumer_secret' => 'xyz',
      'dummy' => 'dummy'
    ));

    $this->assertSame(array(
      'consumer_key' => 'abc',
      'consumer_secret' => 'xyz',
    ), $result);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testUserCredentialsValidationFailsOnEmptyAccessToken() {
    $manager = new Manager(NULL);
    $manager->formatCredentials(array());
  }

  public function testUserCredentialsFormatting() {
    $manager = new Manager(NULL);
    $result = $manager->formatCredentials(array('access_token' => '123', 'dummy' => 'value'));
    $this->assertSame(array('access_token' => '123'), $result);
  }

  public function testIsInitializedForUser() {
    $registry = new \stdClass();
    $registry->credentials = array('access_token' => 'xyz');

    $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->exactly(3))
      ->method('getCredentials')
      ->will($this->returnValue($registry));

    $manager = new Manager($service);

    // Positive case
    $this->assertTrue($manager->is_initialized('user'));

    // Negative case #1
    unset($registry->credentials['access_token']);
    $this->assertFalse($manager->is_initialized('user'));

    // Negative case #2
    unset($registry->credentials);
    $this->assertFalse($manager->is_initialized('user'));
  }

  public function testIsInitializedForSystem() {
    $registry = new \stdClass();
    $registry->credentials = array('consumer_key' => 'xyz', 'consumer_secret' => 'abc');

    $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->exactly(3))
      ->method('getRegistry')
      ->will($this->returnValue($registry));

    $manager = new Manager($service);

    // Positive case
    $this->assertTrue($manager->is_initialized('system'));

    // Negative case #1
    unset($registry->credentials['consumer_key']);
    $this->assertFalse($manager->is_initialized('system'));

    // Negative case #2
    unset($registry->credentials);
    $this->assertFalse($manager->is_initialized('system'));
  }

  public function testIsNotInitializedForUnknown() {
    $manager = new Manager(null);
    $this->assertFalse($manager->is_initialized('dummy'));
  }

  public function testSignRequest() {
    $registry = new \stdClass();
    $registry->credentials = array('access_token' => 'user access token');

    $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->once())
      ->method('getCredentials')
      ->will($this->returnValue($registry));

    $client = $this->getMockBuilder('Guzzle\Http\Client')->setMethods(array('send'))->getMock();

    $manager = new Manager($service);

    $manager->sign_request($client);

    /** @var \Guzzle\Http\Client $client */
    $request = $client->createRequest();
    $request->dispatch('request.before_send', array('request' => $request));
    $authHeader = (string)$request->getHeader('Authorization');

    $this->assertSame('Bearer user access token', $authHeader);
  }

  public function testAuthenticate() {
    $testCase = $this;

    $this->authenticateTest(
      '__callback_url__',
      '__consumer_key__',
      '__consumer_secret__',
      array('__scope_1__', '__scope_2__'),
      function($url) use($testCase) {
        $testCase->assertNotEmpty($url);
        $testCase->assertContains('client_id=__consumer_key__', $url);
        $testCase->assertNotContains('__consumer_secret__', $url);
        $testCase->assertContains('redirect_uri=__callback_url__', $url);
        $testCase->assertContains('scope=__scope_1__%2C__scope_2__', $url);
      }
    );
  }

  public function testAuthenticateEscapesUrlParameters() {
    $testCase = $this;

    $this->authenticateTest(
      '__callback_ #()# _url__',
      '__consumer_ #()# _key__',
      '__consumer_ #()# _secret__',
      array('__scope_ #()# _one__', '__scope_ #()# _two__'),
      function($url) use($testCase) {
        $testCase->assertNotEmpty($url);
        $testCase->assertContains('client_id=__consumer_+%23%28%29%23+_key__', $url);
        $testCase->assertNotContains('__consumer_+%23%28%29%23+_secret__', $url);
        $testCase->assertContains('redirect_uri=__callback_+%23%28%29%23+_url__', $url);
        $testCase->assertContains('scope=__scope_+%23%28%29%23+_one__%2C__scope_+%23%28%29%23+_two__', $url);
      }
    );
  }

  public function testLogout() {
    $user = new \stdClass();
    $user->uid = time();

    $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->once())
      ->method('setCredentials')
      ->with($this->identicalTo(null), $user->uid);

    $manager = new Manager($service);

    $manager->logout($user);
  }

  public function testCallbackHandler($accessTokenUrl = null,
                                      $code = null,
                                      $state = null,
                                      Response $response = null,
                                      $testingForFail = null) {
    $test = $this;

    // Apply default param values
    {
      if (!isset($accessTokenUrl)) {
        $accessTokenUrl = '__access_token_url__';
      }

      if (!isset($code)) {
        $code = '__request_token_code__';
      }

      if (!isset($state)) {
        $state = 'wconsumer';
      }

      if (!isset($response)) {
        $response = new Response(200, null, json_encode(array(
          'access_token' => '__access_token__',
          'token_type' => 'bearer'
        )));
      }

      if (!isset($testingForFail)) {
        $testingForFail = false;
      }
    }

    $onceOrAny = function() use($test, $testingForFail) {
      return (!$testingForFail ? $test->once() : $test->any());
    };

    $user = new \stdClass();
    $user->uid = time();

    $service = null; {
      $registry = new \stdClass();
      $registry->credentials = array('consumer_key' => 'key', 'consumer_secret' => 'secret');

      $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();

      $service
        ->expects($onceOrAny())
        ->method('getRegistry')
        ->will($this->returnValue($registry));

      $service
        ->expects($onceOrAny())
        ->method('setCredentials')
        ->with(array('access_token' => '__access_token__'), $user->uid);
    }

    $request = null; {
      $request = $this->getMockBuilder('Guzzle\Http\Message\Request')->disableOriginalConstructor()->getMock();

      $request
        ->expects($onceOrAny())
        ->method('send')
        ->will($this->returnValue($response));
    }

    $client = null; {
      $client = $this->getMockBuilder('Guzzle\Http\Client')->disableOriginalConstructor()->getMock();

      $client
        ->expects($onceOrAny())
        ->method('post')
        ->will($this->returnCallback(function($url, $headers, $body, $options) use($test, $accessTokenUrl, $code, $request) {
          $test->assertSame('__access_token_url__', $url);

          $test->assertArrayHasKey('Accept', $headers);
          $test->assertSame('application/json', $headers['Accept']);

          $test->assertArrayHasKey('code', $body);
          $test->assertSame($code, $body['code']);

          return $request;
        }));
    }

    $manager = new Manager($service);
    $manager->accessTokenURL = $accessTokenUrl;
    $manager->client = $client;

    $manager->onCallback($user, array(array(
      'state' => $state,
      'code' => $code,
    )));
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testCallbackHandlerFailsOnInvalidStateMarker() {
    $this->testCallbackHandler(null, null, 'invalid', null, true);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testCallbackHandlerFailsOnEmptyCode() {
    $this->testCallbackHandler(null, false, null, null, true);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testCallbackHandlerFailsOnAccessTokenRequestFail() {
    $response = new Response(401);
    $this->testCallbackHandler(null, null, null, $response, true);
  }

  private function onceOrAny($once = true) {
    return ($once ? $this->once() : $this->any());
  }

  private function authenticateTest($callbackUri, $consumerKey, $consumerSecret, $scopes, $urlTesterCallback) {
    $service = $this->getMockBuilder('Drupal\wconsumer\ServiceBase')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->once())
      ->method('callback')
      ->will($this->returnValue($callbackUri));

    $registry = new \stdClass();
    $registry->credentials = array(
      'consumer_key' => $consumerKey,
      'consumer_secret' => $consumerSecret
    );

    $service
      ->expects($this->once())
      ->method('getRegistry')
      ->will($this->returnValue($registry));

    $manager = new Manager($service);
    $manager->scopes = $scopes;

    $php =
      \PHPUnit_Extension_FunctionMocker::start($this, $this->getObjectNamespace($manager))
        ->mockFunction('drupal_goto')
        ->getMock();

    $php
      ->expects($this->once())
      ->method('drupal_goto')
      ->will($this->returnCallback(function ($url, $options) use($urlTesterCallback) {
        $urlTesterCallback($url);
      }));

    $null = null;
    $manager->authenticate($null);
  }

  private function getObjectNamespace($object) {
    $class = new \ReflectionClass($object);
    return $class->getNamespaceName();
  }
}