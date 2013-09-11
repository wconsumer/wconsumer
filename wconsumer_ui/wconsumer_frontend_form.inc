<?php
use Drupal\wconsumer\Wconsumer;



function _wconsumer_frontend_auth($service_name) {
  $service = Wconsumer::instance()->services->get($service_name);
  if (!isset($service)) {
    drupal_set_message(t("{$service_name} service is not currently available"));
    drupal_goto('<front>');
  }

  if (!$service->isActive()) {
    drupal_set_message(t("'{$service->getName()}' service is not currently active"));
    drupal_goto('<front>');
  }

  _wconsumer_ui_save_return_url();

  global $user;
  $scopes = array_filter(module_invoke_all('wconsumer_define_required_scopes', $service));
  $service->authentication->authenticate($user, $scopes);
}

function _wconsumer_frontend_revoke($service_name) {
  $service = Wconsumer::instance()->services->get($service_name);
  if (!isset($service)) {
    drupal_set_message(t("{$service_name} service is not currently available"));
    drupal_goto('<front>');
  }

  global $user;
  $service->authentication->logout($user);
  drupal_set_message(t('Your @service service has been revoked.', array('@service' => $service->getName())));

  drupal_goto();
}