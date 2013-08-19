<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Meetup;



class MeetupTest extends \PHPUnit_Framework_TestCase {

  public function testAuthentication() {
    $twitter = new Meetup();
    $this->assertInstanceOf(Oauth::getClass(), $twitter->authentication);
  }

  public function testName() {
    $twitter = new Meetup();
    $this->assertSame('meetup', $twitter->getName());
  }
}