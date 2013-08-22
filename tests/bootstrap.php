<?php
error_reporting(E_ALL);

define('WC_BASE', dirname(__DIR__));
define('WC_LIB_BASE', WC_BASE.'/lib');

require_once (WC_BASE.'/wconsumer.module');

// Let's see if they initialized Composer
if (!file_exists(WC_BASE.'/vendor/autoload.php'))
  die('Composer not initialized.');

require(WC_BASE.'/vendor/autoload.php');
require(__DIR__.'/TestService.php');

// Let's see if they installed PHPUnit
if (! class_exists('PHPUnit_Framework_TestCase'))
  die('PHPUnit is not installed from composer.');

spl_autoload_register(function($class)
{
  $fileName = str_replace(trim('\ '), "/", $class);

  if (file_exists(WC_LIB_BASE.'/'.$fileName.'.php'))
    require_once (WC_LIB_BASE.'/'.$fileName.'.php');
});