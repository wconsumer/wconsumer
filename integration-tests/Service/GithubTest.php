<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Github;


class GithubTest extends DrupalTestBase {

  public function testApi() {
    $GLOBALS['user'] = (object)array('uid' => 56);

    $github = new Github();

    $github->setServiceCredentials(Credentials::fromArray($this->keys('github', 'app')));
    $github->setCredentials(Credentials::fromArray($this->keys('github', 'user')));

    $api = $github->api();

    $response = $api->get('/user/followers')->send();

    $this->assertTrue($response->isSuccessful());
    $this->assertSame('GitHub.com', (string)$response->getHeader('Server'));
    $this->assertEquals($github->getCredentials()->scopes, explode(', ', (string)$response->getHeader('X-OAuth-Scopes')));
  }
}