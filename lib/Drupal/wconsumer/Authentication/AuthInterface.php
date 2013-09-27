<?php
namespace Drupal\wconsumer\Authentication;

use Guzzle\Http\Client;



interface AuthInterface {
  public function signRequest(Client $client, $user = NULL);
  public function authenticate($user, array $scopes = array());
  public function logout($user);
  public function onCallback($user, $values);
  public function validateServiceCredentials(Credentials $credentials);
}