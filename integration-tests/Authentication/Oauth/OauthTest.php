<?php
namespace Drupal\wconsumer\IntegrationTests\Authentication\Oauth;

use Drupal\wconsumer\IntegrationTests\Authentication\AuthenticationTest;
use Drupal\wconsumer\IntegrationTests\TestService;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Base;
use Drupal\wconsumer\Util\Serialize;
use Guzzle\Http\Client;



class OauthTest extends AuthenticationTest {

  public function testSignRequest($user = null) {
    $service = $this->service(TRUE, TRUE);
    $auth = $this->auth($service);

    $client = new Client();
    $auth->signRequest($client, $user);

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

  public function testSignRequestUsesSpecifiedUserCredentials() {
    // Test for current user which have credentials set up
    $this->testSignRequest(null);

    // Test for some not existing user which have not credentials set up
    $this->setExpectedException('\BadMethodCallException');
    $user = (object)array('uid' => 55);
    $this->testSignRequest($user);
  }

  /**
   * @bypassDrupalGoto
   */
  public function testAuthorizeFetchesRequestTokenAndRedirectsUserToAuthorizeUrl() {
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

    $auth->authorize($GLOBALS['user']);
  }

  /**
   * @bypassDrupalGoto
   */
  public function testAuthorizeSavesRequestTokenInSession() {

    $this->php
      ->expects($this->once())
      ->method('drupal_goto');

    $auth = $this->auth();
    $auth->authorize($GLOBALS['user']);

    $credentials = @$_SESSION['wconsumer:integration_tests_test_service:oauth_request_token'];
    $credentials = Serialize::unserialize($credentials, Credentials::getClass());
    $this->assertNotNull($credentials);
  }

  /**
   * @expectedException \Guzzle\Http\Exception\CurlException
   */
  public function testAuthorizeFailsOnNetworkLevelError() {
    $auth = $this->auth();
    $auth->requestTokenUrl = 'http://host.invalid';
    $auth->authorize($GLOBALS['user']);
  }

  /**
   * @expectedException \Drupal\wconsumer\Authentication\Oauth\OAuthException
   */
  public function testAuthorizeFailsOnInvalidResponse() {
    $auth = $this->auth();
    $auth->requestTokenUrl = 'http://example.com';
    $auth->authorize($GLOBALS['user']);
  }

  /**
   * @expectedException \Guzzle\Http\Exception\ClientErrorResponseException
   */
  public function testAuthorizeFailsOnOauthApiLevelError() {
    $service = null;
    {
      $service = $this->getMock(TestService::getClass(), array('getCallbackUrl'));

      $service
        ->expects($this->once())
        ->method('getCallbackUrl')
        ->will($this->returnValue('C:\fake\url'));

      /** @noinspection PhpParamsInspection */
      $this->configureService($service);
    }

    $auth = $this->auth($service);

    $auth->authorize($GLOBALS['user']);
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testAuthorizeFailsOnEmptyServiceCredentials() {
    $service = $this->service();
    $service->setServiceCredentials(null);

    $auth = $this->auth($service);

    $auth->authorize($GLOBALS['user']);
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
    $_SESSION['wconsumer:integration_tests_test_service:oauth_request_token'] =
        Serialize::serialize(new Credentials('abc', '123'));

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

  protected function auth(Base $service = null) {
    /** @var Oauth $auth */
    $auth = parent::auth($service);

    $auth->requestTokenUrl = 'https://api.twitter.com/oauth/request_token';
    $auth->authorizeUrl = 'https://api.twitter.com/oauth/authorize';
    $auth->accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

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
      $service->setServiceCredentials(Credentials::fromArray($this->keys('twitter', 'app')));
    }

    if ($setupUserCredentials) {
      $this->setupUser();
      $service->setCredentials(Credentials::fromArray($this->keys('twitter', 'user')));
    }

    return $service;
  }
}