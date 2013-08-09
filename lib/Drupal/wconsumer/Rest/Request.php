<?php
namespace Drupal\wconsumer\Rest;

use Drupal\wconsumer\Service;
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
  protected $apiURL;

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
  public function __construct(Client $client = null)
  {
    if (!isset($client))
    {
      $client = Service::createHttpClient();
    }

    $this->client = $client;
  }

  /**
   * Set the API Url
   *
   * @param string $url A valid URL base
   */
  public function setApiUrl($url) {
    $this->apiURL = $url;
    $this->client->setBaseUrl($url);
  }

  /**
   * Retrieve the API Base
   *
   * @return string
   */
  public function getApiUrl() {
    return $this->apiURL;
  }

  public function __call($method, array $arguments = array()) {
    return $this->makeRequest($method, $arguments);
  }

  public function makeRequest($method, array $arguments = array()) {
    $this->authencation->sign_request($this->client);

    array_unshift($arguments, $method);

    /** @var \Guzzle\Http\Message\Request $request */
    $request = call_user_func_array(array($this->client, 'createRequest'), $arguments);

    return $request->send();
  }
}
