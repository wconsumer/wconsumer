<?php
require_once(__DIR__ . '/bootstrap.php');

define('DRUPAL_ROOT', WC_BASE.'/../../../..');

require_once(DRUPAL_ROOT.'/includes/bootstrap.inc');

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);