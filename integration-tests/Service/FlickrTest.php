<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class FlickrTest extends AbstractServiceTest {

  public function testApi() {
    $api = $this->service()->api();

    $response = $api->get('?method=flickr.push.getSubscriptions&format=json&nojsoncallback=1')->send()->json();

    $this->assertNotNull(@$response['subscriptions']);
    $this->assertSame('ok', @$response['stat']);
  }

  protected function currentUserInfoApiEndpoint() {
    return '?method=flickr.push.getSubscriptions&format=json&nojsoncallback=1';
  }
}