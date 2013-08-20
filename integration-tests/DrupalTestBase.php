<?php
namespace Drupal\wconsumer\IntegrationTests;



abstract class DrupalTestBase extends \PHPUnit_Framework_TestCase {
  /**
   * @var \DatabaseTransaction
   */
  private $transaction;

  /**
   * An array of senstivie data required for testing like passwords, keys, secrets etc.
   * See sensitive-test-data.dist.php for details.
   *
   * @var array
   */
  protected $sensitiveData;



  public function setUp() {
    parent::setUp();

    $this->sensitiveData = require(__DIR__.'/keys.php');
    $this->transaction = db_transaction();
  }

  public function tearDown() {
    if (isset($this->transaction)) {
      $this->transaction->rollback();
    }

    parent::tearDown();
  }
}