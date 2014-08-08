<?php

/**
 * @file
 * Contains \Drupal\Core\Template\Attribute.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\SafeMarkup;

/**
 * A class that can be used for collecting then rendering HTML attributtes.
 *
 * To use, one may both pass in an array of already defined attributes and
 * add attributes to it like using array syntax.
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat' . $attributes . '>';
 *  // Produces <cat id="socks" class="black-cat white-cat black-white-cat">
 * @endcode
 *
 * individual parts of the attribute may be printed first.
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat class="cat ' . $attributes['class'] . '"' . $attributes . '>';
 *  // Produces <cat class="cat black-cat white-cat black-white-cat" id="socks">
 * @endcode
 */
class Attribute implements \ArrayAccess, \IteratorAggregate {

  /**
   * Stores the attribute data.
   *
   * @var array
   */
  protected $storage = array();

  /**
   * Constructs a \Drupal\Core\Template\Attribute object.
   *
   * @param array $attributes
   *   An associative array of key-value pairs to be converted to attributes.
   */
  public function __construct($attributes = array()) {
    foreach ($attributes as $name => $value) {
      $this->offsetSet($name, $value);
    }
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function offsetGet($name) {
    if (isset($this->storage[$name])) {
      return $this->storage[$name];
    }
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($name, $value) {
    $this->storage[$name] = $this->createAttributeValue($name, $value);
  }

  /**
   * Creates the different types of attribute values.
   *
   * @param string $name
   *   The attribute name.
   * @param mixed $value
   *   The attribute value.
   *
   * @return \Drupal\Core\Template\AttributeValueBase
   *   An AttributeValueBase representation of the attribute's value.
   */
  protected function createAttributeValue($name, $value) {
    // If the value is already an AttributeValueBase object, return it
    // straight away.
    if ($value instanceOf AttributeValueBase) {
      return $value;
    }
    // An array value or 'class' attribute name are forced to always be an
    // AttributeArray value for consistency.
    if (is_array($value) || $name == 'class') {
      // Cast the value to an array if the value was passed in as a string.
      // @todo Decide to fix all the broken instances of class as a string
      // in core or cast them.
      $value = new AttributeArray($name, (array) $value);
    }
    elseif (is_bool($value)) {
      $value = new AttributeBoolean($name, $value);
    }
    elseif (!is_object($value)) {
      $value = new AttributeString($name, $value);
    }
    return $value;
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($name) {
    unset($this->storage[$name]);
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($name) {
    return isset($this->storage[$name]);
  }

  /**
   * Adds argument values by merging them on to array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to add to the class attribute array.
   *
   * @return $this
   */
  public function addClass() {
    $args = func_get_args();
    $classes = array();
    foreach ($args as $arg) {
      // Merge the values passed in from the classes array.
      // The argument is cast to an array to support comma separated single
      // values or one or more array arguments.
      $classes = array_merge($classes, (array) $arg);
    }

    // Merge if there are values, just add them otherwise.
    if (isset($this->storage['class']) && $this->storage['class'] instanceOf AttributeArray) {
      // Merge the values passed in from the class value array.
      $classes = array_merge($this->storage['class']->value(), $classes);
      // Filter out any duplicate values.
      $classes = array_unique($classes);
      $this->storage['class']->exchangeArray($classes);
    }
    else {
      // Filter out any duplicate values.
      $classes = array_unique($classes);
      $this->offsetSet('class', $classes);
    }

    return $this;
  }

  /**
   * Removes argument values from array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to remove from the class attribute array.
   *
   * @return $this
   */
  public function removeClass() {
    // With no class attribute, there is no need to remove.
    if (isset($this->storage['class']) && $this->storage['class'] instanceOf AttributeArray) {
      $args = func_get_args();
      $classes = array();
      foreach ($args as $arg) {
        // Merge the values passed in from the classes array.
        // The argument is cast to an array to support comma separated single
        // values or one or more array arguments.
        $classes = array_merge($classes, (array) $arg);
      }

      // Remove the values passed in from the value array.
      $classes = array_diff($this->storage['class']->value(), $classes);
      $this->storage['class']->exchangeArray($classes);
    }
    return $this;
  }

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    $return = '';
    foreach ($this->storage as $name => $value) {
      $rendered = $value->render();
      if ($rendered) {
        $return .= ' ' . $rendered;
      }
    }
    return SafeMarkup::set($return);
  }

  /**
   * Implements the magic __clone() method.
   */
  public function  __clone() {
    foreach ($this->storage as $name => $value) {
      $this->storage[$name] = clone $value;
    }
  }

  /**
   * Implements IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new \ArrayIterator($this->storage);
  }

  /**
   * Returns the whole array.
   */
  public function storage() {
    return $this->storage;
  }

}
