<?php
namespace Drupal\wconsumer;



class Exception extends \Exception {
  public static function getClass() {
    return get_called_class();
  }
}
