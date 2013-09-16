<?php
use Drupal\wconsumer\Wconsumer;
use Drupal\wconsumer\Service\Base as Service;
use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



function _wconsumer_ui_form($form, &$form_state, $serviceName = NULL) {
  $form = array();

  if (!isset($serviceName)) {
    $form['dummy'] = array(
      '#markup' => t('Please click on a tab above to configure an individual service.'),
    );

    return $form;
  }

  $usesOauth = function(Service $service) {
    return
      $service->authentication instanceof Oauth ||
      $service->authentication instanceof Oauth2;
  };

  $service = Wconsumer::instance()->services->{$serviceName};

  $tokenLabel = 'Username';
  $secretLabel = 'Password';
  $tokenPredefined = false;

  $authencation = $service->authentication;

  if ($usesOauth($service)) {
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

  if ($usesOauth($service)) {
    $form['creds']['note'] = _wconsumer_ui_form_note($service);
  }

  $form['creds']['enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable service'),
    '#default_value' => $service->isEnabled(),
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

function _wconsumer_ui_form_note(Service $service) {
  $sentences = array();
  $fields = array();
  {
    // register an application
    $registerAppUrl = $service->getMeta()->registerAppUrl;
    if (isset($registerAppUrl)) {
      $sentences[] =
        'In order to use this service you have to
         <a href="@registerUrl" target="_blank">register an application</a>';

      $fields['@registerUrl'] = $registerAppUrl;
    }
    else {
      $sentences[] = 'In order to use this service you have to register an application';
    }

    // use callback uri
    global $base_url;
    $sentences[] =
      'Use "@callbackUrl" (without quotes) as the Callback URL when registering your app (this field might
       also be called Redirect URL or Success Callback URL)';
    $fields['@callbackUrl'] = $base_url;

    // callback uri change note
    if ($service->authentication instanceof Oauth) {
      $sentences[] = 'Actually, you can change it to anything you want if you need it for some reason';
    }
    else {
      $sentences[] =
        'Please do not change it unless you know what you do because most likely it will
        make the service inaccessible';
    }

    // fill in credentials
    $sentences[] = 'Once you created your app come back here and fill in app credentials you\'ve got';
  }

  foreach ($sentences as &$sentence) {
    $sentence = t($sentence, $fields).'. ';
  }

  return array(
    '#markup' => join('', $sentences).'<br /><br />',
  );
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

  $service->setEnabled((bool)$values['enabled']);

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
  drupal_set_message(t(
    "Your @service account is now linked with your local account.",
    array('@service' => $serviceName))
  );

  _wconsumer_ui_goto();
}