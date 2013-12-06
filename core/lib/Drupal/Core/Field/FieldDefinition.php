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
class FieldDefinition extends ListDefinition implements FieldDefinitionInterface {

  /**
   * Creates a new field definition.
   *
   * @param string $type
   *   The type of the field.
   *
   * @return static
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
   * @return static
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
   * @return static
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
    return $this->getItemDefinition()->getSetting($setting_name);
  }

  /**
   * Sets a field setting.
   *
   * @param string $setting_name
   *   The field setting to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setFieldSetting($setting_name, $value) {
    $this->getItemDefinition()->setSetting($setting_name, $value);
    return $this;
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
   * @return static
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
   * @return static
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
   * @return static
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
   * @return static
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

}
