<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Flickr;



class FlickrTest extends AbstractServiceTest {

  protected function service() {
    $flickr = new Flickr();
    $flickr->setServiceCredentials(Credentials::fromArray($this->keys('flickr', 'app')));
    return $flickr;
  }
}