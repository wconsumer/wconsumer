<?php
use Drupal\wconsumer\Service;

/**
 * Callback Form for the Web Consumer Form
 * Managing a service
 *
 * @access private
 * @param array
 * @param array The specific service (null means it's the default)
 * @return array
 */
function _wconsumer_ui_form($form, &$form_state, $service = NULL) {
  $form = array();

  if ($service == NULL) :
    // Default Menu
    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Web Consumer Settings'),
      '#collapsible' => false,
      '#collapsed' => FALSE,
    );

    $form['settings']['can service login'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable users to sign in with services'),
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

  try {
    $object = Service::getObject($service);
  } catch (Drupal\wconsumer\Exception $e) {
    drupal_set_message(t($e->getMessage()), 'error');
    return;
  }

  // Registry Settings
  $registry = $object->getRegistry();
  $credentials = (! $registry) ? FALSE : $registry->credentials;

  $form['creds'] = array(
      '#type' => 'fieldset',
      '#title' => t($service.' Application Credentials'),
      '#collapsible' => false,
      '#collapsed' => FALSE,
    );

  switch(get_class($object->request)) :
    case 'Drupal\wconsumer\Rest\Request' :

      $form['creds']['consumer_key'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Key'),
        '#required' => TRUE,
        '#value' => (isset($credentials['consumer_key'])) ? $credentials['consumer_key'] : '',
      );

      $form['creds']['consumer_secret'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Secret'),
        '#required' => TRUE,
        '#value' => (isset($credentials['consumer_secret'])) ? $credentials['consumer_secret'] : '',
      );

      break;

    case 'Drupal\wconsumer\Soap\Request' :

      break;

    default :
      die('Unknown');
  endswitch;

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration')
  );

  $form['service-name'] = array(
    '#type' => 'hidden',
    '#value' => $service,
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
function _wconsumer_ui_form_submit($form, &$form_state)
{
  $values = $form_state['input'];
  $service = (isset($values['service-name'])) ? $values['service-name'] : drupal_exit();

  if ($service == '_internal') :
    // Settings for the system
    
    return drupal_set_message(t('Settings updated.'));
  endif;

  try {
    $object = Service::getObject($service);
  }
  catch (\Drupal\wconsumer\Exception $e)
  {
    drupal_set_message(t('Unable to obtain service object: '.$service), 'error');
    watchdog('wconsumer_ui', 'Unable to obtain service object: '.$service, array($service), WATCHDOG_ERROR);

    return;
  }

  // Validate
  try {
    $credentials = $object->request->authencation->formatRegistry($values);
  } catch (\Drupal\wconsumer\Exception $e) {
    drupal_set_message($e->getMessage(), 'error');
    return;
  }

  // CRUD
  $object->setRegistry($credentials);

  drupal_set_message(t('Service Configuration Saved.'));
}

/**
 * Callback for the Service
 */
function _wconsumer_ui_callback($form, &$form_state, $service = NULL) {
  global $user;

  try {
    $object = Service::getObject($service);
    $object->request->authencation->onCallback($user, array($_REQUEST, $form_state['input']));
  }
  catch (\Drupal\wconsumer\Exception $e)
  {
    drupal_set_message(t('Unable to obtain service object: '.$service), 'error');
    watchdog('wconsumer_ui', 'Unable to obtain service object: '.$service, array($service), WATCHDOG_ERROR);

    return;
  }

  drupal_set_message('Credentials saved.', 'status');
  return drupal_goto('user/'.$user->uid.'/wconsumer');
}
