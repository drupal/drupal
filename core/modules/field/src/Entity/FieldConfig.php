<?php

/**
 * @file
 * Contains \Drupal\field\Entity\FieldConfig.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\Core\Field\FieldException;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Defines the Field entity.
 *
 * @ConfigEntityType(
 *   id = "field_config",
 *   label = @Translation("Field"),
 *   handlers = {
 *     "access" = "Drupal\field\FieldConfigAccessControlHandler",
 *     "storage" = "Drupal\field\FieldConfigStorage"
 *   },
 *   config_prefix = "field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class FieldConfig extends FieldConfigBase implements FieldConfigInterface {

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
   * The associated FieldStorageConfig entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * Constructs a FieldConfig object.
   *
   * In most cases, Field entities are created via
   * entity_create('field_config', $values), where $values is the same
   * parameter as in this constructor.
   *
   * @param array $values
   *   An array of field properties, keyed by property name. The
   *   storage associated to the field can be specified either with:
   *   - field_storage: the FieldStorageConfigInterface object,
   *   or by referring to an existing field storage in the current configuration
   *   with:
   *   - field_name: The field name.
   *   - entity_type: The entity type.
   *   Additionally, a 'bundle' property is required to indicate the entity
   *   bundle to which the field is attached to. Other array elements will be
   *   used to set the corresponding properties on the class; see the class
   *   property documentation for details.
   *
   * @see entity_create()
   */
  public function __construct(array $values, $entity_type = 'field_config') {
    // Allow either an injected FieldStorageConfig object, or a field_name and
    // entity_type.
    if (isset($values['field_storage'])) {
      if (!$values['field_storage'] instanceof FieldStorageConfigInterface) {
        throw new FieldException('Attempt to create a configurable field for a non-configurable field storage.');
      }
      $field_storage = $values['field_storage'];
      $values['field_name'] = $field_storage->getName();
      $values['entity_type'] = $field_storage->getTargetEntityTypeId();
      // The internal property is fieldStorage, not field_storage.
      unset($values['field_storage']);
      $values['fieldStorage'] = $field_storage;
    }
    else {
      if (empty($values['field_name'])) {
        throw new FieldException('Attempt to create a field without a field_name.');
      }
      if (empty($values['entity_type'])) {
        throw new FieldException(SafeMarkup::format('Attempt to create a field @field_name without an entity_type.', array('@field_name' => $values['field_name'])));
      }
    }
    // 'bundle' is required in either case.
    if (empty($values['bundle'])) {
      throw new FieldException(SafeMarkup::format('Attempt to create a field @field_name without a bundle.', array('@field_name' => $values['field_name'])));
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Validate that we have a valid storage for this field. This throws an
    // exception if the storage is invalid.
    $this->getFieldStorageDefinition();

    // 'Label' defaults to the field name (mostly useful for fields created in
    // tests).
    if (empty($this->label)) {
      $this->label = $this->getName();
    }
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
    $entity_manager = \Drupal::entityManager();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    $storage_definition = $this->getFieldStorageDefinition();

    // Filter out unknown settings and make sure all settings are present, so
    // that a complete field definition is passed to the various hooks and
    // written to config.
    $default_settings = $field_type_manager->getDefaultFieldSettings($storage_definition->getType());
    $this->settings = array_intersect_key($this->settings, $default_settings) + $default_settings;

    if ($this->isNew()) {
      // Notify the entity storage.
      $entity_manager->getStorage($this->entity_type)->onFieldDefinitionCreate($this);
    }
    else {
      // Some updates are always disallowed.
      if ($this->entity_type != $this->original->entity_type) {
        throw new FieldException("Cannot change an existing field's entity_type.");
      }
      if ($this->bundle != $this->original->bundle && empty($this->bundleRenameAllowed)) {
        throw new FieldException("Cannot change an existing field's bundle.");
      }
      if ($storage_definition->uuid() != $this->original->getFieldStorageDefinition()->uuid()) {
        throw new FieldException("Cannot change an existing field's storage.");
      }
      // Notify the entity storage.
      $entity_manager->getStorage($this->entity_type)->onFieldDefinitionUpdate($this, $this->original);
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Mark the field_storage_config as a a dependency.
    $this->addDependency('config', $this->getFieldStorageDefinition()->getConfigDependencyName());
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $fields) {
    $state = \Drupal::state();

    parent::preDelete($storage, $fields);
    // Keep the field definitions in the state storage so we can use them
    // later during field_purge_batch().
    $deleted_fields = $state->get('field.field.deleted') ?: array();
    foreach ($fields as $field) {
      if (!$field->deleted) {
        $config = $field->toArray();
        $config['deleted'] = TRUE;
        $config['field_storage_uuid'] = $field->getFieldStorageDefinition()->uuid();
        $deleted_fields[$field->uuid()] = $config;
      }
    }
    $state->set('field.field.deleted', $deleted_fields);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $fields) {
    // Clear the cache upfront, to refresh the results of getBundles().
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Notify the entity storage.
    foreach ($fields as $field) {
      if (!$field->deleted) {
        \Drupal::entityManager()->getStorage($field->entity_type)->onFieldDefinitionDelete($field);
      }
    }

    // If this is part of a configuration synchronization then the following
    // configuration updates are not necessary.
    $entity = reset($fields);
    if ($entity->isSyncing()) {
      return;
    }

    // Delete the associated field storages if they are not used anymore and are
    // not persistent.
    $storages_to_delete = array();
    foreach ($fields as $field) {
      $storage_definition = $field->getFieldStorageDefinition();
      if (!$field->deleted && !$field->isUninstalling() && $storage_definition->isDeletable()) {
        // Key by field UUID to avoid deleting the same storage twice.
        $storages_to_delete[$storage_definition->uuid()] = $storage_definition;
      }
    }
    if ($storages_to_delete) {
      \Drupal::entityManager()->getStorage('field_storage_config')->delete($storages_to_delete);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    $link_templates = parent::linkTemplates();
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      $link_templates["{$this->entity_type}-field-edit-form"] = 'entity.field_config.' . $this->entity_type . '_field_edit_form';
      $link_templates["{$this->entity_type}-storage-edit-form"] = 'entity.field_config.' . $this->entity_type . '_storage_edit_form';
      $link_templates["{$this->entity_type}-field-delete-form"] = 'entity.field_config.' . $this->entity_type . '_field_delete_form';

      if (isset($link_templates['config-translation-overview'])) {
        $link_templates["config-translation-overview.{$this->entity_type}"] = "entity.field_config.config_translation_overview.{$this->entity_type}";
      }
    }
    return $link_templates;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);
    $entity_type = \Drupal::entityManager()->getDefinition($this->entity_type);
    $parameters[$entity_type->getBundleEntityType()] = $this->bundle;
    return $parameters;
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
  public function getFieldStorageDefinition() {
    if (!$this->fieldStorage) {
      $fields = $this->entityManager()->getFieldStorageDefinitions($this->entity_type);
      if (!isset($fields[$this->field_name])) {
        throw new FieldException(SafeMarkup::format('Attempt to create a field @field_name that does not exist on entity type @entity_type.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));      }
      if (!$fields[$this->field_name] instanceof FieldStorageConfigInterface) {
        throw new FieldException(SafeMarkup::format('Attempt to create a configurable field of non-configurable field storage @field_name.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));
      }
      $this->fieldStorage = $fields[$this->field_name];
    }

    return $this->fieldStorage;
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
   * Loads a field config entity based on the entity type and field name.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Name of the field.
   *
   * @return static
   *   The field config entity if one exists for the provided field
   *   name, otherwise NULL.
   */
  public static function loadByName($entity_type_id, $bundle, $field_name) {
    return \Drupal::entityManager()->getStorage('field_config')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

}
