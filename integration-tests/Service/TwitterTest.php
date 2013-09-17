<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Twitter;



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

  protected function service() {
    $twitter = new Twitter();

    $twitter->setServiceCredentials(Credentials::fromArray($this->keys('twitter', 'app')));
    $twitter->setCredentials(Credentials::fromArray($this->keys('twitter', 'user')));

    return $twitter;
  }
}