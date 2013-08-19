<?php
namespace Drupal\wconsumer\Tests\Rest\Authentication;

use Drupal\wconsumer\Rest\Authentication\Credentials;



class CredentialsTest extends \PHPUnit_Framework_TestCase {
  public function testConstruction() {
    $credentials = new Credentials(123, 'abc', array('friends', 'messages'));
    $this->assertSame('123', $credentials->token);
    $this->assertSame('abc', $credentials->secret);
    $this->assertSame(array('friends', 'messages'), $credentials->scopes);
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
      'scopes' => NULL,
      'dummy' => 'skip'
    ));

    $this->assertSame('token', $credentials->token);
    $this->assertSame('secret', $credentials->secret);
    $this->assertSame(array(), $credentials->scopes);

    /** @noinspection PhpUndefinedFieldInspection */
    $this->assertTrue(!isset($credentials->dummy));
  }

  public function testSerialize() {
    $credentials = new Credentials('johntheuser', 'mypassword');
    $this->assertSame('{"token":"johntheuser","secret":"mypassword","scopes":[]}', $credentials->serialize());
  }

  public function testUnserialize() {
    $credentials = Credentials::unserialize('{"token":"johntheuser","secret":"mypassword","scopes":["scope1", "scope2"]}');
    $this->assertSame('johntheuser', $credentials->token);
    $this->assertSame('mypassword', $credentials->secret);
    $this->assertSame(array("scope1", "scope2"), $credentials->scopes);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testUnserializeFailsOnEmtpyString() {
    Credentials::unserialize('');
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testUnserializeFailsOnNull() {
    Credentials::unserialize(NULL);
  }
}