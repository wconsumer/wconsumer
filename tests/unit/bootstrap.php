<?php
error_reporting(E_ALL);

define('WC_BASE', dirname(dirname(__DIR__)));

// Setup autoloading
{
  if (!file_exists(WC_BASE.'/vendor/autoload.php')) {
    die('Composer not initialized.');
  }

  require_once( WC_BASE . '/vendor/autoload.php' );

  spl_autoload_register(function($class) {
    static $prefix = 'Drupal\\wconsumer\\Tests\\Unit';

    if (strpos($class, $prefix) === 0) {
      $class = substr($class, strlen($prefix));

      $filename = WC_BASE.'/tests/unit/'.str_replace('\\', "/", $class).'.php';
      if (file_exists($filename)) {
        require_once($filename);
      }
    }
  });
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
  die('PHPUnit is not installed from composer.');
}

