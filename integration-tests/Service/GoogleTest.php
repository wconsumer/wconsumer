<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class GoogleTest extends AbstractServiceTest {

  public function testApi() {
    $response = $this->service()->api()->get($this->currentUserInfoApiEndpoint())->send()->json();
    $this->assertNotEmpty(@$response['sub']);
  }

  public function testValidateServiceCredentials() {
    $this->markTestSkipped('Service credentials validation is not implemented for Google service');
  }

  protected function currentUserInfoApiEndpoint() {
    return '/oauth2/v3/userinfo';
  }
}