<?php
namespace Drupal\wconsumer\Common;

class Options
{
  protected $options = array();

  /**
   * @param string Key
   * @param mixed Value
   */
  public function set($key, $value = '')
  {
    $this->options[$key] = $value;
  }

  /**
   * Retrieve an option
   *
   * @param string
   * @return mixed
   */
  public function retrieve($key)
  {
    if (! isset($this->options[$key]))
      return NULL;

    return $this->options[$key];
  }

  /**
   * Check if an option is enabled
   * 
   * @param string
   * @return value
   */
  public function enabled($key)
  {
    return (isset($this->options[$key]) AND $this->options) ? TRUE : FALSE;
  }

  public function enable($key)
  {
    return $this->set($key, TRUE);
  }

  public function disable($key)
  {
    return $this->set($key, FALSE);
  }
}
