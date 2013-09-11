<?php
use Drupal\wconsumer\Wconsumer;

/**
 * Frontend form user/1/wconsumer
 */
function _wconsumer_frontend_form($form, &$form_state) {
  $form['vertical_tabs_services'] = array(
    '#type' => 'vertical_tabs',
  );

  $services = Wconsumer::instance()->services;
  foreach ($services as $service) {
    /** @var \Drupal\wconsumer\Service\Base $service */

    if (!$service->isActive()) {
      continue;
    }

    $serviceName = $service->getName();

    $form[$serviceName] = array(
      '#type' => 'fieldset',
      '#title' => t(ucwords($serviceName)),
      '#tree' => TRUE,
      '#access' => $service->canAccess(),
      '#group'  => 'vertical_tabs_services',
    );

    $form[$serviceName]['action'] = array(
      '#type' => 'button',
      '#name' => $serviceName,
      '#value' => (
        !$service->checkAuthentication('user'))
          ? t('Authenticate ' . ucfirst($serviceName))
          : t('Revoke ' . ucfirst($serviceName)
      ),
      '#executes_submit_callback' => TRUE
    );
  }

  if (count($form) < 2) {
    $form['no-active-services'] = array('#markup' => t('There are no web services active to manage.'));
    unset($form['vertical_tabs_services']);
  }

  return $form;
}

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

  // Persist the url which we want back to in session
  if (!empty($_GET['destination'])) {
    drupal_set_message($_GET['destination'], 'wconsumer_back_to_url');
  }
  unset($_GET['destination']);

  global $user;
  $scopes = array_filter(module_invoke_all('wconsumer_define_required_scopes', $service));
  $service->authentication->authenticate($user, $scopes);
}

/**
 * POST user/%/wconsumer
 *
 * Callback for submitting the authentication form
 *
 * This is where we handle authenticating or authenticating a user
 * from a service.
 */
function _wconsumer_frontend_form_submit($form, &$form_state)
{
  global $user;

  $serviceName = $form_state['clicked_button']['#name'];

  /** @var \Drupal\wconsumer\Service\Base $service */
  $service = Wconsumer::instance()->services->{$serviceName};

  // Either authenticate them or log them out
  if ($service->checkAuthentication('user', $user->uid)) {
    $service->authentication->logout($user);
    drupal_set_message(t('Your '.$serviceName.' service has been revoked.'));
  }
  else {
    _wconsumer_frontend_auth($serviceName);
  }
}