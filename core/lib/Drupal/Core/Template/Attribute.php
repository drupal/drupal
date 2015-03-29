<?php

/**
 * @file
 * Contains \Drupal\Core\Template\Attribute.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Collects, sanitizes, and renders HTML attributes.
 *
 * To use, optionally pass in an associative array of defined attributes, or
 * add attributes using array syntax. For example:
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat' . $attributes . '>';
 *  // Produces <cat id="socks" class="black-cat white-cat black-white-cat">
 * @endcode
 *
 * $attributes always prints out all the attributes. For example:
 * @code
 *  $attributes = new Attribute(array('id' => 'socks'));
 *  $attributes['class'] = array('black-cat', 'white-cat');
 *  $attributes['class'][] = 'black-white-cat';
 *  echo '<cat class="cat ' . $attributes['class'] . '"' . $attributes . '>';
 *  // Produces <cat class="cat black-cat white-cat black-white-cat" id="socks" class="cat black-cat white-cat black-white-cat">
 * @endcode
 *
 * When printing out individual attributes to customize them within a Twig
 * template, use the "without" filter to prevent attributes that have already
 * been printed from being printed again. For example:
 * @code
 *  <cat class="{{ attributes.class }} my-custom-class"{{ attributes|without('class') }}>
 *  {# Produces <cat class="cat black-cat white-cat black-white-cat my-custom-class" id="socks"> #}
 * @endcode
 *
 * The attribute keys and values are automatically sanitized for output with
 * \Drupal\Component\Utility\SafeMarkup::checkPlain().
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
   * Adds classes or merges them on to array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to add to the class attribute array.
   *
   * @return $this
   */
  public function addClass() {
    $args = func_get_args();
    if ($args) {
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
        $this->storage['class']->exchangeArray($classes);
      }
      else {
        $this->offsetSet('class', $classes);
      }
    }

    return $this;
  }

  /**
   * Sets values for an attribute key.
   *
   * @param string $attribute
   *   Name of the attribute.
   * @param string|array $value
   *   Value(s) to set for the given attribute key.
   *
   * @return $this
   */
  public function setAttribute($attribute, $value) {
    $this->offsetSet($attribute, $value);

    return $this;
  }

  /**
   * Removes an attribute from an Attribute object.
   *
   * @param string|array ...
   *   Attributes to remove from the attribute array.
   *
   * @return $this
   */
  public function removeAttribute() {
    $args = func_get_args();
    foreach ($args as $arg) {
      // Support arrays or multiple arguments.
      if (is_array($arg)) {
        foreach ($arg as $value) {
          unset($this->storage[$value]);
        }
      }
      else {
        unset($this->storage[$arg]);
      }
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

      // Remove the values passed in from the value array. Use array_values() to
      // ensure that the array index remains sequential.
      $classes = array_values(array_diff($this->storage['class']->value(), $classes));
      $this->storage['class']->exchangeArray($classes);
    }
    return $this;
  }

  /**
   * Checks if the class array has the given CSS class.
   *
   * @param string $class
   *   The CSS class to check for.
   *
   * @return bool
   *   Returns TRUE if the class exists, or FALSE otherwise.
   */
  public function hasClass($class) {
    if (isset($this->storage['class']) && $this->storage['class'] instanceOf AttributeArray) {
      return in_array($class, $this->storage['class']->value());
    }
    else {
      return FALSE;
    }
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
