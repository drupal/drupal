<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\FieldDefinition.
 */

namespace Drupal\Core\Entity\Field;
use Drupal\Core\Entity\EntityInterface;

/**
 * A class for defining entity fields.
 */
class FieldDefinition implements FieldDefinitionInterface {

  /**
   * The array holding values for all definition keys.
   *
   * @var array
   */
  protected $definition = array();

  /**
   * Constructs a new FieldDefinition object.
   *
   * @param array $definition
   *   (optional) If given, a definition represented as array.
   */
  public function __construct(array $definition = array()) {
    $this->definition = $definition;
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
   * @return \Drupal\Core\Entity\Field\FieldDefinition
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
    // Cut of the leading field_item: prefix from 'field_item:FIELD_TYPE'.
    $parts = explode(':', $this->definition['type']);
    return $parts[1];
  }

  /**
   * Sets the field type.
   *
   * @param string $type
   *   The field type to set.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setFieldType($type) {
    $this->definition['type'] = 'field_item:' . $type;
    return $this;
  }

  /**
   * Sets a field setting.
   *
   * @param string $type
   *   The field type to set.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setFieldSetting($setting_name, $value) {
    $this->definition['settings'][$setting_name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettings() {
    return $this->definition['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSetting($setting_name) {
    return isset($this->definition['settings'][$setting_name]) ? $this->definition['settings'][$setting_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPropertyNames() {
    return array_keys(\Drupal::typedData()->create($this->definition['type'])->getPropertyDefinitions());
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
   * @return \Drupal\Core\Entity\Field\FieldDefinition
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
    return $this->definition['label'];
  }

  /**
   * Sets the field label.
   *
   * @param string $label
   *   The field label to set.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setFieldLabel($label) {
    $this->definition['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription() {
    return $this->definition['description'];
  }

  /**
   * Sets the field label.
   *
   * @param string $description
   *   The field label to set.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setFieldDescription($description) {
    $this->definition['description'] = $description;
    return $this;
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
    return !empty($this->definition['required']);
  }

  /**
   * Sets whether the field is required.
   *
   * @param bool $required
   *   TRUE if the field is required, FALSE otherwise.
   *
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setFieldRequired($required) {
    $this->definition['required'] = $required;
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
   * @return \Drupal\Core\Entity\Field\FieldDefinition
   *   The object itself for chaining.
   */
  public function setPropertyConstraints($name, array $constraints) {
    $this->definition['item_definition']['constraints']['ComplexData'][$name] = $constraints;
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
