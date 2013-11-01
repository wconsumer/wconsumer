<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class MeetupTest extends AbstractServiceTest {

  protected function currentUserInfoApiEndpoint() {
    return 'member/self';
  }
}