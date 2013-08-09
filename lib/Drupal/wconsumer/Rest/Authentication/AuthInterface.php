<?php
/**
 * @file
 * Web Consumer Authentication Interface
 */
namespace Drupal\wconsumer\Rest\Authentication;

/**
 * Authentication Interface
 *
 * Define the schema required to build out authentication methods.
 *
 * @package wconsumer
 * @subpackage request
 */
interface AuthInterface {
  public function isInitialized($type, $user = NULL);
  public function signRequest($client, $user = NULL);
  public function authenticate($user);
  public function logout($user);
  public function onCallback($user, $values);
}
