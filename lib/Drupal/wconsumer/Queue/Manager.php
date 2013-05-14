<?php
namespace Drupal\wconsumer\Queue;

/**
 * Manage the Queue and the requests that go though it
 *
 * @package wconsumer
 * @subpackage queue
 */
class Manager {
  public static function checkScheduled() {
    $query = db_select('wc_requests')
      ->condition('time', time(), '<')
      ->fields('wc_requests')
      ->execute();


    // No pending requests
    if (! $query) return;

    foreach($query as $data) :
      echo 'Performing Web Consumer request #'.$data->request_id.'...'.PHP_EOL;
      $item = new Item($data);
      $item->perform();
    endforeach;
  }
}
