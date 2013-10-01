<?php
use Drupal\wconsumer\Wconsumer;
use Drupal\wconsumer\Service\Base as Service;
use Drupal\wconsumer\Authentication\Oauth\Oauth;
use Drupal\wconsumer\Authentication\Oauth2\Oauth2;



function _wconsumer_ui_form($form, &$form_state, $serviceName = NULL) {
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
  $meta = $service->getMeta();

  if (strpos($service->callback(), 'https://') === 0) {
    _wconsumer_ui_form_check_https_support($meta->niceName);
  }

  $tokenLabel = $meta->consumerKeyLabel;
  $secretLabel = $meta->consumerSecretLabel;

  $tokenPredefined =
      $service->authentication instanceof \Drupal\wconsumer\Authentication\QueryString\QueryString &&
      isset($service->authentication->queryKey);

  $credentials = $service->getServiceCredentials();
  $token =  (isset($credentials) ? $credentials->token  : NULL);
  $secret = (isset($credentials) ? $credentials->secret : NULL);

  $form['creds'] = array(
    '#type' => 'fieldset',
    '#title' => t($serviceName.' Application Credentials'),
    '#collapsible' => FALSE,
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
    {
      $sentences[] =
        'Use "!callbackUrl" (without quotes) as the Callback URL when registering your app (this field might
         also be called Redirect URL or Success Callback URL)';

      // It would be better to specify base website url as the callback. However, some of the services like
      // Google require callback url to be exactly matching to the one specified in app settings. So we'd better
      // use full callback url for all services rather then varying it for some of them.
      $callbackUrl = $service->callback();

      $callbackUrl = check_plain($callbackUrl);
      $callbackUrl = preg_replace('|^(https://)|', '<b>\1</b>', $callbackUrl);

      $fields['!callbackUrl'] = $callbackUrl;
    }

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

function _wconsumer_ui_form_check_https_support($service) {
  global $base_url;

  drupal_set_message(t('
      HTTPS support required to make @service work. Please make sure your Drupal installation
      is able to handle HTTPS request before you enable this service. In order to do this you have to
      <ol>
        <li>install SSL certficate,</li>
        <li>enable HTTPS core support in settings.php by providing <code>$conf[\'https\'] = TRUE;</code> there</li>
        <li>install <a href="https://drupal.org/project/securepages" target="_blank">Secure Pages</a> module (beta or stable version recommended).</li>
      </ol>
      Read more <a href="https://drupal.org/https-information" target="_blank">here</a>.
  ', array(
    '@service' => $service,
  )));

  if (!variable_get('https', FALSE)) {
    drupal_set_message(t('
      It seems this Drupal installation does not support HTTPS. The reason why do we think so is that HTTPS
      support is not enabled in settings.php.
    '), 'warning');
    return;
  }

  if (strpos($base_url, 'https://') !== 0) {
    $client = new \Guzzle\Service\Client();

    $url_is_accessible = function($url) use($client) {
      $response = null;
      $exception = null;

      try {
        $response = $client->get($url)->send();
      }
      catch (\Exception $e) {
        $exception = $e;
      }

      return (!$exception && $response->isSuccessful());
    };

    // Make sure we are able to ping this website at all even via HTTP.
    if (!$url_is_accessible($base_url)) {
      drupal_set_message(t('Can\'t check HTTPS support state'), 'warning');
      return;
    }

    // Check if we are able to ping it via HTTPS.
    $https_base_url = preg_replace('|^http://|i', 'https://', $base_url);
    if (!$url_is_accessible($https_base_url)) {
      drupal_set_message(t('
        It seems this Drupal installation does not support HTTPS. The reason why do we think so is that this website
        is not accessible via https:// link. You can try it on your own:
        <a href="@https_base_url" target="_blank">@https_base_url</a>.
      ', array(
        '@https_base_url' => $https_base_url,
      )), 'warning');
      return;
    }
  }

  drupal_set_message(t('HTTPS is currently supported'));
}

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
    drupal_set_message(t($e->getMessage()), 'error');
    return;
  }

  if (!$service->validateServiceCredentials($credentials)) {
    drupal_set_message(t('The service reports that the new credentials are not valid. Please double-check them.'), 'error');
    return;
  }

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