<?php
namespace Drupal\wconsumer\Tests\Integration\Service;



class DropboxTest extends AbstractServiceTest {

  public function testValidateServiceCredentials() {
    $this->markTestSkipped('Service credentials validation is not implemented for Dropbox service');
  }

  protected function currentUserInfoApiEndpoint() {
    return 'account/info';
  }
}