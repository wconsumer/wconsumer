<?php
/**
 * Web Consumer Manager
 *
 * Get started for PHPUnit
 */
define('WC_BASE', dirname(__DIR__));
define('WC_LIB_BASE', WC_BASE.'/lib');

require_once (WC_BASE.'/wconsumer.module');

// Let's see if they installed PHPUnit
if (! class_exists('PHPUnit_Framework_TestCase'))
  die('PHPUnit is not installed from composer.');

spl_autoload_register(function($class)
{
  $fileName = str_replace(trim('\ '), "/", $class);
  
  if (file_exists(WC_LIB_BASE.'/'.$fileName.'.php'))
    require_once (WC_LIB_BASE.'/'.$fileName.'.php');
});

/**
 * @ignore
 */
function module_invoke_all($hook, $default = NULL) {
  return $default;
}

/**
 * @ignore
 */
function url($path, $options = array()) {
  return $path;
}
