<?php

/**
 * @file
 * Contains \Drupal\field\Entity\Field.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\Unicode;
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
      'active',
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
    unset($this->schema);

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
    $entity_manager = \Drupal::entityManager();
    $storage_controller = $entity_manager->getStorageController($this->entityType);

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
      $message = $prior_field->active ?
        'Attempt to create field name %name which already exists and is active.' :
        'Attempt to create field name %name which already exists, although it is inactive.';
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
    $this->active = TRUE;

    // Make sure all settings are present, so that a complete field
    // definition is passed to the various hooks and written to config.
    $this->settings += $field_type['settings'];

    // Notify the entity storage controller.
    $entity_manager->getStorageController($this->entity_type)->onFieldCreate($this);

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
    $entity_manager = \Drupal::entityManager();
    $storage_controller = $entity_manager->getStorageController($this->entityType);

    $original = $storage_controller->loadUnchanged($this->id());
    $this->original = $original;

    // Some updates are always disallowed.
    if ($this->type != $original->type) {
      throw new FieldException("Cannot change an existing field's type.");
    }
    if ($this->entity_type != $original->entity_type) {
      throw new FieldException("Cannot change an existing field's entity_type.");
    }

    // Make sure all settings are present, so that a complete field definition
    // is saved. This allows calling code to perform partial updates on field
    // objects.
    $this->settings += $original->settings;

    // See if any module forbids the update by throwing an exception. This
    // invokes hook_field_update_forbid().
    $module_handler->invokeAll('field_update_forbid', array($this, $original));

    // Notify the storage controller. The controller can reject the definition
    // update as invalid by raising an exception, which stops execution before
    // the definition is written to config.
    $entity_manager->getStorageController($this->entity_type)->onFieldUpdate($this, $original);

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
      $instance_controller = \Drupal::entityManager()->getStorageController('field_instance');
      $state = \Drupal::state();

      // Delete all non-deleted instances.
      $instance_ids = array();
      foreach ($this->getBundles() as $bundle) {
        $instance_ids[] = "{$this->entity_type}.$bundle.{$this->name}";
      }
      foreach ($instance_controller->loadMultiple($instance_ids) as $instance) {
        // By default, FieldInstance::delete() will automatically try to delete
        // a field definition when it is deleting the last instance of the
        // field. Since the whole field is being deleted here, pass FALSE as
        // the $field_cleanup parameter to prevent a loop.
        $instance->delete(FALSE);
      }

      \Drupal::entityManager()->getStorageController($this->entity_type)->onFieldDelete($this);

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
  public function isFieldMultiple() {
    $cardinality = $this->getFieldCardinality();
    return ($cardinality == static::CARDINALITY_UNLIMITED) || ($cardinality > 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefaultValue(EntityInterface $entity) { }

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
  public function isFieldConfigurable() {
    return TRUE;
  }

}
