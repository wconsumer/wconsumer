<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Linkedin;



class LinkedinTest extends AbstractServiceTest {

  public function testAuthorizedRequest() {
    $linkedin = $this->service();
    $linkedin->setCredentials(Credentials::fromArray($this->keys->get('linkedin', 'user')));
    $api = $linkedin->api();

    $response = $api->get('people/~:(id,formatted-name)?format=json')->send()->json();

    $this->assertNotEmpty($response['id']);
    $this->assertNotEmpty($response['formattedName']);
  }

  public function testValidateServiceCredentials() {
    $this->markTestSkipped("LinkedIn does not support service credentials validation");
  }

  protected function service() {
    $linkedin = new Linkedin();
    $linkedin->setServiceCredentials(Credentials::fromArray($this->keys->get('linkedin', 'app')));
    return $linkedin;
  }
}