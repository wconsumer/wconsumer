<?php
namespace Drupal\wconsumer\Tests\Unit\Authentication;

use Drupal\wconsumer\Authentication\Credentials;



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

  /**
   * @dataProvider provideEqualTests
   */
  public function testEqual($first, $second, $expectedResult) {
    $result = Credentials::equal($first, $second);
    $this->assertSame($expectedResult, $result);
  }

  public static function provideEqualTests() {
    return array(
      // Basic example
      array(new Credentials('abc', 'xyz', array('scope')), new Credentials('abc', 'xyz', array('scope')), true),

      // Nulls
      array(null, null, true),
      array(new Credentials('abc', 'xyz'), null, false),

      // Different scopes
      array(new Credentials('abc', 'xyz', array('scope1')), new Credentials('abc', 'xyz', array('scope2')), false),

      // Same scope set but in different order
      array(new Credentials('abc', 'xyz', array('scope1', 'scope2')), new Credentials('abc', 'xyz', array('scope2', 'scope1')), true),

      // Conversion to int pitfall (string converted to 0 int)
      array(new Credentials('abc', 'xyz'), new Credentials(0, 0), false),
    );
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