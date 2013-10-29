<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Service;
use Drupal\wconsumer\Service\Vimeo;



class VimeoTest extends AbstractServiceTest {

  public function testAuthentication() {
    $vimeo = $this->service();
    $this->assertInstanceOf(Oauth::getClass(), $vimeo->authentication);
  }

  public function testName() {
    $vimeo = $this->service();
    $this->assertSame('vimeo', $vimeo->getName());
  }

  protected function service() {
    return new Vimeo();
  }
}