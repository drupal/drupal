<?php

namespace Drupal\Component\Attribute;

use Drupal\Component\Utility\Html;

/**
 * A class that defines a type of Attribute that can be added to as an array.
 *
 * To use with AttributeCollection, the array must be specified.
 * Correct:
 * @code
 *  $attributes = new AttributeCollection();
 *  $attributes['class'] = [];
 *  $attributes['class'][] = 'cat';
 * @endcode
 * Incorrect:
 * @code
 *  $attributes = new AttributeCollection();
 *  $attributes['class'][] = 'cat';
 * @endcode
 *
 * @see \Drupal\Component\Attribute\AttributeCollection
 */
class AttributeArray extends AttributeValueBase implements \ArrayAccess, \IteratorAggregate {

  /**
   * Ensures empty array as a result of array_filter will not print '$name=""'.
   *
   * @see \Drupal\Component\Attribute\AttributeArray::__toString()
   * @see \Drupal\Component\Attribute\AttributeValueBase::render()
   */
  const RENDER_EMPTY_ATTRIBUTE = FALSE;

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    return $this->value[$offset];
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    unset($this->value[$offset]);
  }

  /**
   * {@inheritdoc}
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
    return Html::escape(implode(' ', $this->value));
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->value);
  }

  /**
   * Exchange the array for another one.
   *
   * @param array $input
   *   The array input to replace the internal value.
   *
   * @return array
   *   The old array value.
   *
   * @see ArrayObject::exchangeArray
   */
  public function exchangeArray($input) {
    $old = $this->value;
    $this->value = $input;
    return $old;
  }

}
