<?php
namespace Drupal\wconsumer\Common;
use Drupal\wconsumer\Exception as WcException;

/**
 * Mapping Data from the Service to Information about the User
 *
 * @package wconsumer
 */
class Mapping {
  private $fields = array();
  private $instance;

  /**
   * Setup the Mapper
   * 
   * @param object Instance of Service Object
   */
  public function __construct(&$instance)
  {
    $this->instance = $instance;
  }

  /**
   * Register a Field to be Mapped
   * 
   * For documentation on this, see the wiki
   * 
   * @param string Field Name
   * @param array Data pertaining to where the field is located
   * @return void
   */
  public function register($field, $data_location)
  {
    $this->fields[$field] = (array) $data_location;
  }

  /**
   * Bulk Register Fields
   *
   * @param array
   * @return void
   */
  public function register($fields)
  {
    foreach($fields as $k => $v)
      $this->register($k, $v);
  }

  /**
   * Retrieve a Mapped Field from the Service
   *
   * @param string
   * @throws Drupal\wconsumer\Exception
   */
  public function retrieve($field) {
    if (! isset($this->fields[$field]))
      throw new WcException(printf('Field %a isn\'t registered to be mapped.'));

    // Let's process this
    $fieldData = $this->fields[$field];

    if (! isset($fieldData['endpoint']))
      throw new WcException(printf('Endpoint for %a field isn\'t registered for %b', $field, $this->instance->getName()));

    $endpoint = $fieldData['endpoint'];
    $http_method = (isset($fieldData['http method'])) ? $fieldData['http method'] : 'get';

    // Response format
    if (! isset($fieldData['response interperter']) AND ! isset($fieldData['response format']))
      throw new WcException(printf('No response interperter/format specified for %a', $field));

    
  }
}
