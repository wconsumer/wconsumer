<?php
namespace Drupal\wconsumer\Tests\Rest\Authentication;

use Drupal\wconsumer\Rest\Authentication\Credentials;



class CredentialsTest extends \PHPUnit_Framework_TestCase {
  public function testConstruction() {
    $credentials = new Credentials(123, 'abc');
    $this->assertSame('123', $credentials->token);
    $this->assertSame('abc', $credentials->secret);
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testConstructionFailsOnEmptyToken() {
    new Credentials(null, 'abc');
  }

  /**
   * @expectedException \Drupal\wconsumer\Exception
   */
  public function testConstructionFailsOnEmptySecret() {
    new Credentials('johndoe', '');
  }

  public function testFromArrayConstruction() {
    $credentials = Credentials::fromArray(array(
      'token' => 'token',
      'secret' => 'secret',
      'dummy' => 'skip'
    ));

    $this->assertSame('token', $credentials->token);
    $this->assertSame('secret', $credentials->secret);

    /** @noinspection PhpUndefinedFieldInspection */
    $this->assertTrue(!isset($credentials->dummy));
  }
}