<?php
namespace Drupal\wconsumer\IntegrationTests;

use Drupal\wconsumer\Authentication\HttpAuth\HttpAuth;
use Drupal\wconsumer\Service\Service;



/**
 * We need this class b/c we need an unique service name which is generated from class name to isolate the test case
 * in this file from others. See Service::$_instance variable for details.
 */
class TestService extends Service {
  protected $name = 'integration_tests_test_service';
  protected $apiUrl = 'http://url.example';



  public static function getClass() {
    return get_called_class();
  }

  protected function initAuthentication() {
    return new HttpAuth($this);
  }
}