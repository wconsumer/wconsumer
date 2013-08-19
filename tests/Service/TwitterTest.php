<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Rest\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Twitter;



class TwitterTest extends \PHPUnit_Framework_TestCase {

  public function testAuthentication() {
    $twitter = new Twitter();
    $this->assertInstanceOf(Oauth::getClass(), $twitter->authentication);
  }

  public function testName() {
    $twitter = new Twitter();
    $this->assertSame('twitter', $twitter->getName());
  }
}