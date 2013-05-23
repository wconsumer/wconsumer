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
  private $validResponseFormats = array('json', 'xml');

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
  public function bulkRegister($fields)
  {
    foreach($fields as $k => $v)
      $this->register($k, $v);
  }

  /**
   * Retrieve a Mapped Field from the Service
   *
   * @param string
   * @throws Drupal\wconsumer\Exception
   * @return mixed
   */
  public function getField($field) {
    if (! isset($this->fields[$field]))
      throw new WcException(sprintf('Field %a isn\'t registered to be mapped.'));

    // Let's process this
    $fieldData = $this->fields[$field];

    if (! isset($fieldData['endpoint']))
      throw new WcException(sprintf('Endpoint for %a field isn\'t registered for %b', $field, $this->instance->getName()));

    $endpoint = $fieldData['endpoint'];
    $http_method = (isset($fieldData['http method'])) ? $fieldData['http method'] : 'get';

    // Response format
    if (! isset($fieldData['response interperter']) AND ! isset($fieldData['response format']))
      throw new WcException(sprintf('No response interperter/format specified for %a', $field));

    if (isset($fieldData['response format']) AND ! in_array($fieldData['response format'], $this->validResponseFormats))
      throw new WcException(sprintf('Unknown response format passed: %1', $fieldData['respone format']));

    // Start the request
    $item = $this->instance->newQueueItem();
    $item->request = array(
      'base' => $endpoint,
      'params' => (isset($fieldData['params'])) ? $fieldData['params'] : array()
    );
    $item->time = 0;
    $item->save();

    // Guzzle Object
    $response = $item->response;

    // Error in the Guzz!
    if ($response->isError())
      throw new WcException( sprintf('%f field threw error on request response: HTTP code %c', $field, $response->getStatusCode()) );

    if (! isset($fieldData['response interperter']) AND isset($fieldData['response format'])) :
      $format = $fieldData['response format'];
        
      // Field Location
      if (! isset($fieldData['field location']))
        throw new WcException('Field location not specified');

      return $this->interpertResponse($response->$format(), $fieldData['field location']);
    else :
      return call_user_func_array($fieldData['response interperter'], array(
        $response,
        $fieldData
      ));
    endif;
  }

  /**
   * Recursive Internal Response Formatter
   *
   * Will be used by default if a `response interperter` isn't passed to the mapper
   *
   * @access private
   * @param object Response data already parsed
   * @param array Field Location
   */
  protected function interpertResponse($responseParsed, $fieldLocation)
  {
    // We've got it!
    if (! is_array($fieldLocation) ) :
      return $responseParsed[$fieldLocation];

    // It's an array meaning that the field is on a location inside of an area on the array
    else :
      $value = reset($fieldLocation);
      $key = key($fieldLocation);

      if (is_array($value))
        return $this->interpertResponse($responseParsed[$key], $value);
      else
        return $responseParsed[$key][$value];
    endif;
  }
}
