<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Meetup;



class MeetupTest extends AbstractServiceTest {

  public function testAuthentication() {
    $meetup = $this->service();
    $this->assertInstanceOf(Oauth::getClass(), $meetup->authentication);
  }

  public function testName() {
    $meetup = $this->service();
    $this->assertSame('meetup', $meetup->getName());
  }

  protected function service() {
    return new Meetup();
  }
}