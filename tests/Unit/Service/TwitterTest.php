<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Service;
use Drupal\wconsumer\Service\Twitter;



class TwitterTest extends AbstractServiceTest {

  public function testAuthentication() {
    $twitter = $this->service();
    $this->assertInstanceOf(Oauth::getClass(), $twitter->authentication);
  }

  public function testName() {
    $twitter = $this->service();
    $this->assertSame('twitter', $twitter->getName());
  }

  protected function service() {
    return new Twitter();
  }
}