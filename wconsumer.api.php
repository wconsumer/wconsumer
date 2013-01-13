<?php
  function wconsumer_init() {
  
    $service = module_invoke_all('wc_config', $service);
    print_r($service);
    drupal_set_message('<pre>' . print_r($service, TRUE) . '</pre>';
}
