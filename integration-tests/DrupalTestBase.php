<?php
namespace Drupal\wconsumer\IntegrationTests;

use Guzzle\Http\Exception\ClientErrorResponseException;



abstract class DrupalTestBase extends \PHPUnit_Framework_TestCase {
  /**
   * @var \DatabaseTransaction
   */
  private $transaction;



  public function setUp() {
    parent::setUp();
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
   * Returns senstivie data required for testing like passwords, keys, secrets etc.
   * See keys.dist.php for details.
   */
  protected function keys($section = NULL, $subsection = NULL, $subsubsection = NULL) {
    static $keys;

    if (!isset($keys)) {
      $keysFile = __DIR__.'/keys.php';
      if (file_exists($keysFile)) {
        $keys = include($keysFile);
      }
      if (empty($keys)) {
        $keys = array();
      }
    }

    $result = $keys;
    foreach (func_get_args() as $section) {
      $result = @$result[$section];
    }

    if (empty($result)) {
      $this->markTestSkipped(
        'Test requires sensitive test data under ['.join('][', func_get_args()).'] '.
        'section of keys.php which is not set'
      );
    }

    return $result;
  }
}