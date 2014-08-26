<?php

/**
 * @file
 * Contains \Drupal\field\Entity\FieldInstanceConfig.
 */

namespace Drupal\field\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\Core\Field\FieldException;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field\FieldInstanceConfigInterface;

/**
 * Defines the Field instance entity.
 *
 * @ConfigEntityType(
 *   id = "field_instance_config",
 *   label = @Translation("Field instance"),
 *   handlers = {
 *     "access" = "Drupal\field\FieldInstanceConfigAccessControlHandler",
 *     "storage" = "Drupal\field\FieldInstanceConfigStorage"
 *   },
 *   config_prefix = "instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class FieldInstanceConfig extends FieldConfigBase implements FieldInstanceConfigInterface {

  /**
   * Flag indicating whether the instance is deleted.
   *
   * The delete() method marks the instance as "deleted" and removes the
   * corresponding entry from the config storage, but keeps its definition in
   * the state storage while field data is purged by a separate
   * garbage-collection process.
   *
   * Deleted instances stay out of the regular entity lifecycle (notably, their
   * values are not populated in loaded entities, and are not saved back).
   *
   * @var bool
   */
  public $deleted = FALSE;

  /**
   * The field ConfigEntity object this is an instance of.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * Constructs a FieldInstanceConfig object.
   *
   * In most cases, Field instance entities are created via
   * entity_create('field_instance_config', $values), where $values is the same
   * parameter as in this constructor.
   *
   * @param array $values
   *   An array of field instance properties, keyed by property name. The
   *   storage associated to the instance can be specified either with:
   *   - field_storage: the FieldStorageConfigInterface object,
   *   or by referring to an existing field storage in the current configuration
   *   with:
   *   - field_name: The field name.
   *   - entity_type: The entity type.
   *   Additionally, a 'bundle' property is required to indicate the entity
   *   bundle to which the instance is attached to. Other array elements will be
   *   used to set the corresponding properties on the class; see the class
   *   property documentation for details.
   *
   * @see entity_create()
   */
  public function __construct(array $values, $entity_type = 'field_instance_config') {
    // Allow either an injected FieldStorageConfig object, or a field_name and
    // entity_type.
    if (isset($values['field_storage'])) {
      if (!$values['field_storage'] instanceof FieldStorageConfigInterface) {
        throw new FieldException('Attempt to create a configurable field instance for a non-configurable field storage.');
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
        throw new FieldException('Attempt to create a field instance without a field_name.');
      }
      if (empty($values['entity_type'])) {
        throw new FieldException(String::format('Attempt to create a field instance @field_name without an entity_type.', array('@field_name' => $values['field_name'])));
      }
    }
    // 'bundle' is required in either case.
    if (empty($values['bundle'])) {
      throw new FieldException(String::format('Attempt to create a field instance @field_name without a bundle.', array('@field_name' => $values['field_name'])));
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Validate that we have a valid storage for this instance. This throws an
    // exception if the storage is invalid.
    $this->getFieldStorageDefinition();

    // 'Label' defaults to the field name (mostly useful for field instances
    // created in tests).
    if (empty($this->label)) {
      $this->label = $this->getName();
    }
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::preSave().
   *
   * @throws \Drupal\Core\Field\FieldException
   *   If the field instance definition is invalid.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures at the configuration storage level.
   */
  public function preSave(EntityStorageInterface $storage) {
    $entity_manager = \Drupal::entityManager();
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');

    $storage_definition = $this->getFieldStorageDefinition();

    if ($this->isNew()) {
      // Set the default instance settings.
      $this->settings += $field_type_manager->getDefaultInstanceSettings($storage_definition->type);
      // Notify the entity storage.
      $entity_manager->getStorage($this->entity_type)->onFieldDefinitionCreate($this);
    }
    else {
      // Some updates are always disallowed.
      if ($this->entity_type != $this->original->entity_type) {
        throw new FieldException("Cannot change an existing instance's entity_type.");
      }
      if ($this->bundle != $this->original->bundle && empty($this->bundleRenameAllowed)) {
        throw new FieldException("Cannot change an existing instance's bundle.");
      }
      if ($storage_definition->uuid() != $this->original->getFieldStorageDefinition()->uuid()) {
        throw new FieldException("Cannot change an existing instance's storage.");
      }
      // Set the default instance settings.
      $this->settings += $field_type_manager->getDefaultInstanceSettings($storage_definition->type);
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
    $this->addDependency('entity', $this->getFieldStorageDefinition()->getConfigDependencyName());
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $instances) {
    $state = \Drupal::state();

    // Keep the instance definitions in the state storage so we can use them
    // later during field_purge_batch().
    $deleted_instances = $state->get('field.instance.deleted') ?: array();
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        $config = $instance->toArray();
        $config['deleted'] = TRUE;
        $config['field_storage_uuid'] = $instance->getFieldStorageDefinition()->uuid();
        $deleted_instances[$instance->uuid()] = $config;
      }
    }
    $state->set('field.instance.deleted', $deleted_instances);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $instances) {
    // Clear the cache upfront, to refresh the results of getBundles().
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Notify the entity storage.
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        \Drupal::entityManager()->getStorage($instance->entity_type)->onFieldDefinitionDelete($instance);
      }
    }

    // If this is part of a configuration synchronization then the following
    // configuration updates are not necessary.
    $entity = reset($instances);
    if ($entity->isSyncing()) {
      return;
    }

    // Delete field storages that have no more instances.
    $storages_to_delete = array();
    foreach ($instances as $instance) {
      $storage_definition = $instance->getFieldStorageDefinition();
      if (!$instance->deleted && empty($instance->noFieldDelete) && !$instance->isUninstalling() && count($storage_definition->getBundles()) == 0) {
        // Key by field UUID to avoid deleting the same storage twice.
        $storages_to_delete[$storage_definition->uuid()] = $storage_definition;
      }
    }
    if ($storages_to_delete) {
      \Drupal::entityManager()->getStorage('field_storage_config')->delete($storages_to_delete);
    }

    // Cleanup entity displays.
    $displays_to_update = array();
    foreach ($instances as $instance) {
      if (!$instance->deleted) {
        $view_modes = \Drupal::entityManager()->getViewModeOptions($instance->entity_type, TRUE);
        foreach (array_keys($view_modes) as $mode) {
          $displays_to_update['entity_view_display'][$instance->entity_type . '.' . $instance->bundle . '.' . $mode][] = $instance->getName();
        }
        $form_modes = \Drupal::entityManager()->getFormModeOptions($instance->entity_type, TRUE);
        foreach (array_keys($form_modes) as $mode) {
          $displays_to_update['entity_form_display'][$instance->entity_type . '.' . $instance->bundle . '.' . $mode][] = $instance->getName();
        }
      }
    }
    foreach ($displays_to_update as $type => $ids) {
      foreach (entity_load_multiple($type, array_keys($ids)) as $id => $display) {
        foreach ($ids[$id] as $field_name) {
          $display->removeComponent($field_name);
        }
        $display->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    $link_templates = parent::linkTemplates();
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      $link_templates['edit-form'] = 'field_ui.instance_edit_' . $this->entity_type;
      $link_templates['storage-edit-form'] = 'field_ui.storage_edit_' . $this->entity_type;
      $link_templates['delete-form'] = 'field_ui.delete_' . $this->entity_type;

      if (isset($link_templates['drupal:config-translation-overview'])) {
        $link_templates['drupal:config-translation-overview'] .= $link_templates['edit-form'];
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
        throw new FieldException(String::format('Attempt to create an instance of field @field_name that does not exist on entity type @entity_type.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));
      }
      if (!$fields[$this->field_name] instanceof FieldStorageConfigInterface) {
        throw new FieldException(String::format('Attempt to create a configurable instance of non-configurable field @field_name.', array('@field_name' => $this->field_name, '@entity_type' => $this->entity_type)));
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
   *   The field instance config entity if one exists for the provided field
   *   name, otherwise NULL.
   */
  public static function loadByName($entity_type_id, $bundle, $field_name) {
    return \Drupal::entityManager()->getStorage('field_instance_config')->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

}
