<?php
namespace Drupal\wconsumer\IntegrationTests\Authentication;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Rest\Authentication\Oauth;
use Drupal\wconsumer\ServiceBase;



class OauthTest extends DrupalTestBase {
  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  private $php;



  public function setUp() {
    parent::setUp();

    $this->php =
      \PHPUnit_Extension_FunctionMocker::start($this, 'Drupal\wconsumer\Rest\Authentication')
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

  /**
   * @dataProvider isInitializedDataProvider
   */
  public function testIsInitialized($serviceCredentials, $userCredentials, $domain, $expectedResult) {
    $auth = $this->auth(new OauthTestSevice());
    $service = $auth->getService();

    $service->setServiceCredentials($serviceCredentials);
    $service->setCredentials($userCredentials);

    $this->assertSame($expectedResult, $auth->is_initialized($domain));
  }

  public static function isInitializedDataProvider() {
    $serviceCredentials = array('consumer_key' => '123', 'consumer_secret' => 'abc');
    $userCredentials = array('access_token' => '123', 'access_token_secret' => 'abc');

    return array(
      array(null, null, 'user', false),
      array(null, null, 'system', false),
      array(null, null, 'unknown', false),
      array($serviceCredentials, null, 'user', false),
      array($serviceCredentials, null, 'system', true),
      array($serviceCredentials, null, 'unknown', false),
      array(null, $userCredentials, 'user', true),
      array(null, $userCredentials, 'system', false),
      array(null, $userCredentials, 'unknown', false),
      array($serviceCredentials, $userCredentials, 'user', true),
      array($serviceCredentials, $userCredentials, 'system', true),
      array($serviceCredentials, $userCredentials, 'unknown', false),
    );
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

    $this->assertNotEmpty($_SESSION['oauth_test_sevice:oauth_token']);
    $this->assertNotEmpty($_SESSION['oauth_test_sevice:oauth_token_secret']);
  }

  /**
   * @expectedException \Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException
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
   * @expectedException \Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException
   */
  public function testAuthenticateFailsOnOauthApiLevelError() {
    $service = null;
    {
      $service = $this->getMock(OauthTestSevice::getClass(), array('callback'));

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
    $service->setServiceCredentials(array());

    $auth = $this->auth($service);

    $auth->authenticate($GLOBALS['user']);
  }

  /**
   * @expectedException \Drupal\wconsumer\Rest\Authentication\Oauth\OAuthException
   */
  public function testCallbackHandlerFailsOnInvalidRequestToken() {
    $auth = $this->auth();

    $_SESSION['oauth_test_sevice:oauth_token'] = 'abc';
    $_SESSION['oauth_test_sevice:oauth_token_secret'] = '123';

    $auth->onCallback($GLOBALS['user'], array());
  }

  /**
   * @expectedException \BadMethodCallException
   */
  public function testCallbackHandlerFailsIfNoRequestTokenInSession() {
    $auth = $this->auth();
    $auth->onCallback($GLOBALS['user'], array());
  }

  private function auth($service = null) {
    global $user;

    $user = new \stdClass();
    $user->uid = 99;

    if (!isset($service)) {
      $service = $this->service();
    }

    $auth = new Oauth($service);
    $auth->requestTokenURL = 'https://api.twitter.com/oauth/request_token';
    $auth->authorizeURL = 'https://api.twitter.com/oauth/authorize';
    $auth->accessTokenURL = 'https://api.twitter.com/oauth/access_token';

    return $auth;
  }

  private function service() {
    return $this->configureService(new OauthTestSevice());
  }

  private function configureService(OauthTestSevice $service) {
    $service->setServiceCredentials(array(
      'consumer_key'    => $this->sensitiveData['twitter']['app']['key'],
      'consumer_secret' => $this->sensitiveData['twitter']['app']['secret'],
    ));

    return $service;
  }
}

/**
 * We need this class b/c we need an unique service name which is generated from class name to isolate the test case
 * in this file from others. See ServiceBase::$_instance variable for details.
 */
class OauthTestSevice extends ServiceBase {
  protected $_service = 'oauth_test_sevice';

  public static function getClass() {
    return get_called_class();
  }
}