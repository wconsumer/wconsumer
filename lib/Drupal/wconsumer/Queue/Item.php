<?php
namespace Drupal\wconsumer\Queue;

use Drupal\wconsumer\Service,
  Drupal\wconsumer\Queue\QueueException,
  Drupal\wconsumer\Queue\Manager;

/**
 * ORM Style Management of Items in the Queue
 *
 * @package wconsumer
 * @subpackage queue
 */
class Item {
  const STATUS_COMPLETE = 'complete';
  const STATUS_ERROR = 'error';
  const STATUS_PENDING = 'pending';
  const STATUS_QUEUE = 'queue';

  /**
   * Internal Information about the columns in the Queue table
   * 
   * @var array
   */
  protected $defaults = array(
    'request_id' => -1,
    'service' => -1,
    'request' => -1,
    'time' => 0,
    'response' => '',
    'status' => 'pending',
    'moderate' => 0,
    'approver_uid' => 0,
    'created_date' => 0
  );

  protected $items = NULL;

  /**
   * Table for Storage
   * 
   * @var string
   */
  protected $table = 'wc_requests';

  /**
   * Requests table in static
   * 
   * @var string
   */
  protected static $static_table = 'wc_requests';

  /**
   * Construct the Item from a data object
   *
   * @param object
   * @throws QueueException
   */
  public function __construct($data = NULL)
  {
    if ($data == NULL) :
      // They're inserting and wanting to create a new Item
      $this->items = (object) $this->defaults;

      return $this->items;
    endif;

    // They're taking a predefined role
    $this->items = new \stdClass;
    
    foreach((array) $data as $k => $v) :
      if (! isset($this->defaults[$k]))
        throw new QueueException('Unknown key passed to construct object: '.$k);

      if (($k == 'request' OR $k == 'response') AND $v !== '' AND $v !== NULL)
        $v = unserialize($v);
      
      $this->items->$k = $v;
    endforeach;

    $this->sanitizeLoading($this->items);
    return $this->items;
  }

  /**
   * Retrieve an Item by the ID
   * 
   * @param int The `request_id`
   * @return object|void
   */
  public static function find($id)
  {
    $data = db_select(self::$static_table)
      ->fields(self::$static_table)
      ->condition('request_id', $id, '=')
      ->execute()->fetchObject();

      if ($data == NULL) return NULL;

      // Setup the Object
      $object = new Item($data);

      return $object;
  }

  /**
   * Magic Method to SET the values
   *
   * @param string
   * @param mixed
   * @throws QueueException
   */
  public function __set($name, $value)
  {
    if ($this->items == NULL)
      throw new QueueException('Item object isn\'t instantiated.');

    if ($name == 'request' AND ! is_array($value))
      throw new QueueException('Request value must be in array format (not serialized)');

    $this->items->$name = $value;
  }

  /**
   * Magic Method to Retrieve the values
   *
   * @param string
   * @throws QueueException
   */
  public function __get($name)
  {
    if ($this->items == NULL)
      throw new QueueException('Item object isn\'t instantiated.');
    
    if (! isset($this->items->$name))
      return NULL; //throw new QueueException('Unknown column passed to item: '.$name);

    return $this->items->$name;
  }

  /**
   * Save the item
   *
   * @return object
   */
  public function save()
  {
    if ($this->items == NULL)
      throw new QueueException('Item object isn\'t instantiated.');

    if ((int) $this->items->request_id > 0) :
      $items = $this->sanitizeSaving(clone $this->items);

      // We don't want to overwrite the ID
      // or the creation date of the request
      unset($items['request_id]']);
      unset($items['created_date]']);

      // They're updating
      db_update($this->table)
        ->fields($items)
        ->condition('request_id', $this->request_id)
        ->execute();
    else :
      // Inserting
      $items = $this->sanitizeSaving(clone $this->items);

      unset($items['request_id']);
      $items['created_date'] = time();

      // Set the new insert ID
      $this->request_id = db_insert($this->table)
        ->fields($items)
        ->execute();
    endif;

    // Are we firing this?
    $check = $this->checkFire();
    if ($check == FALSE)
      return TRUE;
    else
      return $check;
  }

  /**
   * Add a Callback for the Item
   *
   * @param mixed
   * @throws QueueException
   */
  public function callback(&$callback) {
    if (! is_callable($callback))
      throw new QueueException('Callback for queue item isn\'t callable.');

    $this->request['callback'] = $callback;
  }

  /**
   * Sanitize Items for Saving
   *
   * @param object
   * @return array
   */
  public function sanitizeSaving($object)
  {
    foreach(array(
      'request'
    ) as $v) :
      if (is_object($object->$v) OR is_array($object->$v))
        $object->$v = serialize($object->$v);
    endforeach;

    if (is_object($object->response))
      $object->response = Manager::serializeResponse($object->response);
    else
      $object->response = NULL;

    $object->moderate = (int) $object->moderate;
    $object->approver_uid = (int) $object->approver_uid;
    $object->time = (int) $object->time;

    return (array) $object;
  }

  /**
   * Sanitize the Items for loading
   *
   * @param object Items bassed by reference
   */
  public function sanitizeLoading(&$object) {
    foreach(array(
      'request',
      'response',
    ) as $v) :
      if (is_string($object->$v))
        $object->$v = unserialize($object->$v);
    endforeach;
  }

  /**
   * Check to see if this request should be fired off
   * 
   * If it needs to be fired off, it will trigger that.
   * 
   * @return bool|object
   */
  private function checkFire() {
    if ($this->status == 'pending' AND $this->time < time())
      return $this->perform();

    return FALSE;
  }

  /**
   * Perform a Request
   *
   * @param boolean Force it
   */
  public function perform($force = FALSE)
  {
    // Already completed
    if ($this->status == 'completed' AND ! $force)
      return true;

    // Fire it off
    try {
      $object = Service::getObject($this->service);
    }
    catch (Drupal\wconsumer\Exception $e) {
      return;
    }

    // Determine some things about the request
    $request = $this->request;

    $request['http method'] = (isset($request['http method'])) ? strtolower($request['http method']) : 'get';
    $method = $request['http method'];

    $request['response format'] = (isset($request['response format'])) ? $request['response format'] : 'plaintext';

    foreach(array('headers', 'body') as $item)
      $request[$item] = (isset($request[$item])) ? $request[$item] : null;

    // Pass this off to the service's request object
    $this->response = Manager::prepareResponse($object->request->$method(
      $request['base'],
      $request['headers'],
      $request['body']
    ));

    // See HTTP header
    $this->status = ($this->response->isError()) ? self::STATUS_ERROR : self::STATUS_COMPLETE;

    // Save the Response
    $this->save();
    
    // Call the Item's callback
    if (isset($request['callback']))
      call_user_func_array($request['callback'], $this);
    
    return $this->response;
  }
}
