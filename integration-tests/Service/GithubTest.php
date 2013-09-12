<?php
namespace Drupal\wconsumer\IntegrationTests\Service;

use Drupal\wconsumer\IntegrationTests\DrupalTestBase;
use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Github;


class GithubTest extends DrupalTestBase {

  public function testApi() {
    $GLOBALS['user'] = (object)array('uid' => 56);

    $github = $this->github();
    $api = $github->api();

    $response = $api->get('/user/followers')->send();

    $this->assertTrue($response->isSuccessful());
    $this->assertSame('GitHub.com', (string)$response->getHeader('Server'));
    $this->assertEquals($github->getCredentials()->scopes, explode(', ', (string)$response->getHeader('X-OAuth-Scopes')));
  }

  public function testGistCreationAndDeletion() {
    $api = $this->github()->api();

    // Prepare gist post body
    $gist = json_encode(array(
      'description' => 'Test gist',
      'public' => FALSE,
      'files' => array(
        'file1.txt' => array(
          'content' => 'This is test gist content',
        )
      )
    ));

    // Create gist
    $responseObject = $api->post('/gists', NULL, $gist)->send();
    $this->assertSame(201, $responseObject->getStatusCode());

    // Validate response
    $response = $responseObject->json();
    $this->assertInternalType('array', $response);
    $this->assertArrayHasKey('id', $response);
    $this->assertNotEmpty($response['id']);
    $this->assertSame('Test gist', @$response['description']);

    // Delete gist
    $responseObject = $api->delete('/gists/'.rawurlencode($response['id']))->send();
    $this->assertSame(204, $responseObject->getStatusCode());
  }

  private function github() {
    $github = new Github();
    $github->setServiceCredentials(Credentials::fromArray($this->keys('github', 'app')));
    $github->setCredentials(Credentials::fromArray($this->keys('github', 'user')));
    return $github;
  }
}