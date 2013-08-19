<?php
namespace Drupal\wconsumer\Tests;

use Drupal\wconsumer\Service\Base;


/**
 * @ignore
 */
class FooService extends Base {
}

/**
 * @ignore
 */
class FooServiceWithName extends Base {
  protected $name = 'specialservice';
}
