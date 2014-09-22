<?php

/**
 * @file
 * Contains \Drupal\Core\Template\AttributeArray.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\String;

/**
 * A class that defines a type of Attribute that can be added to as an array.
 *
 * To use with Attribute, the array must be specified.
 * Correct:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'] = array();
 *  $attributes['class'][] = 'cat';
 * @endcode
 * Incorrect:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'][] = 'cat';
 * @endcode
 *
 * @see \Drupal\Core\Template\Attribute
 */
class AttributeArray extends AttributeValueBase implements \ArrayAccess, \IteratorAggregate {

  /**
   * Ensures empty array as a result of array_filter will not print '$name=""'.
   *
   * @see \Drupal\Core\Template\AttributeArray::__toString()
   * @see \Drupal\Core\Template\AttributeValueBase::render()
   */
  const RENDER_EMPTY_ATTRIBUTE = FALSE;

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    return $this->value[$offset];
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    if (isset($offset)) {
      $this->value[$offset] = $value;
    }
    else {
      $this->value[] = $value;
    }
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->value[$offset]);
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->value[$offset]);
  }

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    // Filter out any empty values before printing.
    $this->value = array_unique(array_filter($this->value));
    return String::checkPlain(implode(' ', $this->value));
  }

  /**
   * Implements IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new \ArrayIterator($this->value);
  }

  /**
   * Returns the whole array.
   */
  public function value() {
    return $this->value;
  }

  /**
   * Exchange the array for another one.
   *
   * @see ArrayObject::exchangeArray
   *
   * @param array $input
   *   The array input to replace the internal value.
   *
   * @return array
   *   The old array value.
   */
  public function exchangeArray($input) {
    $old = $this->value;
    $this->value = $input;
    return $old;
  }

}
