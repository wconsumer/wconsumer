<?php
namespace Drupal\wconsumer\Tests\Rest;

use Drupal\wconsumer\Rest\Request;



class RequestTest extends \PHPUnit_Framework_TestCase {

  public function testSetGetApiUrl() {
    $url = 'http://invalid.example';

    $request = new Request($this->clientMock());
    $request->setApiUrl($url);
    $this->assertSame($url, $request->getApiUrl());
  }

  public function testCall() {
    $request = $this->getMockBuilder('Guzzle\Http\Message\Request')->disableOriginalConstructor()->getMock();
    $request
      ->expects($this->once())
      ->method('send');

    $client = $this->clientMock();
    $client
      ->expects($this->once())
      ->method('createRequest')
      ->with('post', 'joe')
      ->will($this->returnValue($request));

    $auth = $this->getMock('Drupal\wconsumer\Common\AuthInterface');
    $auth
      ->expects($this->once())
      ->method('sign_request')
      ->with($client);

    $restRequest = new Request($client);
    $restRequest->authencation = $auth;
    $restRequest->post('joe');
  }

  private function clientMock() {
    return $this->getMockBuilder('Guzzle\Http\Client')->disableOriginalConstructor()->getMock();
  }
}