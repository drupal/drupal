<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\FieldItemBase.
 */

namespace Drupal\Core\Entity\Field;

use Drupal\Core\TypedData\ContextAwareTypedData;
use Drupal\Core\TypedData\ContextAwareInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\user;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * An entity field item.
 *
 * Entity field items making use of this base class have to implement
 * ComplexDataInterface::getPropertyDefinitions().
 *
 * @see \Drupal\Core\Entity\Field\FieldItemInterface
 */
abstract class FieldItemBase extends ContextAwareTypedData implements IteratorAggregate, FieldItemInterface {

  /**
   * The array of properties, each implementing the TypedDataInterface.
   *
   * Field objects are instantiated during object construction and cannot be
   * replaced by others, so computed properties can safely store references on
   * other properties.
   *
   * @var array
   */
  protected $properties = array();

  /**
   * Holds any non-property values that get set on the object.
   *
   * @todo: Remove or refactor once EntityNG conversion is complete.
   *
   * @var array
   */
  protected $extraValues = array();

  /**
   * Overrides ContextAwareTypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, ContextAwareInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    // Initialize all property objects, but postpone the creating of computed
    // properties to a second step. That way computed properties can safely get
    // references on non-computed properties during construction.
    $step2 = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if (empty($definition['computed'])) {
        $this->properties[$name] = typed_data()->getPropertyInstance($this, $name);
      }
      else {
        $step2[] = $name;
      }
    }

    foreach ($step2 as $name) {
      $this->properties[$name] = typed_data()->getPropertyInstance($this, $name);
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    $values = array();
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values + $this->extraValues;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values) {
    // Treat the values as property value of the first property, if no array is
    // given.
    if (!is_array($values)) {
      $keys = array_keys($this->properties);
      $values = array($keys[0] => $values);
    }

    foreach ($this->properties as $name => $property) {
      if (isset($values[$name])) {
        $property->setValue($values[$name]);
      }
      else {
        $property->setValue(NULL);
      }
      unset($values[$name]);
    }
    // @todo: Throw an exception for invalid values once conversion is
    // totally completed.
    $this->extraValues = $values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    $strings = array();
    foreach ($this->getProperties() as $property) {
      $strings[] = $property->getString();
    }
    return implode(', ', array_filter($strings));
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    if (!isset($this->properties[$property_name])) {
      throw new InvalidArgumentException('Field ' . check_plain($property_name) . ' is unknown.');
    }
    return $this->properties[$property_name];
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value) {
    $this->get($property_name)->setValue($value);
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__get().
   */
  public function __get($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
    // The property is unknown, so try to get it from the extra values.
    elseif (isset($this->extraValues[$name])) {
      return $this->extraValues[$name];
    }
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__set().
   */
  public function __set($name, $value) {
    // Support setting values via property objects.
    if ($value instanceof TypedDataInterface) {
      $value = $value->getValue();
    }
    if (isset($this->properties[$name])) {
      $this->properties[$name]->setValue($value);
    }
    else {
      // The property is unknown, so set it to the extra values.
      $this->extraValues[$name] = $value;
    }
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__isset().
   */
  public function __isset($name) {
    return isset($this->properties[$name]) && $this->properties[$name]->getValue() !== NULL;
  }

  /**
   * Implements \Drupal\Core\Entity\Field\FieldItemInterface::__unset().
   */
  public function __unset($name) {
    if (isset($this->properties[$name])) {
      $this->properties[$name]->setValue(NULL);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || empty($definition['computed'])) {
        $properties[$name] = $this->properties[$name];
      }
    }
    return $properties;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    return $this->getValue();
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    foreach ($values as $name => $value) {
      $this->get($name)->setValue($value);
    }
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new ArrayIterator($this->getProperties());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    $definitions = $this->getPropertyDefinitions();
    if (isset($definitions[$name])) {
      return $definitions[$name];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    foreach ($this->getProperties() as $property) {
      if ($property->getValue() !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    foreach ($this->properties as $name => $property) {
      $this->properties[$name] = clone $property;
      if ($property instanceof ContextAwareInterface) {
        $this->properties[$name]->setContext($name, $this);
      }
    }
  }
}