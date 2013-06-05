<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Core\Entity\Field.
 */

namespace Drupal\field\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
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
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
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
   * Field types are defined by modules that implement hook_field_info().
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
   * field type in the 'settings' entry of hook_field_info().
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function save() {
    $module_handler = \Drupal::moduleHandler();
    $storage_controller = \Drupal::entityManager()->getStorageController($this->entityType);

    // Clear the derived data about the field.
    unset($this->schema, $this->storageDetails);

    if ($this->isNew()) {
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
      if ($prior_field = current($storage_controller->load(array($this->id)))) {
        $message = $prior_field->active ?
          'Attempt to create field name %id which already exists and is active.' :
          'Attempt to create field name %id which already exists, although it is inactive.';
        throw new FieldException(format_string($message, array('%id' => $this->id)));
      }

      // Disallow reserved field names. This can't prevent all field name
      // collisions with existing entity properties, but some is better than
      // none.
      foreach (\Drupal::entityManager()->getDefinitions() as $type => $info) {
        if (in_array($this->id, $info['entity_keys'])) {
          throw new FieldException(format_string('Attempt to create field %id which is reserved by entity type %type.', array('%id' => $this->id, '%type' => $type)));
        }
      }

      // Check that the field type is known.
      $field_type = field_info_field_types($this->type);
      if (!$field_type) {
        throw new FieldException(format_string('Attempt to create a field of unknown type %type.', array('%type' => $this->type)));
      }
      $this->module = $field_type['module'];
      $this->active = TRUE;

      // Make sure all settings are present, so that a complete field
      // definition is passed to the various hooks and written to config.
      $this->settings += $field_type['settings'];

      // Provide default storage.
      $this->storage += array(
        'type' => config('field.settings')->get('default_storage'),
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

      $hook = 'field_create_field';
      $hook_args = array($this);
    }
    // Otherwise, the field is being updated.
    else {
      $original = $storage_controller->loadUnchanged($this->id());

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

      // Make sure all settings are present, so that a complete field
      // definition is saved. This allows calling code to perform partial
      // updates on a field object.
      $this->settings += $original->settings;

      $has_data = field_has_data($this);

      // See if any module forbids the update by throwing an exception. This
      // invokes hook_field_update_forbid().
      $module_handler->invokeAll('field_update_forbid', array($this, $original, $has_data));

      // Tell the storage engine to update the field by invoking the
      // hook_field_storage_update_field(). The storage engine can reject the
      // definition update as invalid by raising an exception, which stops
      // execution before the definition is written to config.
      $module_handler->invoke($this->storage['module'], 'field_storage_update_field', array($this, $original, $has_data));

      $hook = 'field_update_field';
      $hook_args = array($this, $original, $has_data);
    }

    // Save the configuration.
    $result = parent::save();
    field_cache_clear();

    // Invoke external hooks after the cache is cleared for API consistency.
    // This invokes either hook_field_create_field() or
    // hook_field_update_field() depending on whether the field is new.
    $module_handler->invokeAll($hook, $hook_args);

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
      foreach ($instance_controller->load($instance_ids) as $instance) {
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

      // Invoke hook_field_delete_field().
      $module_handler->invokeAll('field_delete_field', array($this));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    if (!isset($this->schema)) {
      $module_handler = \Drupal::moduleHandler();

      // Collect the schema from the field type.
      // @todo Use $module_handler->loadInclude() once
      // http://drupal.org/node/1941000 is fixed.
      module_load_install($this->module);
      // Invoke hook_field_schema() for the field.
      $schema = (array) $module_handler->invoke($this->module, 'field_schema', array($this));
      $schema += array('columns' => array(), 'indexes' => array(), 'foreign keys' => array());

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

}
