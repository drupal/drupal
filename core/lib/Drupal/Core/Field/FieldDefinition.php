<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldDefinition.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDefinition;

/**
 * A class for defining entity fields.
 */
class FieldDefinition extends ListDefinition implements FieldDefinitionInterface, \ArrayAccess {

  /**
   * Creates a new field definition.
   *
   * @param string $type
   *   The type of the field.
   *
   * @return \Drupal\Core\Field\FieldDefinition
   *   A new field definition object.
   */
  public static function create($type) {
    return new static(array(), DataDefinition::create('field_item:' . $type));
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->definition['field_name'];
  }

  /**
   * Sets the field name.
   *
   * @param string $name
   *   The field name to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setFieldName($name) {
    $this->definition['field_name'] = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    $data_type = $this->getItemDefinition()->getDataType();
    // Cut of the leading field_item: prefix from 'field_item:FIELD_TYPE'.
    $parts = explode(':', $data_type);
    return $parts[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettings() {
    return $this->getItemDefinition()->getSettings();
  }

  /**
   * Sets field settings.
   *
   * @param array $settings
   *   The value to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setFieldSettings(array $settings) {
    $this->getItemDefinition()->setSettings($settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSetting($setting_name) {
    $settings = $this->getFieldSettings();
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Sets a field setting.
   *
   * @param string $setting_name
   *   The field setting to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setFieldSetting($setting_name, $value) {
    $settings = $this->getFieldSettings();
    $settings[$setting_name] = $value;
    return $this->setFieldSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPropertyNames() {
    return array_keys(\Drupal::typedData()->create($this->getItemDefinition())->getPropertyDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldTranslatable() {
    return !empty($this->definition['translatable']);
  }

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setTranslatable($translatable) {
    $this->definition['translatable'] = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldLabel($label) {
    return $this->setLabel($label);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription() {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDescription($description) {
    return $this->setDescription($description);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCardinality() {
    // @todo: Allow to control this.
    return isset($this->definition['cardinality']) ? $this->definition['cardinality'] : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldRequired() {
    return $this->isRequired();
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldMultiple() {
    $cardinality = $this->getFieldCardinality();
    return ($cardinality == static::CARDINALITY_UNLIMITED) || ($cardinality > 1);
  }

  /**
   * Sets whether the field is required.
   *
   * @param bool $required
   *   Whether the field is required.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setFieldRequired($required) {
    return $this->setRequired($required);
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldQueryable() {
    return isset($this->definition['queryable']) ? $this->definition['queryable'] : !$this->isComputed();
  }

  /**
   * Sets whether the field is queryable.
   *
   * @param bool $queryable
   *   Whether the field is queryable.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setFieldQueryable($queryable) {
    $this->definition['queryable'] = $queryable;
    return $this;
  }

  /**
   * Sets constraints for a given field item property.
   *
   * @param string $name
   *   The name of the property to set constraints for.
   * @param array $constraints
   *   The constraints to set.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setPropertyConstraints($name, array $constraints) {
    $item_constraints = $this->getItemDefinition()->getConstraints();
    $item_constraints['ComplexData'][$name] = $constraints;
    $this->getItemDefinition()->setConstraints($item_constraints);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldConfigurable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefaultValue(EntityInterface $entity) {
    return $this->getFieldSetting('default_value');
  }

  /**
   * Allows creating field definition objects from old style definition arrays.
   *
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public static function createFromOldStyleDefinition(array $definition) {
    unset($definition['list']);

    // Separate the list item definition from the list definition.
    $list_definition = $definition;
    unset($list_definition['type']);

    // Constraints, class and settings apply to the list item.
    unset($list_definition['constraints']);
    unset($list_definition['class']);
    unset($list_definition['settings']);

    $field_definition = new FieldDefinition($list_definition);
    if (isset($definition['list_class'])) {
      $field_definition->setClass($definition['list_class']);
    }
    else {
      $type_definition = \Drupal::typedData()->getDefinition($definition['type']);
      if (isset($type_definition['list_class'])) {
        $field_definition->setClass($type_definition['list_class']);
      }
    }
    if (isset($definition['translatable'])) {
      $field_definition->setTranslatable($definition['translatable']);
      unset($definition['translatable']);
    }

    // Take care of the item definition now.
    // Required applies to the field definition only.
    unset($definition['required']);
    $item_definition = new DataDefinition($definition);
    $field_definition->setItemDefinition($item_definition);
    return $field_definition;
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function &offsetGet($offset) {
    if ($offset == 'type') {
      // What previously was "type" is now the type of the list item.
      $type = &$this->itemDefinition->offsetGet('type');
      return $type;
    }
    if (!isset($this->definition[$offset])) {
      $this->definition[$offset] = NULL;
    }
    return $this->definition[$offset];
  }

  /**
   * {@inheritdoc}
   *
   * This is for BC support only.
   * @todo: Remove once https://drupal.org/node/2112239 is in.
   */
  public function offsetSet($offset, $value) {
    if ($offset == 'type') {
      // What previously was "type" is now the type of the list item.
      $this->itemDefinition->setDataType($value);
    }
    else {
      $this->definition[$offset] = $value;
    }
  }
}
