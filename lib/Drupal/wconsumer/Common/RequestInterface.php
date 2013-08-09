<?php
namespace Drupal\wconsumer\Common;

use Guzzle\Http\Message\Response;


/**
 * @method mixed get()    get   ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs GET HTTP request
 * @method mixed post()   post  ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs POST HTTP request
 * @method mixed put()    put   ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs PUT HTTP request
 * @method mixed delete() delete($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs DELETE HTTP request
 * @method mixed head()   head  ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs HEAD HTTP request
 */
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
   * @param string $method HTTP method (POST, GET, etc)
   * @param array $arguments
   * @return Response
   */
  public function makeRequest($method, array $arguments = array());

  /**
   * Magic Method to Make Requests
   *
   * <code>
   * $object->get('end/point.json', array(...));
   * </code>
   *
   * @param string $method
   * @param array  $arguments
   * @return Response
   */
  public function __call($method, array $arguments = array());
}
