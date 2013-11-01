<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class GoogleTest extends AbstractServiceTest {

  public function testApi() {
    $this->markTestSkipped(
      'We can\'t have a long living token for Google stored in keys.php. '.
      'So can\'t test Google API for now.'
    );
  }

  public function testValidateServiceCredentials() {
    $this->markTestSkipped('Service credentials validation is not implemented for Google service');
  }

  protected function currentUserInfoApiEndpoint() {
    return '/oauth2/v3/userinfo';
  }
}