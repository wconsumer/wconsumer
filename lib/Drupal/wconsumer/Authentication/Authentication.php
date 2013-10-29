<?php
namespace Drupal\wconsumer\Authentication;

use Drupal\wconsumer\Service\Service;
use Drupal\wconsumer\Wconsumer;



class Authentication {
  protected $service;



  public function __construct(Service $service) {
    $this->service = $service;
  }

  public function getService() {
    return $this->service;
  }

  public function validateServiceCredentials(Credentials $credentials) {
    return TRUE;
  }

  public static function getClass() {
    return get_called_class();
  }

  // This function takes a input like a=b&a=c&d=e and returns the parsed
  // parameters like this
  // array('a' => array('b','c'), 'd' => 'e')
  protected static function parseResponse($input) {
    if (!isset($input) || !$input) {
      return array();
    }

    $pairs = explode('&', $input);
    $parsed_parameters = array();

    foreach ($pairs as $pair) {
      $split = explode('=', $pair, 2);
      $parameter = urldecode($split[0]);
      $value = isset($split[1]) ? urldecode($split[1]) : '';

      if (isset($parsed_parameters[$parameter])) {

        // We have already recieved parameter(s) with this name, so add to the list
        // of parameters with this name
        if (is_scalar($parsed_parameters[$parameter])) {
          // This is the first duplicate, so transform scalar (string) into an array
          // so we can add the duplicates
          $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
        }

        $parsed_parameters[$parameter][] = $value;
      }
      else {
        $parsed_parameters[$parameter] = $value;
      }
    }

    return $parsed_parameters;
  }

  protected function session($key, $value = NULL) {
    $args = func_get_args();
    array_unshift($args, $this->service->getName());

    $result = call_user_func_array(array(Wconsumer::instance(), 'session'), $args);

    if (func_num_args() < 2  && !isset($result)) {
      throw new \BadMethodCallException('
        Auth data not found in the current session.
        Please make sure you call auth methods in a correct order.
      ');
    }

    return $result;
  }
}
