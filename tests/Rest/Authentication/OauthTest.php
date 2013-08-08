<?php
namespace Drupal\wconsumer\Tests\Authentication;

use Drupal\wconsumer\Rest\Authentication\Oauth;
use Drupal\wconsumer\Tests\TestService;



class OauthTest extends \PHPUnit_Framework_TestCase {

  public function testFormatServiceCredentials() {
    $auth = $this->auth();

    $result = $auth->formatServiceCredentials(
      $this->correctServiceCredentials() +
      array('dummy' => 'skip')
    );

    $this->assertSame($this->correctServiceCredentials(), $result);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testFormatServiceCredentialsFailsOnEmptyToken() {
    $credentials = $this->correctServiceCredentials();
    unset($credentials['consumer_key']);

    $this->auth()->formatServiceCredentials($credentials);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testFormatServiceCredentialsFailsOnEmptySecret() {
    $credentials = $this->correctServiceCredentials();
    $credentials['consumer_secret'] = '';

    $this->auth()->formatServiceCredentials($credentials);
  }

  public function testFormatUserCredentials() {
    $result = $this->auth()->formatCredentials(
      $this->correctUserCredentials() +
      array('shouldbe' => 'skipped')
    );

    $this->assertSame($this->correctUserCredentials(), $result);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testFormatUserCredentialsFailsOnEmptyToken() {
    $credentials = $this->correctUserCredentials();
    $credentials['oauth_token'] = null;

    $this->auth()->formatCredentials($credentials);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testFormatUserCredentialsFailsOnEmptySecret() {
    $credentials = $this->correctUserCredentials();
    $credentials['oauth_token_secret'] = '0';

    $this->auth()->formatCredentials($credentials);
  }

  private function correctServiceCredentials() {
    return array(
      'consumer_key' => '123',
      'consumer_secret' => 'abc',
    );
  }

  private function correctUserCredentials() {
    return array(
      'oauth_token' => '123',
      'oauth_token_secret' => '123',
    );
  }

  private function auth() {
    return new Oauth(new TestService());
  }
}