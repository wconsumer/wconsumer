<?php
/**
 * @file
 * Web Consumer Authentication Interface
 */
namespace Drupal\wconsumer\Common;

/**
 * Authentication Interface
 *
 * Define the schema required to build out authentication methods.
 *
 * @package wconsumer
 * @subpackage request
 */
interface AuthInterface {
  public function formatRegistry($data);
  public function formatCredentials($data);
  public function is_initialized($type);
  public function sign_request(&$client);
  public function authenticate(&$user);
  public function logout(&$logout);

  // Callback
  public function onCallback(&$user, $values);
}
