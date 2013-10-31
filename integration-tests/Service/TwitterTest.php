<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class TwitterTest extends AbstractServiceTest {

  public function testApi() {
    $GLOBALS['user'] = (object)array('uid' => 65);

    $twitter = $this->service();

    $api = $twitter->api();

    $response = $api->get('account/verify_credentials.json')->send();
    $this->assertTrue($response->isSuccessful());

    $data = $response->json();
    $this->assertArrayHasKey('created_at', $data);
    $this->assertArrayHasKey('followers_count', $data);
    $this->assertArrayHasKey('friends_count', $data);
  }

  protected function currentUserInfoApiEndpoint() {
    return 'account/settings.json';
  }
}