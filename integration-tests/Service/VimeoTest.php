<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class VimeoTest extends AbstractServiceTest {

  protected function currentUserInfoApiEndpoint() {
    return '?method=vimeo.people.getInfo&format=json';
  }
}