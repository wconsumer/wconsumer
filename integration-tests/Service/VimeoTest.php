<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Vimeo;



class VimeoTest extends AbstractServiceTest {

  protected function service() {
    $vimeo = new Vimeo();
    $vimeo->setServiceCredentials(Credentials::fromArray($this->keys('vimeo', 'app')));
    return $vimeo;
  }
}