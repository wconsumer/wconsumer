<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Meetup;



class MeetupTest extends AbstractServiceTest {

  protected function service() {
    $meetup = new Meetup();
    $meetup->setServiceCredentials(Credentials::fromArray($this->keys('meetup', 'app')));
    return $meetup;
  }
}