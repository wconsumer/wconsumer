<?php
/**
 * @file
 * Web Consumer Authentication Interface
 */
namespace Drupal\wconsumer\Common;

/**
 * Authentication Interface
 *
 * Define the schema required to build out authencation methods.
 *
 * @package wconsumer
 * @subpackage request
 */
interface AuthInterface {
  public function formatCredentials($data);
}
