<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorageInterface;

/**
 * Represents the default configuration storage object.
 */
class DrupalConfig {

  /**
   * The storage engine to save this config object to.
   *
   * @var DrupalConfigVerifiedStorageInterface
   */
  protected $_verifiedStorage;

  protected $data = array();

  /**
   * Constructs a DrupalConfig object.
   *
   * @param DrupalConfigVerifiedStorageInterface $verified_storage
   *   The storage engine where this config object should be saved.
   */
  public function __construct(DrupalConfigVerifiedStorageInterface $verified_storage) {
    $this->_verifiedStorage = $verified_storage;
    $this->read();
  }

  /**
   * Reads config data from the active store into our object.
   */
  public function read() {
    $active = (array) config_decode($this->_verifiedStorage->read());
    foreach ($active as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * Checks whether a particular value is overridden.
   *
   * @param $key
   *   @todo
   *
   * @return
   *   @todo
   */
  public function isOverridden($key) {
    return isset($this->_overrides[$key]);
  }

  /**
   * Gets data from this config object.
   *
   * @param $key
   *   A string that maps to a key within the configuration data. 
   *   For instance in the following XML:
   *  
   *   <foo>
   *     <bar>baz</bar>
   *   </foo>
   * 
   *  A key of 'foo.bar' would return the string 'baz'. However
   *  a key of 'foo' would return array('bar' => 'baz').
   *
   * If no key is specified, then the entire data array is returned.
   *
   * @return
   *   The data that was requested.
   */
  public function get($key = '') {
    if (empty($key)) {
      return $this->data;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($this->data[$key]) ? $this->data[$key] : NULL;
      }
      else {
        return drupal_array_get_nested_value($this->data, $parts);
      }      
    }
  }

  /**
   * Sets value in this config object.
   *
   * @param $key
   *   @todo
   * @param $value
   *   @todo
   */
  public function set($key, $value) {
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      $this->data[$key] = $value;
    }
    else {
      drupal_array_set_nested_value($this->data, $parts, $value);
    }
  }

  /**
   * Unsets value in this config object.
   *
   * @param $key
   *   @todo
   */
  public function clear($key) {
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      unset($this->data[$key]);
    }
    else {
      drupal_array_unset_nested_value($this->data, $parts);
    }
  }

  /**
   * Saves the configuration object to disk as XML.
   */
  public function save() {
    $this->_verifiedStorage->write(config_encode($this->data));
  }

  /**
   * Deletes the configuration object on disk.
   */
  public function delete() {
    $this->_verifiedStorage->delete();
  }
}
