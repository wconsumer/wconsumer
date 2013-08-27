<?php
namespace Drupal\wconsumer\Authentication;

use Guzzle\Http\Client;


/**
 * Authentication Interface
 *
 * Define the schema required to build out authentication methods.
 *
 * @package wconsumer
 * @subpackage request
 */
interface AuthInterface {
  public function signRequest(Client $client, $user = NULL);
  public function authenticate($user, array $scopes = array());
  public function logout($user);
  public function onCallback($user, $values);
}