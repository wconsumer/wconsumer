<?php
namespace Drupal\wconsumer\Rest;

/**
 * REST Request Class
 *
 * @package wconsumer
 * @subpackage request
 */
class Request
{
  /**
   * API Base URL
   * @var string
   */
  public $apiURL;

  /**
     * Call this method to get singleton
     *
     * @return UserFactory
     */
    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new Request();
        }
        return $inst;
    }
}
