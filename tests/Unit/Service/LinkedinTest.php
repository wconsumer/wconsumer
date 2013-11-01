<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;
use Drupal\wconsumer\Service\Linkedin;



class LinkedinTest extends AbstractServiceTest {

  public function testAuthentication() {
    $linkedin = $this->service();
    $this->assertInstanceOf(Oauth2::getClass(), $linkedin->authentication);
  }

  public function testName() {
    $linkedin = $this->service();
    $this->assertSame('linkedin', $linkedin->getName());
  }

  protected function service() {
    return new Linkedin();
  }
}