<?php

/**
 * @file
 * Contains \Drupal\field\Entity\FieldConfig.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\field\FieldException;
use Drupal\field\FieldConfigInterface;

/**
 * Defines the Field entity.
 *
 * @ConfigEntityType(
 *   id = "field_config",
 *   label = @Translation("Field"),
 *   controllers = {
 *     "storage" = "Drupal\field\FieldConfigStorageController"
 *   },
 *   config_prefix = "field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   }
 * )
 */
class FieldConfig extends ConfigEntityBase implements FieldConfigInterface {

  /**
   * The maximum length of the field name, in characters.
   *
   * For fields created through Field UI, this includes the 'field_' prefix.
   */
  const NAME_MAX_LENGTH = 32;

  /**
   * The field ID.
   *
   * The ID consists of 2 parts: the entity type and the field name.
   *
   * Example: node.body, user.field_main_image.
   *
   * @var string
   */
  public $id;

  /**
   * The field name.
   *
   * This is the name of the property under which the field values are placed in
   * an entity: $entity->{$field_name}. The maximum length is
   * Field:NAME_MAX_LENGTH.
   *
   * Example: body, field_main_image.
   *
   * @var string
   */
  public $name;

  /**
   * The name of the entity type the field can be attached to.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The field type.
   *
   * Example: text, integer.
   *
   * @var string
   */
  public $type;

  /**
   * The name of the module that provides the field type.
   *
   * @var string
   */
  public $module;

  /**
   * Field-type specific settings.
   *
   * An array of key/value pairs, The keys and default values are defined by the
   * field type.
   *
   * @var array
   */
  public $settings = array();

  /**
   * The field cardinality.
   *
   * The maximum number of values the field can hold. Possible values are
   * positive integers or FieldDefinitionInterface::CARDINALITY_UNLIMITED.
   * Defaults to 1.
   *
   * @var integer
   */
  public $cardinality = 1;

  /**
   * Flag indicating whether the field is translatable.
   *
   * Defaults to FALSE.
   *
   * @var bool
   */
  public $translatable = FALSE;

  /**
   * Flag indicating whether the field is available for editing.
   *
   * If TRUE, some actions not available though the UI (but are still possible
   * through direct API manipulation):
   * - field settings cannot be changed,
   * - new instances cannot be created
   * - existing instances cannot be deleted.
   * Defaults to FALSE.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * The custom storage indexes for the field data storage.
   *
   * This set of indexes is merged with the "default" indexes specified by the
   * field type in hook_field_schema() to determine the actual set of indexes
   * that get created.
   *
   * The indexes are defined using the same definition format as Schema API
   * index specifications. Only columns that are part of the field schema, as
   * defined by the field type in hook_field_schema(), are allowed.
   *
   * Some storage backends might not support indexes, and discard that
   * information.
   *
   * @var array
   */
  public $indexes = array();

  /**
   * Flag indicating whether the field is deleted.
   *
   * The delete() method marks the field as "deleted" and removes the
   * corresponding entry from the config storage, but keeps its definition in
   * the state storage while field data is purged by a separate
   * garbage-collection process.
   *
   * Deleted fields stay out of the regular entity lifecycle (notably, their
   * values are not populated in loaded entities, and are not saved back).
   *
   * @var bool
   */
  public $deleted = FALSE;

  /**
   * The field schema.
   *
   * @var array
   */
  protected $schema;

  /**
   * An array of field property definitions.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface[]
   *
   * @see \Drupal\Core\TypedData\ComplexDataDefinitionInterface::getPropertyDefinitions()
   */
  protected $propertyDefinitions;

  /**
   * The data definition of a field item.
   *
   * @var \Drupal\Core\TypedData\DataDefinition
   */
  protected $itemDefinition;

  /**
   * Constructs a FieldConfig object.
   *
   * @param array $values
   *   An array of field properties, keyed by property name. Most array
   *   elements will be used to set the corresponding properties on the class;
   *   see the class property documentation for details. Some array elements
   *   have special meanings and a few are required. Special elements are:
   *   - name: required. As a temporary Backwards Compatibility layer right now,
   *     a 'field_name' property can be accepted in place of 'id'.
   *   - entity_type: required.
   *   - type: required.
   *
   * In most cases, Field entities are created via
   * entity_create('field_config', $values)), where $values is the same
   * parameter as in this constructor.
   *
   * @see entity_create()
   *
   * @ingroup field_crud
   */
  public function __construct(array $values, $entity_type = 'field_config') {
    // Check required properties.
    if (empty($values['name'])) {
      throw new FieldException('Attempt to create an unnamed field.');
    }
    if (!preg_match('/^[_a-z]+[_a-z0-9]*$/', $values['name'])) {
      throw new FieldException(format_string('Attempt to create a field @field_name with invalid characters. Only lowercase alphanumeric characters and underscores are allowed, and only lowercase letters and underscore are allowed as the first character', array('@field_name' => $values['name'])));
    }
    if (empty($values['type'])) {
      throw new FieldException(format_string('Attempt to create field @field_name with no type.', array('@field_name' => $values['name'])));
    }
    if (empty($values['entity_type'])) {
      throw new FieldException(format_string('Attempt to create a field @field_name with no entity_type.', array('@field_name' => $values['name'])));
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $names = array(
      'id',
      'uuid',
      'status',
      'langcode',
      'name',
      'entity_type',
      'type',
      'settings',
      'module',
      'locked',
      'cardinality',
      'translatable',
      'indexes',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::preSave().
   *
   * @throws \Drupal\field\FieldException
   *   If the field definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    // Clear the derived data about the field.
    unset($this->schema);

    if ($this->isNew()) {
      return $this->preSaveNew($storage_controller);
    }
    else {
      return $this->preSaveUpdated($storage_controller);
    }
  }

  /**
   * Prepares saving a new field definition.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller.
   *
   * @throws \Drupal\field\FieldException If the field definition is invalid.
   */
   protected function preSaveNew(EntityStorageControllerInterface $storage_controller) {
    $entity_manager = \Drupal::entityManager();

    // Assign the ID.
    $this->id = $this->id();

    // Field name cannot be longer than FieldConfig::NAME_MAX_LENGTH characters.
    // We use Unicode::strlen() because the DB layer assumes that column widths
    // are given in characters rather than bytes.
    if (Unicode::strlen($this->name) > static::NAME_MAX_LENGTH) {
      throw new FieldException(format_string(
        'Attempt to create a field with an ID longer than @max characters: %name', array(
          '@max' => static::NAME_MAX_LENGTH,
          '%name' => $this->name,
        )
      ));
    }

    // Disallow reserved field names.
    $disallowed_field_names = array_keys($entity_manager->getBaseFieldDefinitions($this->entity_type));
    if (in_array($this->name, $disallowed_field_names)) {
      throw new FieldException(format_string('Attempt to create field %name which is reserved by entity type %type.', array('%name' => $this->name, '%type' => $this->entity_type)));
    }

    // Check that the field type is known.
    $field_type = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);
    if (!$field_type) {
      throw new FieldException(format_string('Attempt to create a field of unknown type %type.', array('%type' => $this->type)));
    }
    $this->module = $field_type['provider'];

    // Make sure all settings are present, so that a complete field
    // definition is passed to the various hooks and written to config.
    $this->settings += $field_type['settings'];

    // Notify the entity storage controller.
    $entity_manager->getStorageController($this->entity_type)->onFieldCreate($this);
  }

  /**
   * Prepares saving an updated field definition.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller.
   */
  protected function preSaveUpdated(EntityStorageControllerInterface $storage_controller) {
    $module_handler = \Drupal::moduleHandler();
    $entity_manager = \Drupal::entityManager();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // Some updates are always disallowed.
    if ($this->type != $this->original->type) {
      throw new FieldException("Cannot change an existing field's type.");
    }
    if ($this->entity_type != $this->original->entity_type) {
      throw new FieldException("Cannot change an existing field's entity_type.");
    }

    // Make sure all settings are present, so that a complete field
    // definition is passed to the various hooks and written to config.
    $this->settings += $field_type_manager->getDefaultSettings($this->type);

    // See if any module forbids the update by throwing an exception. This
    // invokes hook_field_config_update_forbid().
    $module_handler->invokeAll('field_config_update_forbid', array($this, $this->original));

    // Notify the storage controller. The controller can reject the definition
    // update as invalid by raising an exception, which stops execution before
    // the definition is written to config.
    $entity_manager->getStorageController($this->entity_type)->onFieldUpdate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Clear the cache.
    field_cache_clear();

    if ($update) {
      // Invalidate the render cache for all affected entities.
      $entity_manager = \Drupal::entityManager();
      $entity_type = $this->getTargetEntityTypeId();
      if ($entity_manager->hasController($entity_type, 'view_builder')) {
        $entity_manager->getViewBuilder($entity_type)->resetCache();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $fields) {
    $state = \Drupal::state();
    $instance_controller = \Drupal::entityManager()->getStorageController('field_instance_config');

    // Delete instances first. Note: when deleting a field through
    // FieldInstanceConfig::postDelete(), the instances have been deleted already, so
    // no instances will be found here.
    $instance_ids = array();
    foreach ($fields as $field) {
      if (!$field->deleted) {
        foreach ($field->getBundles() as $bundle) {
          $instance_ids[] = "{$field->entity_type}.$bundle.{$field->name}";
        }
      }
    }
    if ($instance_ids) {
      $instances = $instance_controller->loadMultiple($instance_ids);
      // Tag the objects to preserve recursive deletion of the field.
      foreach ($instances as $instance) {
        $instance->noFieldDelete = TRUE;
      }
      $instance_controller->delete($instances);
    }

    // Keep the field definitions in the state storage so we can use them later
    // during field_purge_batch().
    $deleted_fields = $state->get('field.field.deleted') ?: array();
    foreach ($fields as $field) {
      if (!$field->deleted) {
        $config = $field->getExportProperties();
        $config['deleted'] = TRUE;
        $config['bundles'] = $field->getBundles();
        $deleted_fields[$field->uuid] = $config;
      }
    }
    $state->set('field.field.deleted', $deleted_fields);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $fields) {
    // Notify the storage.
    foreach ($fields as $field) {
      if (!$field->deleted) {
        \Drupal::entityManager()->getStorageController($field->entity_type)->onFieldDelete($field);
      }
    }

    // Clear the cache.
    field_cache_clear();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      // Get the schema from the field item class.
      $class = $this->getFieldItemClass();
      $schema = $class::schema($this);
      // Fill in default values for optional entries.
      $schema += array('indexes' => array(), 'foreign keys' => array());

      // Check that the schema does not include forbidden column names.
      if (array_intersect(array_keys($schema['columns']), static::getReservedColumns())) {
        throw new FieldException(format_string('Illegal field type @field_type on @field_name.', array('@field_type' => $this->type, '@field_name' => $this->name)));
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
   * {@inheritdoc}
   */
  public function getBundles() {
    if (empty($this->deleted)) {
      $map = field_info_field_map();
      if (isset($map[$this->entity_type][$this->name]['bundles'])) {
        return $map[$this->entity_type][$this->name]['bundles'];
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
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
    // @todo FieldTypePluginManager maintains its own static cache. However, do
    //   some CPU and memory profiling to see if it's worth statically caching
    //   $field_type_info, or the default field and instance settings, within
    //   $this.
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);

    $settings = $this->settings + $field_type_info['settings'] + $field_type_info['instance_settings'];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    // @todo See getSettings() about potentially statically caching this.
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);

    // We assume here that consecutive array_key_exists() is more efficient than
    // calling getSettings() when all we need is a single setting.
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    elseif (array_key_exists($setting_name, $field_type_info['settings'])) {
      return $field_type_info['settings'][$setting_name];
    }
    elseif (array_key_exists($setting_name, $field_type_info['instance_settings'])) {
      return $field_type_info['instance_settings'][$setting_name];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->translatable;
  }

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return $this
   */
  public function setTranslatable($translatable) {
    $this->translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardinality() {
    return $this->cardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return FALSE;
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
  public function isLocked() {
    return $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue(EntityInterface $entity) { }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayConfigurable($context) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayOptions($display_context) {
    // Hide configurable fields by default.
    return array('type' => 'hidden');
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function isQueryable() {
    return TRUE;
  }

  /**
   * A list of columns that can not be used as field type columns.
   *
   * @return array
   */
  public static function getReservedColumns() {
    return array('deleted');
  }

  /**
   * Determines whether a field has any data.
   *
   * @return
   *   TRUE if the field has data for any entity; FALSE otherwise.
   */
  public function hasData() {
    if ($this->getBundles()) {
      $storage_details = $this->getSchema();
      $columns = array_keys($storage_details['columns']);
      $factory = \Drupal::service('entity.query');
      // Entity Query throws an exception if there is no base table.
      $entity_type = \Drupal::entityManager()->getDefinition($this->entity_type);
      if (!$entity_type->getBaseTable()) {
        return FALSE;
      }
      $query = $factory->get($this->entity_type);
      $group = $query->orConditionGroup();
      foreach ($columns as $column) {
        $group->exists($this->name . '.' . $column);
      }
      $result = $query
        ->condition($group)
        ->count()
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();
      if ($result) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Using the Serialize interface and serialize() / unserialize() methods
   * breaks entity forms in PHP 5.4.
   * @todo Investigate in https://drupal.org/node/2074253.
   */
  public function __sleep() {
    // Only serialize properties from getExportProperties().
    return array_keys(array_intersect_key($this->getExportProperties(), get_object_vars($this)));
  }

  /**
   * Implements the magic __wakeup() method.
   */
  public function __wakeup() {
    // Run the values from getExportProperties() through __construct().
    $values = array_intersect_key($this->getExportProperties(), get_object_vars($this));
    $this->__construct($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return FieldDefinition::createFromDataType($type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromItemType($item_type) {
    // Forward to the field definition class for creating new data definitions
    // via the typed manager.
    return FieldDefinition::createFromItemType($item_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function isList() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    // Derive list class from the field type.
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($this->getType());
    return $type_definition['list_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraint($constraint_name) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    if (!isset($this->itemDefinition)) {
      $this->itemDefinition = FieldItemDataDefinition::create($this)
        ->setSettings($this->getSettings());
    }
    return $this->itemDefinition;
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

}
