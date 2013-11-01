<?php
namespace Drupal\wconsumer\Tests\Integration;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\CurlException;


abstract class DrupalTestBase extends \PHPUnit_Framework_TestCase {
  /**
   * @var TestKeysContainer
   */
  protected $keys;

  /**
   * @var \DatabaseTransaction
   */
  private $transaction;



  public function setUp() {
    parent::setUp();

    $this->keys = new TestKeysContainer($this);
    $this->transaction = db_transaction();
  }

  public function tearDown() {
    if (isset($this->transaction)) {
      $this->transaction->rollback();
    }

    parent::tearDown();
  }

  protected function runTest() {
    $result = null;

    try {
      $result = parent::runTest();
    }
    catch (CurlException $e) {
      $this->markTestSkipped(
        "Request to '{$e->getRequest()->getUrl()}' failed due to connection problem. ".
        "Original message: {$e->getMessage()}."
      );
    }
    catch (ClientErrorResponseException $e) {
      if ($e->getResponse()->getStatusCode() == 429) {
        $this->markTestSkipped("Request to '{$e->getRequest()->getUrl()}' rejected due to rate limiting policy");
      }
      else {
        throw $e;
      }
    }

    return $result;
  }

  /**
   * @deprecated Use $this->keys->get() instead
   */
  protected function keys($section = NULL, $subsection = NULL, $subsubsection = NULL) {
    return call_user_func_array(array($this->keys, 'get'), func_get_args());
  }
}