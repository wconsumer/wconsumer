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

    // Add Web Manager Settings Here
    return $form;
  endif;

  try {
    $object = Service::getObject($service);
  } catch (Drupal\wconsumer\Exception $e) {
    drupal_set_message(t($e->getMessage()), 'error');
    return;
  }

  $form['creds'] = array(
      '#type' => 'fieldset',
      '#title' => t($service.' Application Credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

  switch(get_class($object->request)) :
    case 'Drupal\wconsumer\Rest\Request' :

      $form['creds']['consumer-key'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Key'),
        '#required' => TRUE,
      );

      $form['creds']['consumer-secret'] = array(
        '#type' => 'textfield',
        '#title' => t('Consumer Secret'),
        '#required' => TRUE,
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
