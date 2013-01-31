<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\ArrayElement.
 */

namespace Drupal\Core\Config\Schema;

use \ArrayAccess;
use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \Traversable;

/**
 * Defines a generic configuration element that contains multiple properties.
 */
abstract class ArrayElement extends Element implements IteratorAggregate, ArrayAccess, Countable {

  /**
   * Parsed elements.
   */
  protected $elements;

  /**
   * Gets an array of contained elements.
   *
   * @return array
   *   Array of \Drupal\Core\Config\Schema\ArrayElement objects.
   */
  protected function getElements() {
    if (!isset($this->elements)) {
      $this->elements = $this->parse();
    }
    return $this->elements;
  }

  /**
   * Gets valid configuration data keys.
   *
   * @return array
   *   Array of valid configuration data keys.
   */
  protected function getAllKeys() {
    return is_array($this->value) ? array_keys($this->value) : array();
  }

  /**
   * Builds an array of contained elements.
   *
   * @return array
   *   Array of \Drupal\Core\Config\Schema\ArrayElement objects.
   */
  protected abstract function parse();

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    foreach ($this->getElements() as $element) {
      if (!$element->validate()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->getElements());
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    $elements = $this->getElements();
    return $elements[$offset];
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    if ($value instanceof TypedDataInterface) {
      $value = $value->getValue();
    }
    $this->value[$offset] = $value;
    unset($this->elements);
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->value[$offset]);
    unset($this->elements);
  }

  /**
   * Implements Countable::count().
   */
  public function count() {
    return count($this->getElements());
  }

  /**
   * Implements IteratorAggregate::getIterator();
   */
  public function getIterator() {
    return new ArrayIterator($this->getElements());
  }

}
