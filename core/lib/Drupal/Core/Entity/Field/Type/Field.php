<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Field\Type\Field.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\Type\TypedData;
use Drupal\user\User;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * An entity field, i.e. a list of field items.
 *
 * An entity field is a list of field items, which contain only primitive
 * properties or entity references. Note that even single-valued entity
 * fields are represented as list of items, however for easy access to the
 * contained item the entity field delegates __get() and __set() calls
 * directly to the first item.
 *
 * @see \Drupal\Core\Entity\Field\FieldInterface
 */
class Field extends TypedData implements IteratorAggregate, FieldInterface {

  /**
   * The entity field name.
   *
   * @var string
   */
  protected $name;

  /**
   * The parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parent;

  /**
   * Numerically indexed array of field items, implementing the
   * FieldItemInterface.
   *
   * @var array
   */
  protected $list = array();

  /**
   * Implements TypedDataInterface::getValue().
   */
  public function getValue() {
    $values = array();
    foreach ($this->list as $delta => $item) {
      $values[$delta] = !$item->isEmpty() ? $item->getValue() : NULL;
    }
    return $values;
  }

  /**
   * Implements TypedDataInterface::setValue().
   *
   * @param array $values
   *   An array of values of the field items.
   */
  public function setValue($values) {
    // Support passing in only the value of the first item.
    if (!is_array($values) || (!empty($values) && !is_numeric(current(array_keys($values))))) {
      $values = array(0 => $values);
    }

    if (!is_array($values)) {
      throw new InvalidArgumentException("An entity field requires a numerically indexed array of items as value.");
    }

    if (!empty($values)) {
      if (!is_array($values)) {
        throw new InvalidArgumentException("An entity field requires a numerically indexed array of items as value.");
      }
      // Clear the values of properties for which no value has been passed.
      foreach (array_diff_key($this->list, $values) as $delta => $item) {
        unset($this->list[$delta]);
      }

      // Set the values.
      foreach ($values as $delta => $value) {
        if (!is_numeric($delta)) {
          throw new InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
        }
        elseif (!isset($this->list[$delta])) {
          $this->list[$delta] = $this->createItem($value);
        }
        else {
          $this->list[$delta]->setValue($value);
        }
      }
    }
    else {
      $this->list = array();
    }
  }

  /**
   * Returns a string representation of the field.
   *
   * @return string
   */
  public function getString() {
    $strings = array();
    foreach ($this->list() as $item) {
      $strings[] = $item->getString();
    }
    return implode(', ', array_filter($strings));
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // @todo implement
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->list);
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->list[$offset]);
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      throw new InvalidArgumentException('Unable to get a value with a non-numeric delta in a list.');
    }
    // Allow getting not yet existing items as well.
    // @todo: Maybe add a public createItem() method in addition?
    elseif (!isset($this->list[$offset])) {
      $this->list[$offset] = $this->createItem();
    }
    return $this->list[$offset];
  }

  /**
   * Helper for creating a list item object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  protected function createItem($value = NULL) {
    $context = array('parent' => $this);
    return typed_data()->create(array('list' => FALSE) + $this->definition, $value, $context);
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    if (!isset($offset)) {
      // The [] operator has been used so point at a new entry.
      $offset = $this->list ? max(array_keys($this->list)) + 1 : 0;
    }
    if (is_numeric($offset)) {
      // Support setting values via typed data objects.
      if ($value instanceof TypedDataInterface) {
        $value = $value->getValue();
      }
      $this->offsetGet($offset)->setValue($value);
    }
    else {
      throw new InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
    }
  }

  /**
   * Implements IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new ArrayIterator($this->list);
  }

  /**
   * Implements Countable::count().
   */
  public function count() {
    return count($this->list);
  }

  /**
   * Implements ContextAwareInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements ContextAwareInterface::setName().
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Implements ContextAwareInterface::getParent().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Implements ContextAwareInterface::setParent().
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * Delegate.
   */
  public function getPropertyDefinition($name) {
    return $this->offsetGet(0)->getPropertyDefinition($name);
  }

  /**
   * Delegate.
   */
  public function getPropertyDefinitions() {
    return $this->offsetGet(0)->getPropertyDefinitions();
  }

  /**
   * Delegate.
   */
  public function __get($property_name) {
    return $this->offsetGet(0)->__get($property_name);
  }

  /**
   * Delegate.
   */
  public function get($property_name) {
    return $this->offsetGet(0)->get($property_name);
  }

  /**
   * Delegate.
   */
  public function __set($property_name, $value) {
    $this->offsetGet(0)->__set($property_name, $value);
  }

  /**
   * Delegate.
   */
  public function __isset($property_name) {
    return $this->offsetGet(0)->__isset($property_name);
  }

  /**
   * Delegate.
   */
  public function __unset($property_name) {
    return $this->offsetGet(0)->__unset($property_name);
  }

  /**
   * Implements ListInterface::isEmpty().
   */
  public function isEmpty() {
    foreach ($this->list as $item) {
      if (!$item->isEmpty()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Implements a deep clone.
   */
  public function __clone() {
    foreach ($this->list as $delta => $property) {
      $this->list[$delta] = clone $property;
    }
  }

  /**
   * Implements AccessibleInterface::access().
   */
  public function access(User $account = NULL) {
    // TODO: Implement access() method. Use item access.
  }
}
