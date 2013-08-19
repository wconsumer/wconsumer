<?php
namespace Drupal\wconsumer\IntegrationTests\Rest\Authentication\Oauth2;

use Drupal\wconsumer\IntegrationTests\Rest\Authentication\AuthenticationTest;
use Drupal\wconsumer\Rest\Authentication\Credentials;


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

    $auth = $this->auth();
    $auth->getService()->setServiceCredentials(new Credentials('dummy', 'dummy'));
    $auth->getService()->setCredentials(new Credentials('dummy', 'dummy'));

    $auth->authenticate(NULL, array('notifications', 'user:email'));
  }
}