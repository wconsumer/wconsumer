<?php
namespace Drupal\wconsumer\Service\Exception;



use Drupal\wconsumer\Service\Service;

class NoUserCredentials extends ApiUnavailable {

  protected function message(Service $service) {
    return "Please {$this->connectLink($service)}.";
  }
}
