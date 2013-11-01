<?php

define('WC_BASE', dirname(dirname(__DIR__)));

// Setup autoloading
{
  if (!file_exists(WC_BASE.'/vendor/autoload.php')) {
    die('Composer not initialized.');
  }

  require( WC_BASE . '/vendor/autoload.php' );

  spl_autoload_register(function($class) {
    static $prefix = 'Drupal\\wconsumer\\Tests\\Integration\\';

    if (strpos($class, $prefix) === 0) {
      $class = substr($class, strlen($prefix));

      $filename = WC_BASE.'/tests/integration/'.str_replace('\\', "/", $class).'.php';
      if (file_exists($filename)) {
        require_once($filename);
      }
    }
  });
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
  die('PHPUnit is not installed from composer.');
}

// Setup Drupal
{
  define('DRUPAL_ROOT', __DIR__.'/../../../../../..');
  require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

  global $base_url;
  $base_url = 'http://example.invalid';
}