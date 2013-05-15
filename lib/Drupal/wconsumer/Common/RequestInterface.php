<?php
namespace Drupal\wconsumer\Common;

interface RequestInterface {
  /**
   * Set the API URL
   *
   * @param string
   */
  public function setApiUrl($url);

  /**
   * Retrieve the API URL
   *
   * @return string
   */
  public function getApiUrl();

  /**
   * Make a Request Manually
   *
   * @param  string
   * @param  string HTTP method (POST, GET, etc)
   * @param  array
   * @return  mixed
   */
  public function makeRequest($endPoint, $method, $arguments);

  /**
   * Magic Method to Make Requests
   *
   * ```
   * $object->get('end/point.json', array(...));
   * ```
   * @return mixed
   */
  public function __call($method, $arguments);

  /**
   * Return an Instance of the Object
   *
   * @return object
   */
  public static function Instance();
}
