<?php
use Drupal\wconsumer\Service\Base as Service;
use Drupal\wconsumer\Service\Github;


function hook_wconsumer_define_required_scopes(Service $service) {
  if ($service instanceof Github) {
    return array('user:email', 'gists');
  }

  return null;
}