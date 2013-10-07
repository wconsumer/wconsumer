<?php
namespace Drupal\wconsumer\AcceptanceTests;

use Drupal\wconsumer\IntegrationTests\TestKeysContainer;



class SeleniumTestCase extends \PHPUnit_Extensions_Selenium2TestCase {
  /**
   * @var TestKeysContainer
   */
  protected $keys;



  protected function setUp() {
    parent::setUp();

    $this->keys = new TestKeysContainer($this);

    $this->setBrowser('firefox');
    $this->setBrowserUrl(DRUPAL_BASE_URL);
  }
}
