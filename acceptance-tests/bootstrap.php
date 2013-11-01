<?php

define('WC_BASE', dirname(__DIR__));
define('DRUPAL_ROOT', WC_BASE.'/../../../../');
define('SCREENSHOTS_DIR', WC_BASE.'/build/screenshots');

// Setup autoloading
{
  if (!file_exists(WC_BASE.'/vendor/autoload.php')) {
    die('Composer not initialized.');
  }

  require(WC_BASE.'/vendor/autoload.php');

  spl_autoload_register(function($class) {
    static $prefix = 'Drupal\\wconsumer\\AcceptanceTests\\';

    if (strpos($class, $prefix) === 0) {
      $class = substr($class, strlen($prefix));

      $filename = WC_BASE.'/acceptance-tests/'.str_replace('\\', "/", $class).'.php';
      if (file_exists($filename)) {
        require_once($filename);
      }
    }
  });

  require_once( WC_BASE . '/tests/integration/TestKeysContainer.php' );
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
  die('PHPUnit is not installed from composer.');
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once(DRUPAL_ROOT.'/includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

global $base_url;
define('DRUPAL_BASE_URL', $base_url);