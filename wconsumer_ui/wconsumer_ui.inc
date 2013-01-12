<?php
function _wconsumer_ui_form($form, &$form_state) {
  $form['description'] = array(
    '#type' => 'item', 
    '#title' => t('A form with a submit handler'),
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
