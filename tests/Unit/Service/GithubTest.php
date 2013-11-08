<?php
namespace Drupal\wconsumer\Tests\Unit\Service;

use Drupal\wconsumer\Authentication\Oauth2\Oauth2;
use Drupal\wconsumer\Service\Github;



class GithubTest extends AbstractServiceTest {

  public function testAuthentication() {
    $github = $this->service();
    $this->assertInstanceOf(Oauth2::getClass(), $github->authentication);
  }

  public function testName() {
    $github = $this->service();
    $this->assertSame('github', $github->getName());
  }

  protected function service() {
    return new Github();
  }
}