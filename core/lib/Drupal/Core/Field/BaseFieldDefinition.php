<?php

/**
 * @file
 * Contains \Drupal\Core\Field\BaseFieldDefinition.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * A class for defining entity fields.
 */
class BaseFieldDefinition extends ListDataDefinition implements FieldDefinitionInterface, FieldStorageDefinitionInterface {

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
    $default_settings = $field_type_manager->getDefaultStorageSettings($type) + $field_type_manager->getDefaultFieldSettings($type);
    $field_definition->itemDefinition->setSettings($default_settings);
    return $field_definition;
  }

  /**
   * Creates a new field definition based upon a field storage definition.
   *
   * In cases where one needs a field storage definitions to act like full
   * field definitions, this creates a new field definition based upon the
   * (limited) information available. That way it is possible to use the field
   * definition in places where a full field definition is required; e.g., with
   * widgets or formatters.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition to base the new field definition upon.
   *
   * @return $this
   */
  public static function createFromFieldStorageDefinition(FieldStorageDefinitionInterface $definition) {
    return static::create($definition->getType())
      ->setCardinality($definition->getCardinality())
      ->setConstraints($definition->getConstraints())
      ->setCustomStorage($definition->hasCustomStorage())
      ->setDescription($definition->getDescription())
      ->setLabel($definition->getLabel())
      ->setName($definition->getName())
      ->setProvider($definition->getProvider())
      ->setQueryable($definition->isQueryable())
      ->setRevisionable($definition->isRevisionable())
      ->setSettings($definition->getSettings())
      ->setTargetEntityTypeId($definition->getTargetEntityTypeId())
      ->setTranslatable($definition->isTranslatable());
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
  public function getProvider() {
    return isset($this->definition['provider']) ? $this->definition['provider'] : NULL;
  }

  /**
   * Sets the name of the provider of this field.
   *
   * @param string $provider
   *   The provider name to set.
   *
   * @return $this
   */
  public function setProvider($provider) {
    $this->definition['provider'] = $provider;
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
   * @return $this
   *   The object itself for chaining.
   */
  public function setTranslatable($translatable) {
    $this->definition['translatable'] = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionable() {
    return !empty($this->definition['revisionable']);
  }

  /**
   * Sets whether the field is revisionable.
   *
   * @param bool $revisionable
   *   Whether the field is revisionable.
   *
   * @return $this
   *   The object itself for chaining.
   */
  public function setRevisionable($revisionable) {
    $this->definition['revisionable'] = $revisionable;
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
   * FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED.
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
   * Note: this overwrites any existing property constraints. If you need to
   * add to the existing constraints, use
   * \Drupal\Core\Field\BaseFieldDefinition::addPropertyConstraints()
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
   * Adds constraints for a given field item property.
   *
   * Adds a constraint to a property of a base field item. e.g.
   * @code
   * // Limit the field item's value property to the range 0 through 10.
   * // e.g. $node->size->value.
   * $field->addPropertyConstraints('value', [
   *   'Range' => [
   *     'min' => 0,
   *     'max' => 10,
   *   ]
   * ]);
   * @endcode
   *
   * If you want to add a validation constraint that applies to the
   * \Drupal\Core\Field\FieldItemList, use BaseFieldDefinition::addConstraint()
   * instead.
   *
   * Note: passing a new set of options for an existing property constraint will
   * overwrite with the new options.
   *
   * @param string $name
   *   The name of the property to set constraints for.
   * @param array $constraints
   *   The constraints to set.
   *
   * @return static
   *   The object itself for chaining.
   *
   * @see \Drupal\Core\Field\BaseFieldDefinition::addConstraint()
   */
  public function addPropertyConstraints($name, array $constraints) {
    $item_constraints = $this->getItemDefinition()->getConstraint('ComplexData') ?: [];
    if (isset($item_constraints[$name])) {
      // Add the new property constraints, overwriting as required.
      $item_constraints[$name] = $constraints + $item_constraints[$name];
    }
    else {
      $item_constraints[$name] = $constraints;
    }
    $this->getItemDefinition()->addConstraint('ComplexData', $item_constraints);
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
  public function getDefaultValue(FieldableEntityInterface $entity) {
    // Allow custom default values function.
    if (!empty($this->definition['default_value_callback'])) {
      $value = call_user_func($this->definition['default_value_callback'], $entity, $this);
    }
    else {
      $value = isset($this->definition['default_value']) ? $this->definition['default_value'] : NULL;
    }
    // Normalize into the "array keyed by delta" format.
    if (isset($value) && !is_array($value)) {
      $properties = $this->getPropertyNames();
      $property = reset($properties);
      $value = array(
        array($property => $value),
      );
    }
    // Allow the field type to process default values.
    $field_item_list_class = $this->getClass();
    return $field_item_list_class::processDefaultValue($value, $entity, $this);
  }

  /**
   * Sets a custom default value callback.
   *
   * If set, the callback overrides any set default value.
   *
   * @param string|null $callback
   *   The callback to invoke for getting the default value (pass NULL to unset
   *   a previously set callback). The callback will be invoked with the
   *   following arguments:
   *   - \Drupal\Core\Entity\FieldableEntityInterface $entity
   *     The entity being created.
   *   - \Drupal\Core\Field\FieldDefinitionInterface $definition
   *     The field definition.
   *   It should return the default value in the format accepted by the
   *   setDefaultValue() method.
   *
   * @return $this
   */
  public function setDefaultValueCallback($callback) {
    if (isset($callback) && !is_string($callback)) {
      throw new \InvalidArgumentException('Default value callback must be a string, like "function_name" or "ClassName::methodName"');
    }
    $this->definition['default_value_callback'] = $callback;
    return $this;
  }

  /**
   * Sets a default value.
   *
   * Note that if a default value callback is set, it will take precedence over
   * any value set here.
   *
   * @param mixed $value
   *   The default value for the field. This can be either:
   *   - a literal, in which case it will be assigned to the first property of
   *     the first item.
   *   - a numerically indexed array of items, each item being a property/value
   *     array.
   *   - a non-numerically indexed array, in which case the array is assumed to
   *     be a property/value array and used as the first item
   *   - NULL or array() for no default value.
   *
   * @return $this
   */
  public function setDefaultValue($value) {
    // Unless the value is NULL or an empty array, we may need to transform it.
    if (!(is_null($value) || (is_array($value) && empty($value)))) {
      if (!is_array($value)) {
        $value = array(array($this->getMainPropertyName() => $value));
      }
      elseif (!is_numeric(array_keys($value)[0])) {
        $value = array(0 => $value);
      }
    }
    $this->definition['default_value'] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsProvider($property_name, FieldableEntityInterface $entity) {
    // If the field item class implements the interface, create an orphaned
    // runtime item object, so that it can be used as the options provider
    // without modifying the entity being worked on.
    if (is_subclass_of($this->getFieldItemClass(), '\Drupal\Core\TypedData\OptionsProviderInterface')) {
      $items = $entity->get($this->getName());
      return \Drupal::service('plugin.manager.field.field_type')->createFieldItem($items, 0);
    }
    // @todo: Allow setting custom options provider, see
    // https://www.drupal.org/node/2002138.
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
   * @return $this
   */
  public function setTargetEntityTypeId($entity_type_id) {
    $this->definition['entity_type'] = $entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return isset($this->definition['bundle']) ? $this->definition['bundle'] : NULL;
  }

  /**
   * Sets the bundle this field is defined for.
   *
   * @param string|null $bundle
   *   The bundle, or NULL if the field is not bundle-specific.
   *
   * @return $this
   */
  public function setTargetBundle($bundle) {
    $this->definition['bundle'] = $bundle;
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
      // Fill in default values.
      $schema += array(
        'columns' => array(),
        'unique keys' => array(),
        'indexes' => array(),
        'foreign keys' => array(),
      );

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
   * {@inheritdoc}
   */
  public function hasCustomStorage() {
    return !empty($this->definition['custom_storage']) || $this->isComputed();
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return TRUE;
  }

  /**
   * Sets the storage behavior for this field.
   *
   * @param bool $custom_storage
   *   Pass FALSE if the storage takes care of storing the field,
   *   TRUE otherwise.
   *
   * @return $this
   *
   * @throws \LogicException
   *   Thrown if custom storage is to be set to FALSE for a computed field.
   */
  public function setCustomStorage($custom_storage) {
    if (!$custom_storage && $this->isComputed()) {
      throw new \LogicException("Entity storage cannot store a computed field.");
    }
    $this->definition['custom_storage'] = $custom_storage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueStorageIdentifier() {
    return $this->getTargetEntityTypeId() . '-' . $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($bundle) {
    $override = BaseFieldOverride::loadByName($this->getTargetEntityTypeId(), $bundle, $this->getName());
    if ($override) {
      return $override;
    }
    return BaseFieldOverride::createFromBaseFieldDefinition($this, $bundle);
  }

}
