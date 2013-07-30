<?php
namespace Drupal\wconsumer\Tests\Authentication\Oauth2;

use Drupal\wconsumer\Rest\Authentication\Oauth2\Plugin;
use Guzzle\Common\Event;



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

  public function testRequestHeaderInjection() {
    $request = $this->getMockBuilder('Guzzle\Http\Message\Request')->disableOriginalConstructor()->getMock();
    $request
      ->expects($this->once())
      ->method('setHeader')
      ->with('Authorization', 'Bearer abc');

    $event = new Event(array('request' => $request));

    $plugin = new Plugin('abc');
    $plugin->onRequestBeforeSend($event);
  }
}