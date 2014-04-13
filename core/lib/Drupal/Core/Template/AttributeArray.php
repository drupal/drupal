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

}
