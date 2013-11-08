<?php
namespace Drupal\wconsumer\Tests\Integration\Service;



class MeetupTest extends AbstractServiceTest {

  protected function currentUserInfoApiEndpoint() {
    return 'member/self';
  }
}