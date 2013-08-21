<?php

/**
 * @file
 * Contains \Drupal\field\Entity\Field.
 */

namespace Drupal\field\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
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
 *   module = "field",
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
   * The maximum length of the field ID (machine name), in characters.
   *
   * For fields created through Field UI, this includes the 'field_' prefix.
   */
  const ID_MAX_LENGTH = 32;

  /**
   * The field ID (machine name).
   *
   * This is the name of the property under which the field values are placed in
   * an entity : $entity-{>$field_id}. The maximum length is
   * Field:ID_MAX_LENGTH.
   *
   * Example: body, field_main_image.
   *
   * @var string
   */
  public $id;

  /**
   * The field UUID.
   *
   * This is assigned automatically when the field is created.
   *
   * @var string
   */
  public $uuid;

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
   * Flag indicating whether the field type module is enabled.
   *
   * @var bool
   */
  public $active;

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
   * positive integers or FIELD_CARDINALITY_UNLIMITED. Defaults to 1.
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
   * The entity types on which the field is allowed to have instances.
   *
   * If empty or not specified, the field is allowed to have instances in any
   * entity type.
   *
   * @var array
   */
  public $entity_types = array();

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
   * The field storage definition.
   *
   * An array of key/value pairs identifying the storage backend to use for the
   * field:
   * - type: (string) The storage backend used by the field. Storage backends
   *   are defined by modules that implement hook_field_storage_info().
   * - settings: (array) A sub-array of key/value pairs of settings. The keys
   *   and default values are defined by the storage backend in the 'settings'
   *   entry of hook_field_storage_info().
   * - module: (string, read-only) The name of the module that implements the
   *   storage backend.
   * - active: (integer, read-only) TRUE if the module that implements the
   *   storage backend is currently enabled, FALSE otherwise.
   *
   * @var array
   */
  public $storage = array();

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
   * The storage information for the field.
   *
   * @var array
   */
  protected $storageDetails;

  /**
   * The original field.
   *
   * @var \Drupal\field\Entity\Field
   */
  public $original = NULL;

  /**
   * Constructs a Field object.
   *
   * @param array $values
   *   An array of field properties, keyed by property name. Most array
   *   elements will be used to set the corresponding properties on the class;
   *   see the class property documentation for details. Some array elements
   *   have special meanings and a few are required. Special elements are:
   *   - id: required. As a temporary Backwards Compatibility layer right now,
   *     a 'field_name' property can be accepted in place of 'id'.
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
    if (empty($values['type'])) {
      throw new FieldException('Attempt to create a field with no type.');
    }
    // Temporary BC layer: accept both 'id' and 'field_name'.
    // @todo $field_name and the handling for it will be removed in
    //   http://drupal.org/node/1953408.
    if (empty($values['field_name']) && empty($values['id'])) {
      throw new FieldException('Attempt to create an unnamed field.');
    }
    if (empty($values['id'])) {
      $values['id'] = $values['field_name'];
      unset($values['field_name']);
    }
    if (!preg_match('/^[_a-z]+[_a-z0-9]*$/', $values['id'])) {
      throw new FieldException('Attempt to create a field with invalid characters. Only lowercase alphanumeric characters and underscores are allowed, and only lowercase letters and underscore are allowed as the first character');
    }

    parent::__construct($values, $entity_type);
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
      'type',
      'settings',
      'module',
      'active',
      'entity_types',
      'storage',
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
   * Overrides \Drupal\Core\Entity\Entity::save().
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\field\FieldException
   *   If the field definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function save() {
    // Clear the derived data about the field.
    unset($this->schema, $this->storageDetails);

    if ($this->isNew()) {
      return $this->saveNew();
    }
    else {
      return $this->saveUpdated();
    }
  }

  /**
   * Saves a new field definition.
   *
   * @return int
   *   SAVED_NEW if the definition was saved.
   *
   * @throws \Drupal\field\FieldException
   *   If the field definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  protected function saveNew() {
    $module_handler = \Drupal::moduleHandler();
    $entity_manager = \Drupal::entityManager();
    $storage_controller = $entity_manager->getStorageController($this->entityType);

    // Field name cannot be longer than Field::ID_MAX_LENGTH characters. We
    // use drupal_strlen() because the DB layer assumes that column widths
    // are given in characters rather than bytes.
    if (drupal_strlen($this->id) > static::ID_MAX_LENGTH) {
      throw new FieldException(format_string(
        'Attempt to create a field with an ID longer than @max characters: %id', array(
          '@max' => static::ID_MAX_LENGTH,
          '%id' => $this->id,
        )
      ));
    }

    // Ensure the field name is unique (we do not care about deleted fields).
    if ($prior_field = $storage_controller->load($this->id)) {
      $message = $prior_field->active ?
        'Attempt to create field name %id which already exists and is active.' :
        'Attempt to create field name %id which already exists, although it is inactive.';
      throw new FieldException(format_string($message, array('%id' => $this->id)));
    }

    // Disallow reserved field names. This can't prevent all field name
    // collisions with existing entity properties, but some is better than
    // none.
    foreach ($entity_manager->getDefinitions() as $type => $info) {
      if (in_array($this->id, $info['entity_keys'])) {
        throw new FieldException(format_string('Attempt to create field %id which is reserved by entity type %type.', array('%id' => $this->id, '%type' => $type)));
      }
    }

    // Check that the field type is known.
    $field_type = \Drupal::service('plugin.manager.entity.field.field_type')->getDefinition($this->type);
    if (!$field_type) {
      throw new FieldException(format_string('Attempt to create a field of unknown type %type.', array('%type' => $this->type)));
    }
    $this->module = $field_type['provider'];
    $this->active = TRUE;

    // Make sure all settings are present, so that a complete field
    // definition is passed to the various hooks and written to config.
    $this->settings += $field_type['settings'];

    // Provide default storage.
    $this->storage += array(
      'type' => variable_get('field_storage_default', 'field_sql_storage'),
      'settings' => array(),
    );
    // Check that the storage type is known.
    $storage_type = field_info_storage_types($this->storage['type']);
    if (!$storage_type) {
      throw new FieldException(format_string('Attempt to create a field with unknown storage type %type.', array('%type' => $this->storage['type'])));
    }
    $this->storage['module'] = $storage_type['module'];
    $this->storage['active'] = TRUE;
    // Provide default storage settings.
    $this->storage['settings'] += $storage_type['settings'];

    // Invoke the storage backend's hook_field_storage_create_field().
    $module_handler->invoke($this->storage['module'], 'field_storage_create_field', array($this));

    // Save the configuration.
    $result = parent::save();
    field_cache_clear();

    return $result;
  }

  /**
   * Saves an updated field definition.
   *
   * @return int
   *   SAVED_UPDATED if the definition was saved.
   *
   * @throws \Drupal\field\FieldException
   *   If the field definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  protected function saveUpdated() {
    $module_handler = \Drupal::moduleHandler();
    $storage_controller = \Drupal::entityManager()->getStorageController($this->entityType);

    $original = $storage_controller->loadUnchanged($this->id());
    $this->original = $original;

    // Some updates are always disallowed.
    if ($this->type != $original->type) {
      throw new FieldException("Cannot change an existing field's type.");
    }
    if ($this->entity_types != $original->entity_types) {
      throw new FieldException("Cannot change an existing field's entity_types property.");
    }
    if ($this->storage['type'] != $original->storage['type']) {
      throw new FieldException("Cannot change an existing field's storage type.");
    }

    // Make sure all settings are present, so that a complete field definition
    // is saved. This allows calling code to perform partial updates on field
    // objects.
    $this->settings += $original->settings;

    // See if any module forbids the update by throwing an exception. This
    // invokes hook_field_update_forbid().
    $module_handler->invokeAll('field_update_forbid', array($this, $original));

    // Tell the storage engine to update the field by invoking the
    // hook_field_storage_update_field(). The storage engine can reject the
    // definition update as invalid by raising an exception, which stops
    // execution before the definition is written to config.
    $module_handler->invoke($this->storage['module'], 'field_storage_update_field', array($this, $original));

    // Save the configuration.
    $result = parent::save();
    field_cache_clear();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (!$this->deleted) {
      $module_handler = \Drupal::moduleHandler();
      $instance_controller = \Drupal::entityManager()->getStorageController('field_instance');
      $state = \Drupal::state();

      // Delete all non-deleted instances.
      $instance_ids = array();
      foreach ($this->getBundles() as $entity_type => $bundles) {
        foreach ($bundles as $bundle) {
          $instance_ids[] = "$entity_type.$bundle.$this->id";
        }
      }
      foreach ($instance_controller->loadMultiple($instance_ids) as $instance) {
        // By default, FieldInstance::delete() will automatically try to delete
        // a field definition when it is deleting the last instance of the
        // field. Since the whole field is being deleted here, pass FALSE as
        // the $field_cleanup parameter to prevent a loop.
        $instance->delete(FALSE);
      }

      // Mark field data for deletion by invoking
      // hook_field_storage_delete_field().
      $module_handler->invoke($this->storage['module'], 'field_storage_delete_field', array($this));

      // Delete the configuration of this field and save the field configuration
      // in the key_value table so we can use it later during
      // field_purge_batch(). This makes sure a new field can be created
      // immediately with the same name.
      $deleted_fields = $state->get('field.field.deleted') ?: array();
      $config = $this->getExportProperties();
      $config['deleted'] = TRUE;
      $deleted_fields[$this->uuid] = $config;
      $state->set('field.field.deleted', $deleted_fields);

      parent::delete();

      // Clear the cache.
      field_cache_clear();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      // Get the schema from the field item class.
      $definition = \Drupal::service('plugin.manager.entity.field.field_type')->getDefinition($this->type);
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
  public function getStorageDetails() {
    if (!isset($this->storageDetails)) {
      $module_handler = \Drupal::moduleHandler();

      // Collect the storage details from the storage backend, and let other
      // modules alter it. This invokes hook_field_storage_details() and
      // hook_field_storage_details_alter().
      $details = (array) $module_handler->invoke($this->storage['module'], 'field_storage_details', array($this));
      $module_handler->alter('field_storage_details', $details, $this);

      $this->storageDetails = $details;
    }

    return $this->storageDetails;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    if (empty($this->deleted)) {
      $map = field_info_field_map();
      if (isset($map[$this->id]['bundles'])) {
        return $map[$this->id]['bundles'];
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->id;
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
    $field_type_info = \Drupal::service('plugin.manager.entity.field.field_type')->getDefinition($this->type);

    $settings = $this->settings + $field_type_info['settings'] + $field_type_info['instance_settings'];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSetting($setting_name) {
    // @todo See getFieldSettings() about potentially statically caching this.
    $field_type_info = \Drupal::service('plugin.manager.entity.field.field_type')->getDefinition($this->type);

    // We assume here that consecutive array_key_exists() is more efficient than
    // calling getFieldSettings() when all we need is a single setting.
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    elseif (array_key_exists($setting_name, $field_type_info['settings'])) {
      return $field_type_info['settings'][$setting_name];
    }
    else {
      return $field_type_info['instance_settings'][$setting_name];
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
   * {@inheritdoc}
   */
  public function getFieldLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDescription() {
    return '';
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
  public function getFieldDefaultValue(EntityInterface $entity) { }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->{$offset}) || in_array($offset, array('columns', 'foreign keys', 'bundles', 'storage_details'));
  }

  /**
   * {@inheritdoc}
   */
  public function &offsetGet($offset) {
    switch ($offset) {
      case 'id':
        return $this->uuid;

      case 'field_name':
        return $this->id;

      case 'columns':
        $this->getSchema();
        return $this->schema['columns'];

      case 'foreign keys':
        $this->getSchema();
        return $this->schema['foreign keys'];

      case 'bundles':
        $bundles = $this->getBundles();
        return $bundles;

      case 'storage_details':
        $this->getStorageDetails();
        return $this->storageDetails;
    }

    return $this->{$offset};
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!in_array($offset, array('columns', 'foreign keys', 'bundles', 'storage_details'))) {
      $this->{$offset} = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    if (!in_array($offset, array('columns', 'foreign keys', 'bundles', 'storage_details'))) {
      unset($this->{$offset});
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    // Only store the definition, not external objects or derived data.
    return serialize($this->getExportProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $this->__construct(unserialize($serialized));
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
    $storage_details = $this->getSchema();
    $columns = array_keys($storage_details['columns']);
    $factory = \Drupal::service('entity.query');
    foreach ($this->getBundles() as $entity_type => $bundle) {
      // Entity Query throws an exception if there is no base table.
      $entity_info = \Drupal::entityManager()->getDefinition($entity_type);
      if (!isset($entity_info['base_table'])) {
        continue;
      }
      $query = $factory->get($entity_type);
      $group = $query->orConditionGroup();
      foreach ($columns as $column) {
        $group->exists($this->id() . '.' . $column);
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
}
