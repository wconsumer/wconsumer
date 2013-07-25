<?php
namespace Drupal\wconsumer\Rest;
use Drupal\wconsumer\Common\RequestInterface;
use Guzzle\Http\Client;

/**
 * REST Request Class
 *
 * @package wconsumer
 * @subpackage request
 */
class Request implements RequestInterface
{
  /**
   * API Base URL
   *
   * @var string
   */
  private $apiURL;

  /**
   * Instance Object of this Request Class
   *
   * @var object
   * @access private
   */
  private static $instance = NULL;

  /**
   * Instance of the Service Object
   *
   * @var object
   * @access private
   */
  private $_serviceInstance;

  /**
   * @var Client
   */
  private $client;

  /**
   * Authentication Object
   *
   * @var object
   */
  public $authencation;

  /**
   * Construct the Request Object
   *
   * @param $client Client
   */
  public function __construct(Client $client)
  {
    $this->client = $client;
  }

  /**
   * Call this method to get a instance
   *
   * @return object
   * @access public
   * @codeCoverageIgnore
   */
  public static function Instance()
  {
    if (static::$instance !== NULL)
      static::$instance = new Request(new Client());

    return static::$instance;
  }

  /**
   * Set the API Url
   *
   * @param string A valid URL base
   */
  public function setApiUrl($url) { $this->apiURL = $url; }

  /**
   * Retrieve the API Base
   *
   * @return string
   */
  public function getApiUrl() { return $this->apiURL; }

  /**
   * Magic Method to make a request a bit easier
   *
   * @return object
   * @access private
   */
  public function __call($method, $arguments = array())
  {
    $this->makeRequest($this->getApiUrl(), $method, $arguments);
  }

  /**
   * Manually setup and Execute the Request
   *
   * @param  string
   * @param  string HTTP method
   * @param  array
   * @return object
   */
  public function makeRequest($endPoint, $method, $arguments)
  {
    $this->client->setBaseUrl($endPoint);

    // Manage Authentication
    $this->authencation->sign_request($this->client);

    // Make the request
    array_unshift($arguments, $method);

    $request = call_user_func_array(array($this->client, 'createRequest'), $arguments);
    return $request->send();
  }
}
