<?php
namespace Drupal\wconsumer\Rest;

use Drupal\wconsumer\Rest\Authentication\AuthInterface;
use Drupal\wconsumer\Common\RequestInterface;
use Drupal\wconsumer\Wconsumer;
use Guzzle\Http\Client;


/**
 * REST Request Class
 *
 * @package wconsumer
 * @subpackage request
 *
 * @method mixed get()    get   ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs GET HTTP request
 * @method mixed post()   post  ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs POST HTTP request
 * @method mixed put()    put   ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs PUT HTTP request
 * @method mixed delete() delete($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs DELETE HTTP request
 * @method mixed head()   head  ($arg1 = NULL, $arg2 = NULL, $argN = NULL) Performs HEAD HTTP request
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
   * @var AuthInterface
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
      $client = Wconsumer::instance()->container['httpClient'];
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
    $this->authencation->signRequest($this->client);

    array_unshift($arguments, $method);

    /** @var \Guzzle\Http\Message\Request $request */
    $request = call_user_func_array(array($this->client, 'createRequest'), $arguments);

    return $request->send();
  }
}
