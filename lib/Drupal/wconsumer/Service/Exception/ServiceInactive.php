<?php
namespace Drupal\wconsumer\Service\Exception;

use Drupal\wconsumer\Service\Service;



class ServiceInactive extends ApiUnavailable {

  protected function message(Service $service) {
    return
      "{$service->getMeta()->niceName} service integration which is required ".
      "for this function to work is currently deactivated.";
  }
}
