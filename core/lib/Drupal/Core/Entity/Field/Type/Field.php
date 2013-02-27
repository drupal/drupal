<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\Field.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\user\Plugin\Core\Entity\User;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\ContextAwareTypedData;
use Drupal\Core\TypedData\TypedDataInterface;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * Represents an entity field; that is, a list of field item objects.
 *
 * An entity field is a list of field items, which contain only primitive
 * properties or entity references. Note that even single-valued entity
 * fields are represented as list of items, however for easy access to the
 * contained item the entity field delegates __get() and __set() calls
 * directly to the first item.
 *
 * @see \Drupal\Core\Entity\Field\FieldInterface
 */
class Field extends ContextAwareTypedData implements IteratorAggregate, FieldInterface {

  /**
   * Numerically indexed array of field items, implementing the
   * FieldItemInterface.
   *
   * @var array
   */
  protected $list = array();

  /**
   * Overrides ContextAwareTypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, ContextAwareInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Always initialize one empty item as most times a value for at least one
    // item will be present. That way prototypes created by
    // \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance() will
    // already have this field item ready for use after cloning.
    $this->list[0] = $this->createItem(0);
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    if (isset($this->list)) {
      $values = array();
      foreach ($this->list as $delta => $item) {
        if (!$item->isEmpty()) {
          $values[$delta] = $item->getValue();
        }
        else {
          $values[$delta] = NULL;
        }
      }
      return $values;
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of values of the field items, or NULL to unset the field.
   */
  public function setValue($values) {
    if (!isset($values) || $values === array()) {
      $this->list = $values;
    }
    else {
      // Support passing in only the value of the first item.
      if (!is_array($values) || !is_numeric(current(array_keys($values)))) {
        $values = array(0 => $values);
      }

      // Clear the values of properties for which no value has been passed.
      if (isset($this->list)) {
        $this->list = array_intersect_key($this->list, $values);
      }

      // Set the values.
      foreach ($values as $delta => $value) {
        if (!is_numeric($delta)) {
          throw new InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
        }
        elseif (!isset($this->list[$delta])) {
          $this->list[$delta] = $this->createItem($delta, $value);
        }
        else {
          $this->list[$delta]->setValue($value);
        }
      }
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    $strings = array();
    if (isset($this->list)) {
      foreach ($this->list as $item) {
        $strings[] = $item->getString();
      }
      return implode(', ', array_filter($strings));
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getConstraints().
   */
  public function getConstraints() {
    // Apply the constraints to the list items only.
    return array();
  }

  /**
   * Implements \ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->list) && array_key_exists($offset, $this->list);
  }

  /**
   * Implements \ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    if (isset($this->list)) {
      unset($this->list[$offset]);
    }
  }

  /**
   * Implements \ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      throw new InvalidArgumentException('Unable to get a value with a non-numeric delta in a list.');
    }
    // Allow getting not yet existing items as well.
    // @todo: Maybe add a public createItem() method in addition?
    elseif (!isset($this->list[$offset])) {
      $this->list[$offset] = $this->createItem($offset);
    }
    return $this->list[$offset];
  }

  /**
   * Helper for creating a list item object.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  protected function createItem($offset = 0, $value = NULL) {
    return typed_data()->getPropertyInstance($this, $offset, $value);
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::getItemDefinition().
   */
  public function getItemDefinition() {
    return array('list' => FALSE) + $this->definition;
  }

  /**
   * Implements \ArrayAccess::offsetSet().
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
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    if (isset($this->list)) {
      return new ArrayIterator($this->list);
    }
    return new ArrayIterator(array());
  }

  /**
   * Implements \Countable::count().
   */
  public function count() {
    return isset($this->list) ? count($this->list) : 0;
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    return $this->offsetGet(0)->getPropertyDefinition($name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    return $this->offsetGet(0)->getPropertyDefinitions();
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__get().
   */
  public function __get($property_name) {
    return $this->offsetGet(0)->get($property_name)->getValue();
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::get().
   */
  public function get($property_name) {
    return $this->offsetGet(0)->get($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__set().
   */
  public function __set($property_name, $value) {
    $this->offsetGet(0)->__set($property_name, $value);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__isset().
   */
  public function __isset($property_name) {
    return $this->offsetGet(0)->__isset($property_name);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldInterface::__unset().
   */
  public function __unset($property_name) {
    return $this->offsetGet(0)->__unset($property_name);
  }

  /**
   * Implements \Drupal\Core\TypedData\ListInterface::isEmpty().
   */
  public function isEmpty() {
    if (isset($this->list)) {
      foreach ($this->list as $item) {
        if (!$item->isEmpty()) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    if (isset($this->list)) {
      foreach ($this->list as $delta => $item) {
        $this->list[$delta] = clone $item;
        if ($item instanceof ContextAwareInterface) {
          $this->list[$delta]->setContext($delta, $this);
        }
      }
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', User $account = NULL) {
    global $user;
    if (!isset($account) && $user->uid) {
      $account = user_load($user->uid);
    }
    // Get the default access restriction that lives within this field.
    $access = $this->defaultAccess($operation, $account);
    // Invoke hook and collect grants/denies for field access from other
    // modules. Our default access flag is masked under the ':default' key.
    $grants = array(':default' => $access);
    foreach (module_implements('entity_field_access') as $module) {
      $grants = array_merge($grants, array($module => module_invoke($module, 'entity_field_access', $operation, $this, $account)));
    }
    // Also allow modules to alter the returned grants/denies.
    $context = array(
      'operation' => $operation,
      'field' => $this,
      'account' => $account,
    );
    drupal_alter('entity_field_access', $grants, $context);

    // One grant being FALSE is enough to deny access immediately.
    if (in_array(FALSE, $grants, TRUE)) {
      return FALSE;
    }
    // At least one grant has the explicit opinion to allow access.
    if (in_array(TRUE, $grants, TRUE)) {
      return TRUE;
    }
    // All grants are NULL and have no opinion - deny access in that case.
    return FALSE;
  }

  /**
   * Contains the default access logic of this field.
   *
   * See \Drupal\Core\TypedData\AccessibleInterface::access() for the parameter
   * doucmentation. This method can be overriden by field sub classes to provide
   * a different default access logic. That allows them to inherit the complete
   * access() method which contains the access hook invocation logic.
   *
   * @return bool
   *   TRUE if access to this field is allowed per default, FALSE otherwise.
   */
  public function defaultAccess($operation = 'view', User $account = NULL) {
    // Grant access per default.
    return TRUE;
  }
}
