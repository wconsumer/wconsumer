<?php
namespace Drupal\wconsumer\Tests\Integration\Service;



class VimeoTest extends AbstractServiceTest {

  protected function currentUserInfoApiEndpoint() {
    return '?method=vimeo.people.getInfo&format=json';
  }
}