<?php
namespace Drupal\wconsumer\Tests\Service;

use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Service\Vimeo;



class VimeoTest extends \PHPUnit_Framework_TestCase {

  public function testAuthentication() {
    $vimeo = new Vimeo();
    $this->assertInstanceOf(Oauth::getClass(), $vimeo->authentication);
  }

  public function testName() {
    $vimeo = new Vimeo();
    $this->assertSame('vimeo', $vimeo->getName());
  }
}