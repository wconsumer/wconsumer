<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Linkedin;



class LinkedinTest extends AbstractServiceTest {

  public function testAuthentication() {
    $linkedin = $this->service();
    $this->assertInstanceOf(Oauth::getClass(), $linkedin->authentication);
  }

  public function testName() {
    $linkedin = $this->service();
    $this->assertSame('linkedin', $linkedin->getName());
  }

  protected function service() {
    return new Linkedin();
  }
}