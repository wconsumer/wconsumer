<?php
namespace Drupal\wconsumer\Authentication;

use Drupal\wconsumer\Exception as WconsumerException;



/**
 * @property-read string $token
 * @property-read string $secret
 * @property-read array  $scopes
 */
class Credentials {
  private $token;
  private $secret;
  private $scopes;



  public static function fromArray(array $data) {
    /** @var self $self */
    $self = new static(@$data['token'], @$data['secret'], @$data['scopes']);
    return $self;
  }

  public static function getClass() {
    return get_called_class();
  }

  public function __construct($token, $secret, array $scopes = NULL) {
    $this->token = $this->input($token, 'Token');
    $this->secret = $this->input($secret, 'Token secret');
    $this->scopes = (array)$scopes;
  }

  public function __get($property) {
    return $this->{$property};
  }

  /**
   * Serialize credentials.
   *
   * We are doing it with JSON for three reasons:
   *  1. JSON is more human-readable than built-in php serialization format
   *  2. We don't need any special built-in serialization capabilities like storing class name or resource ids.
   *  3. We must keep us away from the special capabilities mentioned above. B/c in some cases this will lead to
   *      unability to unserialize previously serialized data. For example if we rename or move Credentials class in
   *      future versions.
   *
   * All that we need is to store plain array data.
   *
   * Also, we can't use predefined Serializable interface which is supported by serialize()/unserialize()
   * b/c ::unserialize() can't be static in it. I.e. php should know class name before ::unserialize() is called by
   * built-in unserialize(). That means we should include class name in the ::serialize() result. We don't want to do so
   * b/c we want to be able to painlessly rename or move class in the future.
   *
   * @return string
   */
  public function serialize() {
    return json_encode(get_object_vars($this));
  }

  public static function unserialize($string) {
    $data = @json_decode($string, true);
    if (!is_array($data)) {
      throw new \InvalidArgumentException
      (
        "Couldn't unserialize credentials from string '{$string}'. ".
        "Original json_decode error: '".json_last_error()."'."
      );
    }

    return static::fromArray($data);
  }

  private function input($value, $name) {
    if (isset($value) && !is_scalar($value)) {
      throw new \InvalidArgumentException("Invalid {$name} value '".var_export($value, true)."'");
    }

    $value = (string)$value;

    if ($value === '') {
      $value = null;
    }

    if (!isset($value)) {
      throw new WconsumerException("{$name} required");
    }

    return $value;
  }
}