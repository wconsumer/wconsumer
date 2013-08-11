<?php

define('WC_BASE', dirname(__DIR__));
define('WC_LIB_BASE', WC_BASE.'/lib');

// Let's see if they initialized Composer
if (!file_exists(WC_BASE.'/vendor/autoload.php')) {
  die('Composer not initialized.');
}
require(WC_BASE.'/vendor/autoload.php');

// Let's see if they installed PHPUnit
if (!class_exists('PHPUnit_Framework_TestCase')) {
  die('PHPUnit is not installed from composer.');
}

require_once(__DIR__.'/DrupalTestBase.php');
require_once(__DIR__.'/TestService.php');
require_once(__DIR__.'/ServiceBaseTest.php');
require_once(__DIR__.'/Rest/Authentication/AuthenticationTest.php');

define('DRUPAL_ROOT', __DIR__.'/../../../../..');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

global $base_url;
$base_url = 'http://example.invalid';