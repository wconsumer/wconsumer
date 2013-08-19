<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Linkedin;



class LinkedinTest extends \PHPUnit_Framework_TestCase {

  public function testAuthentication() {
    $twitter = new Linkedin();
    $this->assertInstanceOf(Oauth::getClass(), $twitter->authentication);
  }

  public function testName() {
    $twitter = new Linkedin();
    $this->assertSame('linkedin', $twitter->getName());
  }
}