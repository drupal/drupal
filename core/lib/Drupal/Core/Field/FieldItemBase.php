<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldItemBase.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\user;

/**
 * An entity field item.
 *
 * Entity field items making use of this base class have to implement
 * ComplexDataInterface::getPropertyDefinitions().
 *
 * @see \Drupal\Core\Field\FieldItemInterface
 */
abstract class FieldItemBase extends Map implements FieldItemInterface {

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::__construct().
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Initialize computed properties by default, such that they get cloned
    // with the whole item.
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if (!empty($definition['computed'])) {
        $this->properties[$name] = \Drupal::typedData()->getPropertyInstance($this, $name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->getParent()->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->parent->getLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    return $this->getParent()->getFieldDefinition();
  }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->getFieldDefinition()->getFieldSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getFieldSetting($setting_name) {
    return $this->getFieldDefinition()->getFieldSetting($setting_name);
  }

  /**
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the first property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $keys = array_keys($this->getPropertyDefinitions());
      $values = array($keys[0] => $values);
    }
    $this->values = $values;
    // Update any existing property objects.
    foreach ($this->properties as $name => $property) {
      $value = NULL;
      if (isset($values[$name])) {
        $value = $values[$name];
      }
      $property->setValue($value, FALSE);
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
  public function __get($name) {
    // There is either a property object or a plain value - possibly for a
    // not-defined property. If we have a plain value, directly return it.
    if (isset($this->values[$name])) {
      return $this->values[$name];
    }
    elseif (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // For defined properties there is either a property object or a plain
    // value that needs to be updated.
    if (isset($this->properties[$property_name])) {
      $this->properties[$property_name]->setValue($value, FALSE);
      unset($this->values[$property_name]);
    }
    // Allow setting plain values for not-defined properties also.
    else {
      $this->values[$property_name] = $value;
    }
    // Directly notify ourselves.
    if ($notify) {
      $this->onChange($property_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    // Support setting values via property objects, but take care in as the
    // value of the 'entity' property is typed data also.
    if ($value instanceof TypedDataInterface && !($value instanceof EntityInterface)) {
      $value = $value->getValue();
    }
    $this->set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    return isset($this->values[$name]) || (isset($this->properties[$name]) && $this->properties[$name]->getValue() !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    $this->set($name, NULL);
    unset($this->values[$name]);
  }

  /**
   * Overrides \Drupal\Core\TypedData\Map::onChange().
   */
  public function onChange($property_name) {
    // Notify the parent of changes.
    if (isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
    // Remove the plain value, such that any further __get() calls go via the
    // updated property object.
    if (isset($this->properties[$property_name])) {
      unset($this->values[$property_name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // If property constraints are present add in a ComplexData constraint for
    // applying them.
    if (!empty($this->definition['property_constraints'])) {
      $constraints[] = \Drupal::typedData()->getValidationConstraintManager()
        ->create('ComplexData', $this->definition['property_constraints']);
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() { }

  /**
   * {@inheritdoc}
   */
  public function insert() { }

  /**
   * {@inheritdoc}
   */
  public function update() { }

  /**
   * {@inheritdoc}
   */
  public function delete() { }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() { }

}
