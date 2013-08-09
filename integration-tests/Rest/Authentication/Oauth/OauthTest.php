<?php
namespace Drupal\wconsumer\IntegrationTests\Rest\Authentication\Oauth;

use Drupal\wconsumer\IntegrationTests\Rest\Authentication\AuthenticationTest;
use Drupal\wconsumer\IntegrationTests\TestService;
use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\Rest\Authentication\Oauth\Oauth;
use Drupal\wconsumer\ServiceBase;
use Guzzle\Http\Client;



class OauthTest extends AuthenticationTest {
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $php;



  public function setUp() {
    parent::setUp();

    $this->php =
      \PHPUnit_Extension_FunctionMocker::start($this, 'Drupal\wconsumer\Rest\Authentication\Oauth')
        ->mockFunction('drupal_goto')
      ->getMock();

    // There are two reasons to disable drupal_goto() by default:
    //  1. It just terminates current process with an empty phpunit output.
    //  2. By default we don't expected redirects, b/c it's a control flow violation.
    $annotations = $this->getAnnotations();
    $neverOrAny = !isset($annotations['method']['allowDrupalGoto']) ? $this->never() : $this->any();
    $this->php
      ->expects($neverOrAny)
      ->method('drupal_goto');
  }

  public function testSignRequest() {
    $service = $this->service(TRUE, TRUE);
    $auth = $this->auth($service);

    $client = new Client();
    $auth->signRequest($client);

    $response = $client->get('https://api.twitter.com/1.1/account/verify_credentials.json')->send();
    $this->assertTrue($response->isSuccessful());
    $responseData = $response->json();
    $this->assertNotEmpty($responseData['name']);
    $this->assertNotEmpty($responseData['screen_name']);
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testSignRequestFailsOnUninitializedServiceCredentials() {
    $service = $this->service(FALSE, TRUE);
    $auth = $this->auth($service);
    $auth->signRequest($client = new Client());
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testSignRequestFailsOnUninitializedUserCredentials() {
    $auth = $this->auth();
    $auth->signRequest($client = new Client());
  }

  /**
   * @allowDrupalGoto
   */
  public function testAuthenticateFetchesRequestTokenAndRedirectsUserToAuthorizeUrl() {
    $self = $this;

    $auth = $this->auth();

    $this->php
      ->expects($this->once())
      ->method('drupal_goto')
      ->will($this->returnCallback(function ($url, $options) use($self) {
        $self->assertNotEmpty($url);

        $urlParts = parse_url($url);
        $self->assertSame('api.twitter.com', $urlParts['host']);
        $self->assertSame('/oauth/authorize', $urlParts['path']);
        $self->assertRegExp('/^oauth_token=\S+$/', $urlParts['query']);

        $self->assertSame(array('external' => TRUE), $options);
      }));

    $auth->authenticate($user);
  }

  /**
   * @allowDrupalGoto
   */
  public function testAuthenticateSavesRequestTokenInSession() {

    $this->php
      ->expects($this->once())
      ->method('drupal_goto');

    $auth = $this->auth();
    $auth->authenticate($GLOBALS['user']);

    $credentials = $_SESSION['integration_tests_test_service:oauth_request_token'];
    $this->assertNotNull($credentials);
    $this->assertInstanceOf(Credentials::getClass(), $credentials);
  }

  /**
   * @expectedException \Guzzle\Http\Exception\CurlException
   */
  public function testAuthenticateFailsOnNetworkLevelError() {
    $auth = $this->auth();
    $auth->requestTokenURL = 'http://host.invalid';
    $auth->authenticate($GLOBALS['user']);
  }

  /**
   * @expectedException \Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException
   */
  public function testAuthenticateFailsOnInvalidResponse() {
    $auth = $this->auth();
    $auth->requestTokenURL = 'http://example.com';
    $auth->authenticate($GLOBALS['user']);
  }

  /**
   * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
   */
  public function testAuthenticateFailsOnOauthApiLevelError() {
    $service = null;
    {
      $service = $this->getMock(TestService::getClass(), array('callback'));

      $service
        ->expects($this->once())
        ->method('callback')
        ->will($this->returnValue('C:\fake\url'));

      /** @noinspection PhpParamsInspection */
      $this->configureService($service);
    }

    $auth = $this->auth($service);

    $auth->authenticate($GLOBALS['user']);
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testAuthenticateFailsOnEmptyServiceCredentials() {
    $service = $this->service();
    $service->setServiceCredentials(null);

    $auth = $this->auth($service);

    $auth->authenticate($GLOBALS['user']);
  }

  public function testLogout() {
    $user = new \stdClass();
    $user->uid = 123;

    $service = new TestService();

    $credentials = new Credentials('test', '123');
    $service->setCredentials($credentials, $user->uid);
    $this->assertEquals($credentials, $service->getCredentials($user->uid));

    $auth = $this->auth($service);

    $auth->logout($user);

    $this->assertSame(null, $service->getCredentials($user->uid));
  }

  /**
   * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
   */
  public function testCallbackHandlerFailsOnInvalidRequestToken() {
    $_SESSION['integration_tests_test_service:oauth_request_token'] = new Credentials('abc', '123');

    $auth = $this->auth();
    $auth->onCallback($GLOBALS['user'], array());
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testCallbackHandlerFailsIfNoRequestTokenInSession() {
    $auth = $this->auth();
    $auth->onCallback($GLOBALS['user'], array());
  }

  protected function auth(ServiceBase $service = null) {
    if (!isset($service)) {
      $service = $this->service();
    }

    $auth = new Oauth($service);
    $auth->requestTokenURL = 'https://api.twitter.com/oauth/request_token';
    $auth->authorizeURL = 'https://api.twitter.com/oauth/authorize';
    $auth->accessTokenURL = 'https://api.twitter.com/oauth/access_token';

    return $auth;
  }

  protected function service($setupServiceCredentials = true, $setupUserCredentials = false) {
    return $this->configureService(parent::service(), $setupServiceCredentials, $setupUserCredentials);
  }

  private function setupUser() {
    global $user;

    $user = new \stdClass();
    $user->uid = 99;
  }

  private function configureService(TestService $service,
                                    $setupServiceCredentials = true,
                                    $setupUserCredentials = false) {

    if ($setupServiceCredentials) {
      $service->setServiceCredentials(new Credentials(
        $this->sensitiveData['twitter']['app']['key'],
        $this->sensitiveData['twitter']['app']['secret']
      ));
    }

    if ($setupUserCredentials) {
      $this->setupUser();
      $service->setCredentials(new Credentials(
        $this->sensitiveData['twitter']['user']['token'],
        $this->sensitiveData['twitter']['user']['secret']
      ));
    }

    return $service;
  }
}