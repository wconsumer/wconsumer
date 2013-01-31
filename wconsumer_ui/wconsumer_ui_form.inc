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

  $form['description'] = array(
    '#type' => 'item',
    '#title' => t('Service Management: '.ucwords($service)),
  );

  $form['name'] = array(
    '#type' => 'fieldset',
    '#title' => t('Name'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['name']['first'] = array(
    '#type' => 'textfield',
    '#title' => t('First name'),
    '#required' => TRUE,
    '#default_value' => "First name",
    '#description' => "Please enter your first name.",
    '#size' => 20,
    '#maxlength' => 20,
  );
  $form['name']['last'] = array(
    '#type' => 'textfield',
    '#title' => t('Last name'),
    '#required' => TRUE,
  );
  $form['year_of_birth'] = array(
    '#type' => 'textfield',
    '#title' => "Year of birth",
    '#description' => 'Format is "YYYY"',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit',
  );
  return $form;
}
