<?php
use Drupal\wconsumer\Wconsumer;



function _wconsumer_ui_form($form, &$form_state, $serviceName = NULL) {
  $form = array();

  if (!isset($serviceName)) {
    $form['dummy'] = array(
      '#markup' => t('Please click on a tab above to configure an individual service'),
    );

    return $form;
  }

  $service = Wconsumer::instance()->services->{$serviceName};

  $tokenLabel = 'Username';
  $secretLabel = 'Password';
  $tokenPredefined = false;

  $authencation = $service->authentication;

  if ($authencation instanceof \Drupal\wconsumer\Authentication\Oauth\Oauth ||
      $authencation instanceof \Drupal\wconsumer\Authentication\Oauth2\Oauth2) {
    $tokenLabel = 'Consumer Key';
    $secretLabel = 'Consumer Secret';
  }
  elseif ($authencation instanceof \Drupal\wconsumer\Authentication\QueryString\QueryString) {
    $tokenLabel = $authencation->keyLabel;
    $secretLabel = $authencation->valueLabel;

    if (isset($authencation->queryKey)) {
      $tokenPredefined = true;
    }
  }

  $credentials = $service->getServiceCredentials();
  $token = (isset($credentials) ? $credentials->token : null);
  $secret = (isset($credentials) ? $credentials->secret : null);

  $form['creds'] = array(
      '#type' => 'fieldset',
      '#title' => t($serviceName.' Application Credentials'),
      '#collapsible' => false,
      '#collapsed' => FALSE,
    );

  if (!$tokenPredefined) {
    $form['creds']['token'] = array(
      '#type' => 'textfield',
      '#title' => t($tokenLabel),
      '#required' => TRUE,
      '#default_value' => $token,
    );
  }
  else {
    $form['creds']['token'] = array(
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => (isset($token) ? $token : 'dummy'),
    );
  }

  $form['creds']['secret'] = array(
    '#type' => 'textfield',
    '#title' => t($secretLabel),
    '#required' => TRUE,
    '#default_value' => $secret,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration')
  );

  $form['service-name'] = array(
    '#type' => 'hidden',
    '#value' => $serviceName,
  );

  return $form;
}

/**
 * POST Callback for the Service Update Form
 *
 * @param object
 * @param object
 * @access private
 */
function _wconsumer_ui_form_submit($form, &$form_state) {
  $values = $form_state['input'];
  if (!isset($values['service-name'])) {
    drupal_exit();
  }

  $serviceName = $values['service-name'];
  $service = Wconsumer::instance()->services->get($serviceName);
  if (!isset($service)) {
    drupal_set_message(t('Unable to obtain service object: '.$serviceName), 'error');
    watchdog('wconsumer_ui', 'Unable to obtain service object: '.$serviceName, array($serviceName), WATCHDOG_ERROR);
    return;
  }

  // Validate
  try {
    $credentials = \Drupal\wconsumer\Authentication\Credentials::fromArray($values);
  } catch (\Drupal\wconsumer\Exception $e) {
    drupal_set_message($e->getMessage(), 'error');
    return;
  }

  // CRUD
  $service->setServiceCredentials($credentials);

  drupal_set_message(t('Service Configuration Saved.'));
}

/**
 * Callback for the Service
 */
function _wconsumer_ui_callback($serviceName = NULL) {
  global $user;

  $service = Wconsumer::instance()->services->get($serviceName);
  if (!isset($service)) {
    drupal_set_message(t('Unable to obtain service object: '.$serviceName), 'error');
    watchdog('wconsumer_ui', 'Unable to obtain service object: '.$serviceName, array($serviceName), WATCHDOG_ERROR);
    return;
  }

  $service->authentication->onCallback($user, array($_REQUEST));

  if ($back_to_urls = drupal_get_messages('wconsumer_back_to_url')) {
    $_GET['destination'] = end($back_to_urls['wconsumer_back_to_url']);
  }

  drupal_goto();
}