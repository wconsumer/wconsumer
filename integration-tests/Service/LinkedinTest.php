<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Linkedin;



class LinkedinTest extends AbstractServiceTest {

  protected function service() {
    $linkedin = new Linkedin();
    $linkedin->setServiceCredentials(Credentials::fromArray($this->keys('linkedin', 'app')));
    return $linkedin;
  }
}