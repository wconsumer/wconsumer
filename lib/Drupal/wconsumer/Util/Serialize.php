<?php
namespace Drupal\wconsumer\Util;


/**
 * Class provides serialize() and unserialize() methods which should be used commonly instead of built-in PHP ones.
 *
 * The reason why do we want to have custom serialization is that we want to avoid storing of class names along with
 * their data. Doing so would bring a headache if/when we want to rename/move serialized classes which are already
 * stored on multiple servers. We do want to be able to refactor everything we need without writing special upgrade
 * scripts to achieve this.
 */
class Serialize {

  /**
   * Turns any $value into a persistence-ready string
   *
   * Currently fails if $value is an instance of a class without serialize() method. That could be changed in
   * the future if we decide we want to spend some more time implementing a common way to serialize objects.
   *
   * @param mixed $value
   * @return string
   *
   * @throws \InvalidArgumentException If $value is an instance of a class without serialize() method
   */
  public static function serialize($value) {
    if (is_object($value)) {
      self::checkCustomSerialization($value, 'serialize');
      return $value->serialize();
    }

    $result = json_encode($value);
    if ($result === FALSE) {
      self::jsonError("Couldn't encode value to serialize", $value);
    }

    return $result;
  }

  /**
   * Turns string into a language object
   *
   * If $string is a serialized object instance then you have to provide its $class. $class must have unserialize()
   * method implemented. That's b/c we don't want yet to spend time on implementing a common way to serialize and
   * unserialize objects.
   *
   * @param string|null $string
   * @param string|null $class
   * @return mixed
   *
   * @throws \InvalidArgumentException If $value is an instance of a class without unserialize() method
   */
  public static function unserialize($string, $class = NULL) {
    if (isset($string) && !is_string($string)) {
      throw new \InvalidArgumentException('$string expected to be a string type');
    }

    if (!isset($string) || strcasecmp($string, 'null') == 0) {
      return NULL;
    }

    if (isset($class)) {
      self::checkCustomSerialization($class, 'unserialize');
      return call_user_func(array($class, 'unserialize'), $string);
    }

    $result = json_decode($string, TRUE);
    if (!isset($result)) {
      self::jsonError("Couldn't decode serialized string", $string);
    }

    return $result;
  }

  private static function checkCustomSerialization($class, $method) {
    if (!method_exists($class, $method)) {
      throw new \InvalidArgumentException('The only supported way to serialize/unserialize objects '.
                                          'is a custom per-class serialize/unserialize implementation');
    }
  }

  private static function jsonError($message, $data) {
    throw new \InvalidArgumentException(
      $message.". ".
      "Data: '".var_export($data, TRUE)."'. ".
      "Original error code: '".json_last_error()."'."
    );
  }
}