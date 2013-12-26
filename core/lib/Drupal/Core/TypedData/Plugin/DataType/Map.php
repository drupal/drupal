<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Plugin\DataType\Map.
 */

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedData;
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
 *
 * @DataType(
 *   id = "map",
 *   label = @Translation("Map")
 * )
 */
class Map extends TypedData implements \IteratorAggregate, ComplexDataInterface {

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
  protected $properties = array();

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    $definitions = array();
    foreach ($this->values as $name => $value) {
      $definitions[$name] = DataDefinition::create('any');
    }
    return $definitions;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::getValue().
   */
  public function getValue($include_computed = FALSE) {
    // Update the values and return them.
    foreach ($this->properties as $name => $property) {
      $definition = $property->getDefinition();
      if ($include_computed || !$definition->isComputed()) {
        $value = $property->getValue();
        // Only write NULL values if the whole map is not NULL.
        if (isset($this->values) || isset($value)) {
          $this->values[$name] = $value;
        }
      }
    }
    return $this->values;
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }
    $this->values = $values;

    // Update any existing property objects.
    foreach ($this->properties as $name => $property) {
      $value = NULL;
      if (isset($values[$name])) {
        $value = $values[$name];
      }
      $property->setValue($value, FALSE);
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
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
    if (!isset($this->properties[$property_name])) {
      $value = NULL;
      if (isset($this->values[$property_name])) {
        $value = $this->values[$property_name];
      }
      // If the property is unknown, this will throw an exception.
      $this->properties[$property_name] = \Drupal::typedDataManager()->getPropertyInstance($this, $property_name, $value);
    }
    return $this->properties[$property_name];
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    if ($this->getPropertyDefinition($property_name)) {
      $this->get($property_name)->setValue($value, $notify);
    }
    else {
      // Just set the plain value, which allows adding a new entry to the map.
      $this->values[$property_name] = $value;
      // Directly notify ourselves.
      if ($notify) {
        $this->onChange($property_name, $value);
      }
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || !$definition->isComputed()) {
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
    foreach ($this->properties as $property) {
      $definition = $property->getDefinition();
      if (!$definition->isComputed() && $property->getValue() !== NULL) {
        return FALSE;
      }
    }
    if (isset($this->values)) {
      foreach ($this->values as $name => $value) {
        if (isset($value) && !isset($this->properties[$name])) {
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
    foreach ($this->properties as $name => $property) {
      $this->properties[$name] = clone $property;
      $this->properties[$name]->setContext($name, $this);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::onChange().
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Apply the default value of all properties.
    foreach ($this->getProperties() as $property) {
      $property->applyDefaultValue(FALSE);
    }
    return $this;
  }
}
