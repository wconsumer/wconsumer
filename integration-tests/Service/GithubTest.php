<?php
namespace Drupal\wconsumer\IntegrationTests\Service;



class GithubTest extends AbstractServiceTest {

  public function testApi() {
    $GLOBALS['user'] = (object)array('uid' => 56);

    $github = $this->service();
    $api = $github->api();

    $response = $api->get('/user/followers')->send();

    $this->assertTrue($response->isSuccessful());
    $this->assertSame('GitHub.com', (string)$response->getHeader('Server'));
    $this->assertEquals($github->getCredentials()->scopes, explode(', ', (string)$response->getHeader('X-OAuth-Scopes')));
  }

  public function testGistCreationAndDeletion() {
    $api = $this->service()->api();

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

  protected function currentUserInfoApiEndpoint() {
    return '/user';
  }
}