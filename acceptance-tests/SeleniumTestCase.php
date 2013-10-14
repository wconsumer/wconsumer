<?php
namespace Drupal\wconsumer\AcceptanceTests;

use Drupal\wconsumer\IntegrationTests\TestKeysContainer;
use Exception;
use RuntimeException;


class SeleniumTestCase extends \PHPUnit_Extensions_Selenium2TestCase {
  const MAX_TRIES = 3;

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
      $this->saveScreenshot();
      $this->appendCurrentUrl($e);
    }

    parent::onNotSuccessfulTest($e);
  }

  protected function runTest() {
    $triesLeft = self::MAX_TRIES;

    $exception = null;
    while ($triesLeft--) {
      try {
        return parent::runTest();
      }
      catch (\Exception $e) {
        $exception = $e;
      }
    }

    throw $exception;
  }

  protected function setUp() {
    parent::setUp();

    $this->keys = new TestKeysContainer($this);

    $this->setBrowser('firefox');
    $this->setBrowserUrl(DRUPAL_BASE_URL);
  }

  private function saveScreenshot() {
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

  private function appendCurrentUrl(\Exception $e) {
    $messageProperty = new \ReflectionProperty(get_class($e), 'message');
    $messageProperty->setAccessible(TRUE);
    $messageProperty->setValue($e, "(url: {$this->url()}) {$messageProperty->getValue($e)}");
  }
}
