<?php
  namespace Drupal\wconsumer\IntegrationTests;



  class TestKeysContainer {
    private $test;



    public function __construct(\PHPUnit_Framework_TestCase $test) {
      $this->test = $test;
    }

    /**
     * Returns senstivie data required for testing like passwords, keys, secrets etc.
     * See keys.dist.php for details.
     */
    public function get($section = NULL, $subsection = NULL, $subsubsection = NULL) {
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
        $this->test->markTestSkipped(
          'Test requires sensitive test data under ['.join('][', func_get_args()).'] '.
          'section of keys.php which is not set'
        );
      }

      return $result;
    }
  }
?>