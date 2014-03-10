<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldDefinition.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\field\FieldException;

/**
 * A class for defining entity fields.
 */
class FieldDefinition extends ListDataDefinition implements FieldDefinitionInterface {

  /**
   * The field type.
   *
   * @var string
   */
  protected $type;

  /**
   * An array of field property definitions.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface[]
   *
   * @see \Drupal\Core\TypedData\ComplexDataDefinitionInterface::getPropertyDefinitions()
   */
  protected $propertyDefinitions;

  /**
   * The field schema.
   *
   * @var array
   */
  protected $schema;

  /**
   * @var array
   */
  protected $indexes = array();

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
    $field_definition = new static(array());
    $field_definition->type = $type;
    $field_definition->itemDefinition = FieldItemDataDefinition::create($field_definition);
    // Create a definition for the items, and initialize it with the default
    // settings for the field type.
    // @todo Cleanup in https://drupal.org/node/2116341.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $default_settings = $field_type_manager->getDefaultSettings($type) + $field_type_manager->getDefaultInstanceSettings($type);
    $field_definition->itemDefinition->setSettings($default_settings);
    return $field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromItemType($item_type) {
    // The data type of a field item is in the form of "field_item:$field_type".
    $parts = explode(':', $item_type, 2);
    return static::create($parts[1]);
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
    return $this->type;
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
   * Sets the maximum number of items allowed for the field.
   *
   * Possible values are positive integers or
   * FieldDefinitionInterface::CARDINALITY_UNLIMITED.
   *
   * @param int $cardinality
   *  The field cardinality.
   *
   * @return $this
   */
  public function setCardinality($cardinality) {
    $this->definition['cardinality'] = $cardinality;
    return $this;
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
  public function getPropertyDefinition($name) {
    if (!isset($this->propertyDefinitions)) {
      $this->getPropertyDefinitions();
    }
    if (isset($this->propertyDefinitions[$name])) {
      return $this->propertyDefinitions[$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $class = $this->getFieldItemClass();
      $this->propertyDefinitions = $class::propertyDefinitions($this);
    }
    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyNames() {
    return array_keys($this->getPropertyDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getMainPropertyName() {
    $class = $this->getFieldItemClass();
    return $class::mainPropertyName();
  }

  /**
   * Helper to retrieve the field item class.
   *
   * @todo: Remove once getClass() adds in defaults. See
   * https://drupal.org/node/2116341.
   */
  protected function getFieldItemClass() {
    if ($class = $this->getItemDefinition()->getClass()) {
      return $class;
    }
    else {
      $type_definition = \Drupal::typedDataManager()
        ->getDefinition($this->getItemDefinition()->getDataType());
      return $type_definition['class'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Do not serialize the statically cached property definitions.
    $vars = get_object_vars($this);
    unset($vars['propertyDefinitions']);
    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return isset($this->definition['entity_type']) ? $this->definition['entity_type'] : NULL;
  }

  /**
   * Sets the ID of the type of the entity this field is attached to.
   *
   * @param string $entity_type_id
   *   The name of the target entity type to set.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setTargetEntityTypeId($entity_type_id) {
    $this->definition['entity_type'] = $entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      // Get the schema from the field item class.
      $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->getType());
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
