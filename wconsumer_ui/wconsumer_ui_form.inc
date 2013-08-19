<?php
use Drupal\wconsumer\Wconsumer;

/**
 * Callback Form for the Web Consumer Form
 * Managing a service
 *
 * @access private
 * @param array
 * @param array The specific service (null means it's the default)
 * @return array
 */
function _wconsumer_ui_form($form, &$form_state, $serviceName = NULL) {
  $form = array();

  if ($serviceName == NULL) :
    $service_login = (int) variable_get('wconsumer:service_login');

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Web Consumer Settings'),
      '#collapsible' => false,
      '#collapsed' => FALSE,
    );

    $form['settings']['service-login'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable users to sign in with services'),
      '#default_value' => $service_login,
      '#return_value' => 1,
      //'#value' => $service_login,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Configuration')
    );

    $form['service-name'] = array(
      '#type' => 'hidden',
      '#value' => '_internal',
    );

    // Add Web Manager Settings Here
    return $form;
  endif;

  $service = Service::getObject($serviceName);

  $tokenLabel = 'Username';
  $secretLabel = 'Password';
  $tokenPredefined = false;

  $authencation = $service->request->authencation;

  if ($authencation instanceof \Drupal\wconsumer\Rest\Authentication\Oauth\Oauth ||
      $authencation instanceof \Drupal\wconsumer\Rest\Authentication\Oauth2\Oauth2) {
    $tokenLabel = 'Consumer Key';
    $secretLabel = 'Consumer Secret';
  }
  elseif ($authencation instanceof \Drupal\wconsumer\Rest\Authentication\QueryString\QueryString) {
    $tokenLabel = $service->request->authencation->keyLabel;
    $secretLabel = $service->request->authencation->valueLabel;

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

  if ($serviceName == '_internal') :
    // Settings for the system
    variable_set('wconsumer:service_login', $form_state['values']['service-login']);
    drupal_set_message(t('Settings updated.'));
    return;
  endif;

  $service = Wconsumer::instance()->services->get($serviceName);
  if (!isset($service)) {
    drupal_set_message(t('Unable to obtain service object: '.$serviceName), 'error');
    watchdog('wconsumer_ui', 'Unable to obtain service object: '.$serviceName, array($serviceName), WATCHDOG_ERROR);
    return;
  }

  // Validate
  try {
    $credentials = \Drupal\wconsumer\Rest\Authentication\Credentials::fromArray($values);
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

  drupal_set_message('Credentials saved.', 'status');
  drupal_goto('user/'.$user->uid.'/wconsumer');
}
