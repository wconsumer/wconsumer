<?php
namespace Drupal\wconsumer\Service\Exception;

use Drupal\wconsumer\Exception as WconsumerException;
use Drupal\wconsumer\Service\Service;


class ApiUnavailable extends WconsumerException {

  public function __construct(Service $service) {
    parent::__construct($this->message($service));
  }

  protected function message(Service $service) {
    return
      "{$service->getMeta()->niceName} features is not currently available. ".
      "Please try again later or contact with the site administrator.";
  }

  protected function connectLink(Service $service, $reConnect = false) {
    $url = check_plain(url('wconsumer/auth/'.rawurlencode($service->getName()), array('query' => drupal_get_destination())));
    $contents = ($reConnect ? "re-connect" : "connect") . " with {$service->getMeta()->niceName}";
    $link = "<a href=\"{$url}\">{$contents}</a>";
    return $link;
  }
}