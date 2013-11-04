<?php
define('WC_BASE', dirname(__DIR__));
define('SCREENSHOTS_DIR', WC_BASE.'/build/screenshots');

// Setup autoloading
{
  if (!file_exists(WC_BASE.'/vendor/autoload.php')) {
    die('Composer not initialized.');
  }

  require_once(WC_BASE.'/vendor/autoload.php');

  spl_autoload_register(function($class) {
    static $prefix = 'Drupal\\wconsumer\\Tests';

    if (strpos($class, $prefix) === 0) {
      $class = substr($class, strlen($prefix));

      $filename = WC_BASE.'/tests/'.str_replace('\\', "/", $class).'.php';
      if (file_exists($filename)) {
        require_once($filename);
      }
    }
  });
}

if (!class_exists('PHPUnit_Framework_TestCase')) {
  die('PHPUnit is not installed from composer.');
}

// Save previously generated coverage report from being overwritten.
// This is for CI server env tests run to be able to merge multiple coverage reports. It also assumed that
// every build is starting from scratch with empty coverage directory which is true for CI server. This does not make
// much sense for local/dev testing.
call_user_func(function() {
  if (is_file($config = __DIR__.'/phpunit.xml.dist')) {
    $time = date('Ymd-His');
    $xml = simplexml_load_file($config);
    foreach (@$xml->{'logging'}->{'log'} as $log) {
      if (($target = (string)@$log['target']) && file_exists($target = dirname($config).'/'.$target)) {
        rename($target, "{$target}-{$time}");
      }
    }
  }
});

// Run testsuite-specific boostrap.php
global $argv;
if (preg_match('/phpunit(\.(php|phar|bat))?$/', reset($argv))) {
  $dir = end($argv);
  do {
    $bootstrap = $dir.'/bootstrap.php';
    if (is_file($bootstrap)) {
      require_once($bootstrap);
      break;
    }
  } while ($dir = dirname($dir));
}