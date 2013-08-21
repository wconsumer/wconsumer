<?php
namespace Drupal\wconsumer\IntegrationTests\Authentication\Oauth2;

use Drupal\wconsumer\IntegrationTests\Authentication\AuthenticationTest;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Authentication\Oauth2\Oauth2;
use Drupal\wconsumer\Service\Base as ServiceBase;
use Guzzle\Http\Message\Response;



class Oauth2Test extends AuthenticationTest {
  /**
   * @bypassDrupalGoto
   */
  public function testAuthenticateProvidesScopesInAuthorizeUrl() {
    $test = $this;

    $this->php
      ->expects($this->once())
      ->method('drupal_goto')
      ->will($this->returnCallback(function($url, $options) use($test) {
        $query = null;
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $test->assertSame('notifications,user:email', $query['scope']);
      }));

    $this->auth()->authenticate(NULL, array('notifications', 'user:email'));
  }

  /**
   * @bypassDrupalGoto
   */
  public function testAuthenticateSavesAuthStateInSession() {
    $scopes = array('notifications', 'user:email');

    $this->auth()->authenticate(NULL, $scopes);

    $state = @$_SESSION['wconsumer:integration_tests_test_service:oauth2_state'];
    $this->assertInternalType('array', $state);
    $this->assertArrayHasKey('key', $state);
    $this->assertNotEmpty($state['key']);
    $this->assertArrayHasKey('scopes', $state);
    $this->assertSame($scopes, $state['scopes']);

    return $state;
  }

  /**
   * @bypassDrupalGoto
   */
  public function testAuthenticateGeneratesUniqieStateKeyEachTime() {
    $state = $this->testAuthenticateSavesAuthStateInSession();
    $firstStateKey = $state['key'];

    $state = $this->testAuthenticateSavesAuthStateInSession();
    $secondStateKey = $state['key'];

    $this->assertNotEquals($firstStateKey, $secondStateKey);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testCallbackHandlerFailsOnInvalidStateKey() {
    $_SESSION['wconsumer:integration_tests_test_service:oauth2_state'] = array(
      'key' => 'xyz',
      'scopes' => array(),
    );

    $this->auth()->onCallback(NULL, array(array(
      'state' => 'abc',
      'code' => '123'
    )));
  }

  public function testCallbackHandlerStoresCredentialsWithScopes() {
    $_SESSION['wconsumer:integration_tests_test_service:oauth2_state'] = array(
      'key' => 'xyz',
      'scopes' => array('friends'),
    );

    $client = $this->getMockBuilder('Guzzle\Http\Client')->disableOriginalConstructor()->getMock();
    $client
      ->expects($this->once())
      ->method('send')
      ->will($this->returnValue(new Response(200, null, '{"access_token": " x123x "}')));

    $auth = $this->auth();
    $auth->client = $client;

    $auth->onCallback((object)array('uid' => 534), array(array(
      'state' => 'xyz',
      'code' => 'abc'
    )));

    $this->assertSame(array('friends'), $this->auth()->getService()->getCredentials(534)->scopes);
  }

  protected function auth(ServiceBase $service = NULL) {
    /** @var Oauth2 $auth */
    $auth = parent::auth($service);

    $auth->authorizeURL = 'https://github.com/login/oauth/authorize';
    $auth->accessTokenURL = 'https://github.com/login/oauth/access_token';

    return $auth;
  }

  protected function service() {
    $service = parent::service();

    $service->setServiceCredentials(Credentials::fromArray($this->keys('github', 'app')));

    return $service;
  }
}