<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Rest\Authentication\Credentials;
use Drupal\wconsumer\Service\Github;


class GithubTest extends DrupalTestBase {

  public function testApi() {
    $github = new Github();

    $github->setServiceCredentials(Credentials::fromArray($this->sensitiveData['github']['app']));
    $github->setCredentials(Credentials::fromArray($this->sensitiveData['github']['user']));

    $api = $github->api();

    $response = $api->get('/user/followers')->send();

    $this->assertTrue($response->isSuccessful());
    $this->assertSame('GitHub.com', (string)$response->getHeader('Server'));
    $this->assertEquals($github->getCredentials()->scopes, explode(', ', (string)$response->getHeader('X-OAuth-Scopes')));
  }
}