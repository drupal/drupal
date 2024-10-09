<?php

namespace Drupal\Core\TypedData\Plugin\DataType;

use Drupal\Component\Utility\FilterArray;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedData;

/**
 * The "map" data type.
 *
 * The "map" data type represent a simple complex data type, e.g. for
 * representing associative arrays. It can also serve as base class for any
 * complex data type.
 *
 * By default there is no metadata for contained properties. Extending classes
 * may want to override MapDataDefinition::getPropertyDefinitions() to define
 * it.
 *
 * @ingroup typed_data
 */
#[DataType(
  id: "map",
  label: new TranslatableMarkup("Map"),
  definition_class: MapDataDefinition::class,
)]
class Map extends TypedData implements \IteratorAggregate, ComplexDataInterface {

  /**
   * The data definition.
   *
   * @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface
   */
  protected $definition;

  /**
   * An array of values for the contained properties.
   *
   * @var array
   */
  protected $values = [];

  /**
   * The array of properties.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface[]
   */
  protected $properties = [];

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // Update the values and return them.
    foreach ($this->properties as $name => $property) {
      $definition = $property->getDataDefinition();
      if (!$definition->isComputed()) {
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
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it to FALSE to
   *   avoid being notified again.
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }
    $this->values = $values;

    // Update any existing property objects.
    foreach ($this->properties as $name => $property) {
      $value = $values[$name] ?? NULL;
      $property->setValue($value, FALSE);
      // Remove the value from $this->values to ensure it does not contain any
      // value for computed properties.
      unset($this->values[$name]);
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $strings = [];
    foreach ($this->getProperties() as $property) {
      $strings[] = $property->getString();
    }
    // Remove any empty strings resulting from empty items.
    return implode(', ', FilterArray::removeEmptyStrings($strings));
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    if (!isset($this->properties[$property_name])) {
      $value = NULL;
      if (isset($this->values[$property_name])) {
        $value = $this->values[$property_name];
      }
      // If the property is unknown, this will throw an exception.
      $this->properties[$property_name] = $this->getTypedDataManager()->getPropertyInstance($this, $property_name, $value);
    }
    return $this->properties[$property_name];
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Separate the writing in a protected method, such that onChange
    // implementations can make use of it.
    $this->writePropertyValue($property_name, $value);
    $this->onChange($property_name, $notify);
    return $this;
  }

  /**
   * Writes the value of a property without handling changes.
   *
   * Implementations of onChange() should use this method instead of set() in
   * order to avoid onChange() being triggered again.
   *
   * @param string $property_name
   *   The name of the property to be written.
   * @param $value
   *   The value to set.
   */
  protected function writePropertyValue($property_name, $value) {
    if ($this->definition->getPropertyDefinition($property_name)) {
      $this->get($property_name)->setValue($value, FALSE);
    }
    else {
      // Just set the plain value, which allows adding a new entry to the map.
      $this->values[$property_name] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    $properties = [];
    foreach ($this->definition->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || !$definition->isComputed()) {
        $properties[$name] = $this->get($name);
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \ArrayIterator {
    return new \ArrayIterator($this->getProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->properties as $property) {
      $definition = $property->getDataDefinition();
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
   * {@inheritdoc}
   *
   * @param $property_name
   *   The name of the property.
   * @param bool $notify
   *   (optional) Whether to forward the notification to the parent. Defaults to
   *   TRUE. By passing FALSE, overrides of this method can re-use the logic
   *   of parent classes without triggering notification.
   */
  public function onChange($property_name, $notify = TRUE) {
    // Notify the parent of changes.
    if ($notify && isset($this->parent)) {
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
