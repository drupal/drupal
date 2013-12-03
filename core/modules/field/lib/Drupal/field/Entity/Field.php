<?php

/**
 * @file
 * Contains \Drupal\field\Entity\Field.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\FieldException;
use Drupal\field\FieldInterface;

/**
 * Defines the Field entity.
 *
 * @todo use 'field' as the id once hook_field_load() and friends
 * are removed.
 *
 * @EntityType(
 *   id = "field_entity",
 *   label = @Translation("Field"),
 *   controllers = {
 *     "storage" = "Drupal\field\FieldStorageController"
 *   },
 *   config_prefix = "field.field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Field extends ConfigEntityBase implements FieldInterface {

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
   * The field UUID.
   *
   * This is assigned automatically when the field is created.
   *
   * @var string
   */
  public $uuid;

  /**
   * The name of the entity type the field can be attached to.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The field type.
   *
   * Example: text, number_integer.
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
   * The original field.
   *
   * @var \Drupal\field\Entity\Field
   */
  public $original = NULL;

  /**
   * The data definition of a field item.
   *
   * @var \Drupal\Core\TypedData\DataDefinition
   */
  protected $itemDefinition;

  /**
   * Constructs a Field object.
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
   * entity_create('field_entity', $values)), where $values is the same
   * parameter as in this constructor.
   *
   * @see entity_create()
   *
   * @ingroup field_crud
   */
  public function __construct(array $values, $entity_type = 'field_entity') {
    // Check required properties.
    if (empty($values['name'])) {
      throw new FieldException('Attempt to create an unnamed field.');
    }
    if (!preg_match('/^[_a-z]+[_a-z0-9]*$/', $values['name'])) {
      throw new FieldException('Attempt to create a field with invalid characters. Only lowercase alphanumeric characters and underscores are allowed, and only lowercase letters and underscore are allowed as the first character');
    }
    if (empty($values['type'])) {
      throw new FieldException('Attempt to create a field with no type.');
    }
    if (empty($values['entity_type'])) {
      throw new FieldException('Attempt to create a field with no entity_type.');
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

    // Field name cannot be longer than Field::NAME_MAX_LENGTH characters. We
    // use Unicode::strlen() because the DB layer assumes that column widths
    // are given in characters rather than bytes.
    if (Unicode::strlen($this->name) > static::NAME_MAX_LENGTH) {
      throw new FieldException(format_string(
        'Attempt to create a field with an ID longer than @max characters: %name', array(
          '@max' => static::NAME_MAX_LENGTH,
          '%name' => $this->name,
        )
      ));
    }

    // Ensure the field name is unique (we do not care about deleted fields).
    if ($prior_field = $storage_controller->load($this->id)) {
      $message = 'Attempt to create field name %name which already exists.';
      throw new FieldException(format_string($message, array('%name' => $this->name)));
    }

    // Disallow reserved field names. This can't prevent all field name
    // collisions with existing entity properties, but some is better than
    // none.
    foreach ($entity_manager->getDefinitions() as $type => $info) {
      if (in_array($this->name, $info['entity_keys'])) {
        throw new FieldException(format_string('Attempt to create field %name which is reserved by entity type %type.', array('%name' => $this->name, '%type' => $type)));
      }
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
    // invokes hook_field_update_forbid().
    $module_handler->invokeAll('field_update_forbid', array($this, $this->original));

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
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $fields) {
    $state = \Drupal::state();
    $instance_controller = \Drupal::entityManager()->getStorageController('field_instance');

    // Delete instances first. Note: when deleting a field through
    // FieldInstance::postDelete(), the instances have been deleted already, so
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
      $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);
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
  public function getFieldName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettings() {
    // @todo field_info_field_types() calls _field_info_collate_types() which
    //   maintains its own static cache. However, do some CPU and memory
    //   profiling to see if it's worth statically caching $field_type_info, or
    //   the default field and instance settings, within $this.
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);

    $settings = $this->settings + $field_type_info['settings'] + $field_type_info['instance_settings'];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSetting($setting_name) {
    // @todo See getFieldSettings() about potentially statically caching this.
    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->type);

    // We assume here that consecutive array_key_exists() is more efficient than
    // calling getFieldSettings() when all we need is a single setting.
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
  public function getFieldPropertyNames() {
    $schema = $this->getSchema();
    return array_keys($schema['columns']);
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldTranslatable() {
    return $this->translatable;
  }

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return \Drupal\field\Entity\Field
   *   The object itself for chaining.
   */
  public function setTranslatable($translatable) {
    $this->translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCardinality() {
    return $this->cardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldMultiple() {
    $cardinality = $this->getFieldCardinality();
    return ($cardinality == static::CARDINALITY_UNLIMITED) || ($cardinality > 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefaultValue(EntityInterface $entity) { }

  /**
   * {@inheritdoc}
   */
  public function isFieldConfigurable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldQueryable() {
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
      $entity_info = \Drupal::entityManager()->getDefinition($this->entity_type);
      if (!isset($entity_info['base_table'])) {
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
  public function getDataType() {
    return 'list';
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
    return $this->getFieldDescription();
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
  public function isRequired() {
    return $this->isFieldRequired();
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    // Derive list class from the field type.
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($this->getFieldType());
    return $type_definition['list_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // This should actually return the settings for field item list, which are
    // not the field settings. However, there is no harm in returning field
    // settings here, so we do that to avoid confusion for now.
    // @todo: Unify with getFieldSettings() or remove once typed data moved
    // to the adapter approach.
    return $this->getFieldSettings();
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
  public function getItemDefinition() {
    if (!isset($this->itemDefinition)) {
      $this->itemDefinition = DataDefinition::create('field_item:' . $this->type)
        ->setSettings($this->getFieldSettings());
    }
    return $this->itemDefinition;
  }

}
