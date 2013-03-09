<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Type\Map.
 */

namespace Drupal\Core\TypedData\Type;

use Drupal\Core\TypedData\ContextAwareTypedData;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * The "map" data type.
 *
 * The "map" data type represent a simple complex data type, e.g. for
 * representing associative arrays. It can also serve as base class for any
 * complex data type.
 *
 * By default there is no metadata for contained properties. Extending classes
 * may want to override Map::getPropertyDefinitions() to define it.
 */
class Map extends ContextAwareTypedData implements \IteratorAggregate, ComplexDataInterface {

  /**
   * An array of values for the contained properties.
   *
   * @var array
   */
  protected $values = array();

  /**
   * The array of properties, each implementing the TypedDataInterface.
   *
   * @var array
   */
  protected $properties;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    $definitions = array();
    foreach ($this->values as $name => $value) {
      $definitions[$name] = array(
        'type' => 'any',
      );
    }
    return $definitions;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue() {
    return $this->values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values) {
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }
    $this->values = $values;
    unset($this->properties);
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getString().
   */
  public function getString() {
    $strings = array();
    foreach ($this->getProperties() as $property) {
      $strings[] = $property->getString();
    }
    // Remove any empty strings resulting from empty items.
    return implode(', ', array_filter($strings, 'drupal_strlen'));
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    if (!$this->getPropertyDefinition($property_name)) {
      throw new \InvalidArgumentException('Property ' . check_plain($property_name) . ' is unknown.');
    }
    if (!isset($this->properties[$property_name])) {
      $this->properties[$property_name] = typed_data()->getPropertyInstance($this, $property_name, isset($this->values[$property_name]) ? $this->values[$property_name] : NULL);
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
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || empty($definition['computed'])) {
        $properties[$name] = $this->get($name);
      }
    }
    return $properties;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    $values = array();
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
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
    return new \ArrayIterator($this->getProperties());
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
    foreach ($this->getProperties() as $name => $property) {
      $this->properties[$name] = clone $property;
      if ($property instanceof ContextAwareInterface) {
        $this->properties[$name]->setContext($name, $this);
      }
    }
  }
}
