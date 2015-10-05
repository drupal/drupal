<?php

/**
 * @file
 * Contains \Drupal\field\Entity\FieldStorageConfig.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Defines the Field storage configuration entity.
 *
 * @ConfigEntityType(
 *   id = "field_storage_config",
 *   label = @Translation("Field storage"),
 *   handlers = {
 *     "storage" = "Drupal\field\FieldStorageConfigStorage"
 *   },
 *   config_prefix = "storage",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "field_name",
 *     "entity_type",
 *     "type",
 *     "settings",
 *     "module",
 *     "locked",
 *     "cardinality",
 *     "translatable",
 *     "indexes",
 *     "persist_with_no_fields",
 *     "custom_storage",
 *   }
 * )
 */
class FieldStorageConfig extends ConfigEntityBase implements FieldStorageConfigInterface {

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
  protected $id;

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
  protected $field_name;

  /**
   * The name of the entity type the field can be attached to.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The field type.
   *
   * Example: text, integer.
   *
   * @var string
   */
  protected $type;

  /**
   * The name of the module that provides the field type.
   *
   * @var string
   */
  protected $module;

  /**
   * Field-type specific settings.
   *
   * An array of key/value pairs, The keys and default values are defined by the
   * field type.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The field cardinality.
   *
   * The maximum number of values the field can hold. Possible values are
   * positive integers or
   * FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED. Defaults to 1.
   *
   * @var int
   */
  protected $cardinality = 1;

  /**
   * Flag indicating whether the field is translatable.
   *
   * Defaults to TRUE.
   *
   * @var bool
   */
  protected $translatable = TRUE;

  /**
   * Flag indicating whether the field is available for editing.
   *
   * If TRUE, some actions not available though the UI (but are still possible
   * through direct API manipulation):
   * - field settings cannot be changed,
   * - new fields cannot be created
   * - existing fields cannot be deleted.
   * Defaults to FALSE.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * Flag indicating whether the field storage should be deleted when orphaned.
   *
   * By default field storages for configurable fields are removed when there
   * are no remaining fields using them. If multiple modules provide bundles
   * which need to use the same field storage then setting this to TRUE will
   * preserve the field storage regardless of what happens to the bundles. The
   * classic use case for this is node body field storage since Book, Forum, the
   * Standard profile and bundle (node type) creation through the UI all use
   * same field storage.
   *
   * @var bool
   */
  protected $persist_with_no_fields = FALSE;

  /**
   * A boolean indicating whether or not the field item uses custom storage.
   *
   * @var bool
   */
  public $custom_storage = FALSE;

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
  protected $indexes = [];

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
  protected $deleted = FALSE;

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
   * Static flag set to prevent recursion during field deletes.
   *
   * @var bool
   */
  protected static $inDeletion = FALSE;

  /**
   * Constructs a FieldStorageConfig object.
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
   * entity_create('field_storage_config', $values)), where $values is the same
   * parameter as in this constructor.
   *
   * @see entity_create()
   */
  public function __construct(array $values, $entity_type = 'field_storage_config') {
    // Check required properties.
    if (empty($values['field_name'])) {
      throw new FieldException('Attempt to create a field storage without a field name.');
    }
    if (!preg_match('/^[_a-z]+[_a-z0-9]*$/', $values['field_name'])) {
      throw new FieldException("Attempt to create a field storage {$values['field_name']} with invalid characters. Only lowercase alphanumeric characters and underscores are allowed, and only lowercase letters and underscore are allowed as the first character");
    }
    if (empty($values['type'])) {
      throw new FieldException("Attempt to create a field storage {$values['field_name']} with no type.");
    }
    if (empty($values['entity_type'])) {
      throw new FieldException("Attempt to create a field storage {$values['field_name']} with no entity_type.");
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getTargetEntityTypeId() . '.' . $this->getName();
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::preSave().
   *
   * @throws \Drupal\Core\Field\FieldException
   *   If the field definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function preSave(EntityStorageInterface $storage) {
    // Clear the derived data about the field.
    unset($this->schema);

    // Filter out unknown settings and make sure all settings are present, so
    // that a complete field definition is passed to the various hooks and
    // written to config.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $default_settings = $field_type_manager->getDefaultStorageSettings($this->type);
    $this->settings = array_intersect_key($this->settings, $default_settings) + $default_settings;

    if ($this->isNew()) {
      $this->preSaveNew($storage);
    }
    else {
      $this->preSaveUpdated($storage);
    }

    parent::preSave($storage);
  }

  /**
   * Prepares saving a new field definition.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   *
   * @throws \Drupal\Core\Field\FieldException If the field definition is invalid.
   */
  protected function preSaveNew(EntityStorageInterface $storage) {
    $entity_manager = \Drupal::entityManager();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    // Assign the ID.
    $this->id = $this->id();

    // Field name cannot be longer than FieldStorageConfig::NAME_MAX_LENGTH characters.
    // We use Unicode::strlen() because the DB layer assumes that column widths
    // are given in characters rather than bytes.
    if (Unicode::strlen($this->getName()) > static::NAME_MAX_LENGTH) {
      throw new FieldException('Attempt to create a field storage with an name longer than ' . static::NAME_MAX_LENGTH . ' characters: ' . $this->getName());
    }

    // Disallow reserved field names.
    $disallowed_field_names = array_keys($entity_manager->getBaseFieldDefinitions($this->getTargetEntityTypeId()));
    if (in_array($this->getName(), $disallowed_field_names)) {
      throw new FieldException("Attempt to create field storage {$this->getName()} which is reserved by entity type {$this->getTargetEntityTypeId()}.");
    }

    // Check that the field type is known.
    $field_type = $field_type_manager->getDefinition($this->getType(), FALSE);
    if (!$field_type) {
      throw new FieldException("Attempt to create a field storage of unknown type {$this->getType()}.");
    }
    $this->module = $field_type['provider'];

    // Notify the entity manager.
    $entity_manager->onFieldStorageDefinitionCreate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Ensure the field is dependent on the providing module.
    $this->addDependency('module', $this->getTypeProvider());
    // Ensure the field is dependent on the provider of the entity type.
    $entity_type = \Drupal::entityManager()->getDefinition($this->entity_type);
    $this->addDependency('module', $entity_type->getProvider());
    return $this->dependencies;
  }

  /**
   * Prepares saving an updated field definition.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   */
  protected function preSaveUpdated(EntityStorageInterface $storage) {
    $module_handler = \Drupal::moduleHandler();
    $entity_manager = \Drupal::entityManager();

    // Some updates are always disallowed.
    if ($this->getType() != $this->original->getType()) {
      throw new FieldException("Cannot change the field type for an existing field storage.");
    }
    if ($this->getTargetEntityTypeId() != $this->original->getTargetEntityTypeId()) {
      throw new FieldException("Cannot change the entity type for an existing field storage.");
    }

    // See if any module forbids the update by throwing an exception. This
    // invokes hook_field_storage_config_update_forbid().
    $module_handler->invokeAll('field_storage_config_update_forbid', array($this, $this->original));

    // Notify the entity manager. A listener can reject the definition
    // update as invalid by raising an exception, which stops execution before
    // the definition is written to config.
    $entity_manager->onFieldStorageDefinitionUpdate($this, $this->original);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if ($update) {
      // Invalidate the render cache for all affected entities.
      $entity_manager = \Drupal::entityManager();
      $entity_type = $this->getTargetEntityTypeId();
      if ($entity_manager->hasHandler($entity_type, 'view_builder')) {
        $entity_manager->getViewBuilder($entity_type)->resetCache();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $field_storages) {
    $state = \Drupal::state();

    // Set the static flag so that we don't delete field storages whilst
    // deleting fields.
    static::$inDeletion = TRUE;

    // Delete or fix any configuration that is dependent, for example, fields.
    parent::preDelete($storage, $field_storages);

    // Keep the field definitions in the state storage so we can use them later
    // during field_purge_batch().
    $deleted_storages = $state->get('field.storage.deleted') ?: array();
    foreach ($field_storages as $field_storage) {
      if (!$field_storage->deleted) {
        $config = $field_storage->toArray();
        $config['deleted'] = TRUE;
        $config['bundles'] = $field_storage->getBundles();
        $deleted_storages[$field_storage->uuid()] = $config;
      }
    }

    $state->set('field.storage.deleted', $deleted_storages);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $fields) {
    // Notify the storage.
    foreach ($fields as $field) {
      if (!$field->deleted) {
        \Drupal::entityManager()->onFieldStorageDefinitionDelete($field);
        $field->deleted = TRUE;
      }
    }
    // Unset static flag.
    static::$inDeletion = FALSE;
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
  public function hasCustomStorage() {
    return $this->custom_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
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
    if (!$this->isDeleted()) {
      $map = \Drupal::entityManager()->getFieldMap();
      if (isset($map[$this->getTargetEntityTypeId()][$this->getName()]['bundles'])) {
        return $map[$this->getTargetEntityTypeId()][$this->getName()]['bundles'];
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    return $this->deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeProvider() {
    return $this->module;
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
    //   $field_type_info, or the default field storage and field settings,
    //   within $this.
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    $settings = $field_type_manager->getDefaultStorageSettings($this->getType());
    return $this->settings + $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    // @todo See getSettings() about potentially statically caching this.
    // We assume here that one call to array_key_exists() is more efficient
    // than calling getSettings() when all we need is a single setting.
    if (array_key_exists($setting_name, $this->settings)) {
      return $this->settings[$setting_name];
    }
    $settings = $this->getSettings();
    if (array_key_exists($setting_name, $settings)) {
      return $settings[$setting_name];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($setting_name, $value) {
    $this->settings[$setting_name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings + $this->settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->translatable;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionable() {
    // All configurable fields are revisionable.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslatable($translatable) {
    $this->translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return 'field';
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
  public function setCardinality($cardinality) {
    $this->cardinality = $cardinality;
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
  public function isMultiple() {
    $cardinality = $this->getCardinality();
    return ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) || ($cardinality > 1);
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
  public function setLocked($locked) {
    $this->locked = $locked;
    return $this;
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
   * Determines whether a field has any data.
   *
   * @return bool
   *   TRUE if the field has data for any entity; FALSE otherwise.
   */
  public function hasData() {
    return \Drupal::entityManager()->getStorage($this->entity_type)->countFieldData($this, TRUE);
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Using the Serialize interface and serialize() / unserialize() methods
   * breaks entity forms in PHP 5.4.
   * @todo Investigate in https://www.drupal.org/node/2074253.
   */
  public function __sleep() {
    // Only serialize necessary properties, excluding those that can be
    // recalculated.
    $properties = get_object_vars($this);
    unset($properties['schema'], $properties['propertyDefinitions'], $properties['original']);
    return array_keys($properties);
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
   * {@inheritdoc}
   */
  public function getUniqueStorageIdentifier() {
    return $this->uuid();
  }

  /**
   * Helper to retrieve the field item class.
   */
  protected function getFieldItemClass() {
    $type_definition = \Drupal::typedDataManager()
      ->getDefinition('field_item:' . $this->getType());
    return $type_definition['class'];
  }

  /**
   * Loads a field config entity based on the entity type and field name.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $field_name
   *   Name of the field.
   *
   * @return static
   *   The field config entity if one exists for the provided field name,
   *   otherwise NULL.
   */
  public static function loadByName($entity_type_id, $field_name) {
    return \Drupal::entityManager()->getStorage('field_storage_config')->load($entity_type_id . '.' . $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    // The field storage is not deleted, is configured to be removed when there
    // are no fields, the field storage has no bundles, and field storages are
    // not in the process of being deleted.
    return !$this->deleted && !$this->persist_with_no_fields && count($this->getBundles()) == 0 && !static::$inDeletion;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexes() {
    return $this->indexes;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexes(array $indexes) {
    $this->indexes = $indexes;
    return $this;
  }

}
