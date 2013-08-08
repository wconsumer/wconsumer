<?php
namespace Drupal\wconsumer\Rest\Authentication;

use Drupal\wconsumer\Exception as WconsumerException;



/**
 * @property-read string $token
 * @property-read string $secret
 */
class Credentials {
  private $token;
  private $secret;

  public static function fromArray(array $data) {
    return new static(@$data['token'], @$data['secret']);
  }

  public static function getClass() {
    return get_called_class();
  }

  public function __construct($token, $secret) {
    $this->token = $this->input($token, 'Token');
    $this->secret = $this->input($secret, 'Token secret');
  }

  public function __get($property) {
    return $this->{$property};
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