<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldDefinition.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDefinition;
use Drupal\field\FieldException;

/**
 * A class for defining entity fields.
 */
class FieldDefinition extends ListDefinition implements FieldDefinitionInterface {

  /**
   * The field schema.
   *
   * @var array
   */
  protected $schema;

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
  public function getName() {
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
  public function setName($name) {
    $this->definition['field_name'] = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $data_type = $this->getItemDefinition()->getDataType();
    // Cut of the leading field_item: prefix from 'field_item:FIELD_TYPE'.
    $parts = explode(':', $data_type);
    return $parts[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
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
  public function setSettings(array $settings) {
    $this->getItemDefinition()->setSettings($settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
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
  public function setSetting($setting_name, $value) {
    $this->getItemDefinition()->setSetting($setting_name, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyNames() {
    return array_keys(\Drupal::typedDataManager()->create($this->getItemDefinition())->getPropertyDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
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
  public function getCardinality() {
    // @todo: Allow to control this.
    return isset($this->definition['cardinality']) ? $this->definition['cardinality'] : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    $cardinality = $this->getCardinality();
    return ($cardinality == static::CARDINALITY_UNLIMITED) || ($cardinality > 1);
  }

  /**
   * {@inheritdoc}
   */
  public function isQueryable() {
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
  public function setQueryable($queryable) {
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
   * Sets the display options for the field in forms or rendered entities.
   *
   * This enables generic rendering of the field with widgets / formatters,
   * including automated support for "In place editing", and with optional
   * configurability in the "Manage display" / "Manage form display" UI screens.
   *
   * Unless this method is called, the field remains invisible (or requires
   * ad-hoc rendering logic).
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   * @param array $options
   *   An array of display options. Refer to
   *   \Drupal\Core\Field\FieldDefinitionInterface::getDisplayOptions() for
   *   a list of supported keys. The options should include at least a 'weight',
   *   or specify 'type' = 'hidden'. The 'default_widget' / 'default_formatter'
   *   for the field type will be used if no 'type' is specified.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setDisplayOptions($display_context, array $options) {
    $this->definition['display'][$display_context]['options'] = $options;
    return $this;
  }

  /**
   * Sets whether the display for the field can be configured.
   *
   * @param string $display_context
   *   The display context. Either 'view' or 'form'.
   * @param bool $configurable
   *   Whether the display options can be configured (e.g., via the "Manage
   *   display" / "Manage form display" UI screens). If TRUE, the options
   *   specified via getDisplayOptions() act as defaults.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setDisplayConfigurable($display_context, $configurable) {
    // If no explicit display options have been specified, default to 'hidden'.
    if (empty($this->definition['display'][$display_context])) {
      $this->definition['display'][$display_context]['options'] = array('type' => 'hidden');
    }
    $this->definition['display'][$display_context]['configurable'] = $configurable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions($display_context) {
    return isset($this->definition['display'][$display_context]['options']) ? $this->definition['display'][$display_context]['options'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayConfigurable($display_context) {
    return isset($this->definition['display'][$display_context]['configurable']) ? $this->definition['display'][$display_context]['configurable'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(EntityInterface $entity) {
    return $this->getSetting('default_value');
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      // Get the schema from the field item class.
      $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->getFieldType());
      $class = $definition['class'];
      $schema = $class::schema($this);
      // Fill in default values for optional entries.
      $schema += array('indexes' => array(), 'foreign keys' => array());

      // Check that the schema does not include forbidden column names.
      if (array_intersect(array_keys($schema['columns']), static::getReservedColumns())) {
        throw new FieldException('Illegal field type columns.');
      }

      // Merge custom indexes with those specified by the field type. Custom
      // indexes prevail.
      $schema['indexes'] = $this->indexes + $schema['indexes'];

      $this->schema = $schema;
    }

    return $this->schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumns() {
    $schema = $this->getSchema();
    // A typical use case for the method is to iterate on the columns, while
    // some other use cases rely on identifying the first column with the key()
    // function. Since the schema is persisted in the Field object, we take care
    // of resetting the array pointer so that the former does not interfere with
    // the latter.
    reset($schema['columns']);
    return $schema['columns'];
  }

  /**
   * A list of columns that can not be used as field type columns.
   *
   * @return array
   */
  public static function getReservedColumns() {
    return array('deleted');
  }

}
