<?php
namespace Drupal\wconsumer\Service\Exception;

use Drupal\wconsumer\Service\Service;



class AdditionalScopesRequired extends ApiUnavailable {

  protected function message(Service $service) {
    return
      "We need additional permissions with your {$service->getMeta()->niceName} account. ".
      "Please {$this->connectLink($service, true)}.";
  }
}
