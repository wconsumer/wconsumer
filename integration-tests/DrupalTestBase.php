<?php
namespace Drupal\wconsumer\IntegrationTests;

use Guzzle\Http\Exception\ClientErrorResponseException;



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
    try {
      return parent::runTest();
    }
    catch (ClientErrorResponseException $e) {
      if ($e->getResponse()->getStatusCode() == 429) {
        $this->markTestSkipped("Request to '{$e->getRequest()->getUrl()}' rejected due to rate limiting policy");
      }
      else {
        throw $e;
      }
    }
  }

  /**
   * @deprecated Use $this->keys->get() instead
   */
  protected function keys($section = NULL, $subsection = NULL, $subsubsection = NULL) {
    return call_user_func_array(array($this->keys, 'get'), func_get_args());
  }
}