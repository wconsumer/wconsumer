<?php
namespace Drupal\wconsumer\Tests\Authentication\Oauth2;

use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\Rest\Authentication\Oauth2\Oauth2;
use Drupal\wconsumer\Service\Base;
use Drupal\wconsumer\Tests\TestService;
use Guzzle\Http\Message\Response;



class Oauth2Test extends \PHPUnit_Framework_TestCase {

  public function testSignRequest() {
    $service = $this->getMockBuilder('Drupal\wconsumer\Service\Base')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->once())
      ->method('requireCredentials')
      ->will($this->returnValue(new Credentials('dummy', 'oauth2 access token')));

    $client = $this->getMockBuilder('Guzzle\Http\Client')->setMethods(array('send'))->getMock();

    /** @noinspection PhpParamsInspection */
    $auth = $this->auth($service);

    /** @noinspection PhpParamsInspection */
    $auth->signRequest($client);

    /** @var \Guzzle\Http\Client $client */
    $request = $client->createRequest();
    $request->dispatch('request.before_send', array('request' => $request));
    $authHeader = (string)$request->getHeader('Authorization');

    $this->assertSame('Bearer oauth2 access token', $authHeader);
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

    $service = $this->getMockBuilder('Drupal\wconsumer\Service\Base')->disableOriginalConstructor()->getMock();
    $service
      ->expects($this->once())
      ->method('setCredentials')
      ->with($this->identicalTo(null), $user->uid);

    /** @noinspection PhpParamsInspection */
    $auth = $this->auth($service);

    $auth->logout($user);
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
      $service = $this->getMockBuilder('Drupal\wconsumer\Service\Base')->disableOriginalConstructor()->getMock();

      $service
        ->expects($onceOrAny())
        ->method('requireServiceCredentials')
        ->will($this->returnValue(new Credentials('key', 'secret')));

      $service
        ->expects($onceOrAny())
        ->method('setCredentials')
        ->with(new Credentials('dummy', '__access_token__'), $user->uid);
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

    /** @noinspection PhpParamsInspection */
    $auth = $this->auth($service);
    $auth->accessTokenURL = $accessTokenUrl;
    $auth->client = $client;

    $auth->onCallback($user, array(array(
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
  public function testCallbackHandlerFailsOnAccessTokenRequestHttpLevelError() {
    $response = new Response(401);
    $this->testCallbackHandler(null, null, null, $response, true);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   * @expectedExceptionMessage 'bad_verification_code'
   */
  public function testCallbackHandlerFailsOnAccessTokenRequestApplicationLevelError() {
    $response = new Response(200, null, json_encode(array('error' => 'bad_verification_code')));
    $this->testCallbackHandler(null, null, null, $response, true);
  }

  private function authenticateTest($callbackUri, $consumerKey, $consumerSecret, $scopes, $urlTesterCallback) {
    $service = $this->getMockBuilder('Drupal\wconsumer\Service\Base')->disableOriginalConstructor()->getMock();

    $service
      ->expects($this->once())
      ->method('callback')
      ->will($this->returnValue($callbackUri));

    $service
      ->expects($this->once())
      ->method('requireServiceCredentials')
      ->will($this->returnValue(new Credentials($consumerKey, $consumerSecret)));


    /** @noinspection PhpParamsInspection */
    $auth = $this->auth($service);
    $auth->scopes = $scopes;

    $php =
      \PHPUnit_Extension_FunctionMocker::start($this, $this->getObjectNamespace($auth))
        ->mockFunction('drupal_goto')
      ->getMock();

    $php
      ->expects($this->once())
      ->method('drupal_goto')
      ->will($this->returnCallback(function ($url, $options) use($urlTesterCallback) {
        $urlTesterCallback($url);
      }));

    $null = NULL;
    $auth->authenticate($null);
  }

  private function getObjectNamespace($object) {
    $class = new \ReflectionClass($object);
    return $class->getNamespaceName();
  }

  private function auth(Base $service = NULL) {
    if (!isset($service)) {
      $service = new TestService();
    }

    $auth = new Oauth2($service);

    return $auth;
  }
}