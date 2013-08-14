<?php
use Drupal\wconsumer\Wconsumer;

/**
 * Frontend form user/1/wconsumer
 */
function _wconsumer_frontend_form($form, &$form_state)
{
  $services = Wconsumer::instance()->services;

  // If they don't have any services active
  if (count($services) == 0) :
    $form['no-active-services'] = array(
          '#markup' => 'There are no Web Consumer Services active to manage.'
        );
    return $form;
  endif;

  foreach ($services as $name => $service) {
    $form[$name] = array(
      '#type' => 'fieldset',
      '#title' => t(ucwords($name)),
      '#tree' => TRUE,
      '#access' => $service->canAccess(),
    );

    // If they are setup?
    if ($service->checkAuthentication('system')) :
      // Show who they are
      /*
      if (isset($service['userInfoItem'])) {
        if (function_exists($service['userInfoItem'])):
          $string = call_user_func_array($service['userInfoItem'], array(
            wc_check_service_auth($name)
          ));
        else:
        // Manually get their info
        endif;
        $form[$name]['account-info'] = array(
          '#markup' => $string
        );
      }
      */
      $form['service-name'] = array(
        '#type' => 'hidden',
        '#value' => $name
      );
      $form[$name]['action'] = array(
        '#type' => 'button',
        '#name' => $name,
        '#value' => (!$service->checkAuthentication('user'))
          ? t('Authenticate ' . ucfirst($name))
          : t('Revoke ' . ucfirst($name)),
        '#executes_submit_callback' => TRUE
      );
    else:
    // Not setup yet
      $form[$name]['markup'] = array(
        '#markup' => 'The site administrator hasn\'t setup this service yet so users may not authenticate with it.'
      );
    endif;

  }
  return $form;
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
  $service = Wconsumer::instance()->services[$serviceName];

  // Either authenticate them or log them out
  if ($service->checkAuthentication('user', $user->uid)) {
    // Log them out
    $service->request->authencation->logout($user);
    drupal_set_message(t('Your '.$serviceName.' service has been revoked.'));
  }
  else {
    $service->request->authencation->authenticate($user);
  }
}
