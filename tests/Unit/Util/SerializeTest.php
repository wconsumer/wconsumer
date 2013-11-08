<?php
namespace Drupal\wconsumer\Tests\Unit\Util;

use Drupal\wconsumer\Util\Serialize;



class SerializeTest extends \PHPUnit_Framework_TestCase {

  /**
   * @dataProvider provideSerializeExamples
   */
  public function testSerialize($input, $expectedOutput) {
    $result = Serialize::serialize($input);
    $this->assertSame($expectedOutput, $result);
  }

  public function testSerializeUsesCustomSerialization() {
    $object = new SerializeTest_Person('Tuttsy');
    $result = Serialize::serialize($object);
    $this->assertSame('name=Tuttsy', $result);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testSerializeFailsOnObjectIfNoCustomSerialization() {
    Serialize::serialize(new \stdClass());
  }

  /**
   * @dataProvider provideUnserializeExamples
   */
  public function testUnserialize($string, $expectedResult) {
    $result = Serialize::unserialize($string);
    $this->assertSame($expectedResult, $result);
  }

  public function testUnserializeUsesCustomUnserialization() {
    $result = Serialize::unserialize("name=Nancy", SerializeTest_Person::getClass());
    $this->assertInstanceOf(SerializeTest_Person::getClass(), $result);
    $this->assertSame('Nancy', $result->getName());
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testUnserializeFailsOnObjectIfNoCustomUnserialization() {
    Serialize::unserialize('{"name":"jane"}', '\stdClass');
  }

  public function testUnserializeForNullWithClass() {
    $result = Serialize::unserialize(NULL, '\stdClass');
    $this->assertNull($result);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testUnserializeFailsOnMalformedValue() {
    Serialize::unserialize("{name");
  }

  public static function provideSerializeExamples() {
    return array(
      array(NULL, "null"),
      array(FALSE, "false"),
      array(array('name' => 'Joe', 'occupation' => 'lawyer'), '{"name":"Joe","occupation":"lawyer"}'),
    );
  }

  public static function provideUnserializeExamples() {
    return array(
      array("null", NULL),
      array("false", FALSE),
      array('{"name":"Joe","occupation":"lawyer"}', array('name' => 'Joe', 'occupation' => 'lawyer')),
    );
  }
}

class SerializeTest_Person {
  private $name;


  public function __construct($name) {
    $this->name = $name;
  }

  public function serialize() {
    return http_build_query(array('name' => $this->name));
  }

  public static function unserialize($string) {
    parse_str($string, $values);
    return new static($values['name']);
  }

  public function getName() {
    return $this->name;
  }

  public static function getClass() {
    return get_called_class();
  }
}