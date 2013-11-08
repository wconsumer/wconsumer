<?php
namespace Drupal\wconsumer\Tests\Integration\Service;



class LinkedinTest extends AbstractServiceTest {

  public function testAuthorizedRequest() {
    $response = $this->service()->api()->get($this->currentUserInfoApiEndpoint())->send()->json();

    $this->assertNotEmpty($response['id']);
    $this->assertNotEmpty($response['formattedName']);
  }

  public function testValidateServiceCredentials() {
    $this->markTestSkipped("LinkedIn does not support service credentials validation");
  }

  protected function currentUserInfoApiEndpoint() {
    return 'people/~:(id,formatted-name)?format=json';
  }
}