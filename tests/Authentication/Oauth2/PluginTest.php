<?php
namespace Drupal\wconsumer\Tests\Authentication\Oauth2;

use Drupal\wconsumer\Authentication\Oauth2\Plugin;
use Guzzle\Common\Event;
use Guzzle\Http\Message\Request;


class PluginTest extends \PHPUnit_Framework_TestCase {
  /**
   * @expectedException \InvalidArgumentException
   */
  public function testConstructionRequiresAccessToken() {
    new Plugin(NULL);
  }

  public function testSubscribesBeforeSendEvent() {
    $plugin = new Plugin('hello');
    $this->assertArrayHasKey('request.before_send', $plugin->getSubscribedEvents());
  }

  public function testAuthorizationWithHeader() {
    $request = new Request(Request::GET, 'http://fake.invalid');
    $event = new Event(array('request' => $request));
    $plugin = new Plugin('abc');

    $plugin->onRequestBeforeSend($event);

    $this->assertSame('Bearer abc', (string)$request->getHeader('Authorization'));
  }

  public function testAuthorizationWithUrlParameter() {
    $request = new Request(Request::GET, 'http://fake.invalid');
    $event = new Event(array('request' => $request));
    $plugin = new Plugin('xyz', 'oauth_access_token');

    $plugin->onRequestBeforeSend($event);

    $this->assertSame('xyz', $request->getQuery()->get('oauth_access_token'));
    $this->assertNull($request->getHeader('Authorization'));
  }
}