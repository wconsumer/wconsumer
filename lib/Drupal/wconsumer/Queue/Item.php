<?php
namespace Drupal\wconsumer\Queue;

use Drupal\wconsumer\Exception as WcException;

/**
 * ORM Style Management of Items in the Queue
 *
 * @package wconsumer
 * @subpackage queue
 */
class Item {
  /**
   * Internal Information about the columns in the Queue table
   * 
   * @var array
   */
  private $defaults = array(
    'request_id' => -1,
    'service_id' => -1,
    'request' => -1,
    'time' => 0,
    'response' => '',
    'status' => 'pending',
    'moderate' => 0,
    'approver_uid' => 0,
    'created_date' => 0
  );

  private $items = NULL;

  /**
   * Table for Storage
   * 
   * @var string
   */
  protected $table = 'wc_requests';
  protected static $static_table = 'wc_requests';

  /**
   * Construct the Item from a data object
   *
   * @param object
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

    foreach($data as $k => $v) :
      if (! isset($this->defaults[$k]))
        throw new WcException('Unknown key passed to construct object: '.$k);

      if ($k == 'request' OR $k == 'response' AND $v !== '' AND $v !== NULL)
        $v = unserialize($v);
      
      $this->items->$k = $v;
    endforeach;
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
      ->condition('request_id', $id)
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
   */
  public function __set($name, $value)
  {
    if ($this->items == NULL)
      throw new WcException('Item object isn\'t instantiated.');

    $this->items->$name = $value;
  }

  /**
   * Magic Method to Retrieve the values
   *
   * @param string
   */
  public function __get($name)
  {
    if ($this->items == NULL)
      throw new WcException('Item object isn\'t instantiated.');
    
    if (! isset($this->items->$name))
      throw new WcException('Unknown column passed to item: '.$name);

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
      throw new WcException('Item object isn\'t instantiated.');

    if ((int) $this->items->request_id > 0) :
      $items = $this->items;
      $this->sanitize($items);

      // We don't want to overwrite the request ID
      unset($items->request_id);
      unset($items->created_date);

      // They're updating
      return db_update($this->table)
        ->fields((array) $items)
        ->condition('request_id', $this->request_id)
        ->execute();
    else :
      // Inserting
      $items = $this->items;
      $this->sanitize($items);

      unset($items->request_id);
      $items->created_date = time();

      $item = db_insert($this->table)
        ->fields((array) $items)
        ->execute();

        // Set the new insert ID
        $this->request_id = $item;

        return $item;
    endif;
  }

  /**
   * Sanitize Items for Saving
   *
   * @param object Passed by reference
   */
  public function sanitize(&$object)
  {
    foreach(array(
      'request',
      'response',
    ) as $v) :
      if (is_object($object->$v) OR is_array($object->$v))
        $object->$v = serialize($object->$v);
    endforeach;

    $object->service_id = (int) $object->service_id;
    $object->moderate = (int) $object->moderate;
    $object->approver_uid = (int) $object->approver_uid;
  }
}
