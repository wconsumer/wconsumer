<?php
namespace Drupal\wconsumer\AcceptanceTests;

use Drupal\wconsumer\IntegrationTests\TestKeysContainer;



class SeleniumTestCase extends \PHPUnit_Extensions_Selenium2TestCase {
  /**
   * @var TestKeysContainer
   */
  protected $keys;


  public static function getClass() {
    return get_called_class();
  }

  public static function getBaseClass() {
    return __CLASS__;
  }

  public function onNotSuccessfulTest(\Exception $e) {
    if ($this->getSessionId()) {
      $screenshotFilename = null; {
        $thisClassPath = explode('\\', get_class($this));
        $baseClassPaths = explode('\\', self::getBaseClass());
        $thisClassPathRelativeToBase = array_diff($thisClassPath, $baseClassPaths);
        $testCaseId = join('\\', $thisClassPathRelativeToBase).'::'.$this->getName();
        $screenshotBaseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $testCaseId);
        $screenshotFilename = SCREENSHOTS_DIR."/{$screenshotBaseName}.png";
      }

      file_put_contents($screenshotFilename, $this->currentScreenshot());
    }

    parent::onNotSuccessfulTest($e);
  }

  protected function setUp() {
    parent::setUp();

    $this->keys = new TestKeysContainer($this);

    $this->setBrowser('firefox');
    $this->setBrowserUrl(DRUPAL_BASE_URL);
  }
}
