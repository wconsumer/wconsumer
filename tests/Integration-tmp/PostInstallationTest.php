<?php
namespace Drupal\wconsumer\Tests\Integration;

/**
 * A test suite to run right after drupal + wconsumer installation to make sure everything is set up properly and
 * is ready for integration testing
 */
class PostInstallationTest extends DrupalTestBase {

  public function testWconsumerInstalled() {
    $this->assertTrue(module_exists('wconsumer'));
  }
}