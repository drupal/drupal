<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\Type\Field.
 */

namespace Drupal\Core\Entity\Field\Type;

use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\user\Plugin\Core\Entity\User;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\ItemList;

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
class Field extends ItemList implements FieldInterface {

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
   * Overrides \Drupal\Core\TypedData\ItemList::getValue().
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
   * Overrides \Drupal\Core\TypedData\ItemList::setValue().
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
          throw new \InvalidArgumentException('Unable to set a value with a non-numeric delta in a list.');
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
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', User $account = NULL) {
    // TODO: Implement access() method. Use item access.
  }
}
