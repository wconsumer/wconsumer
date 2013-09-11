<?php
use Drupal\wconsumer\Wconsumer;

/**
 * Frontend form user/1/wconsumer
 */
function _wconsumer_frontend_form($form, &$form_state) {
  $services = Wconsumer::instance()->services;
  foreach ($services as $name => $service) {
    /** @var \Drupal\wconsumer\Service\Base $service */

    if (!$service->isActive()) {
      continue;
    }

    $form[$name] = array(
      '#type' => 'fieldset',
      '#title' => t(ucwords($name)),
      '#tree' => TRUE,
      '#access' => $service->canAccess(),
    );

    $form[$name]['action'] = array(
      '#type' => 'button',
      '#name' => $name,
      '#value' => (
        !$service->checkAuthentication('user'))
          ? t('Authenticate ' . ucfirst($name))
          : t('Revoke ' . ucfirst($name)
      ),
      '#executes_submit_callback' => TRUE
    );
  }

  if (count($form) == 0) {
    $form['no-active-services'] = array(
      '#markup' => 'There are no web services active to manage.'
    );
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