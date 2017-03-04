<?php

namespace Drupal\field;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldConfigStorageBase;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\State\StateInterface;

/**
 * Controller class for fields.
 */
class FieldConfigStorage extends FieldConfigStorageBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a FieldConfigStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EntityManagerInterface $entity_manager, StateInterface $state, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->entityManager = $entity_manager;
    $this->state = $state;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.manager'),
      $container->get('state'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // If the field storage has been deleted in the same import, the field will
    // be deleted by then, and there is nothing left to do. Just return TRUE so
    // that the file does not get written to active store.
    if (!$old_config->get()) {
      return TRUE;
    }
    return parent::importDelete($name, $new_config, $old_config);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $conditions = []) {
    // Include deleted fields if specified in the $conditions parameters.
    $include_deleted = isset($conditions['include_deleted']) ? $conditions['include_deleted'] : FALSE;
    unset($conditions['include_deleted']);

    $fields = [];

    // Get fields stored in configuration. If we are explicitly looking for
    // deleted fields only, this can be skipped, because they will be
    // retrieved from state below.
    if (empty($conditions['deleted'])) {
      if (isset($conditions['entity_type']) && isset($conditions['bundle']) && isset($conditions['field_name'])) {
        // Optimize for the most frequent case where we do have a specific ID.
        $id = $conditions['entity_type'] . '.' . $conditions['bundle'] . '.' . $conditions['field_name'];
        $fields = $this->loadMultiple([$id]);
      }
      else {
        // No specific ID, we need to examine all existing fields.
        $fields = $this->loadMultiple();
      }
    }

    // Merge deleted fields (stored in state) if needed.
    if ($include_deleted || !empty($conditions['deleted'])) {
      $deleted_fields = $this->state->get('field.field.deleted') ?: [];
      $deleted_storages = $this->state->get('field.storage.deleted') ?: [];
      foreach ($deleted_fields as $id => $config) {
        // If the field storage itself is deleted, inject it directly in the field.
        if (isset($deleted_storages[$config['field_storage_uuid']])) {
          $config['field_storage'] = $this->entityManager->getStorage('field_storage_config')->create($deleted_storages[$config['field_storage_uuid']]);
        }
        $fields[$id] = $this->create($config);
      }
    }

    // Collect matching fields.
    $matching_fields = [];
    foreach ($fields as $field) {
      // Some conditions are checked against the field storage.
      $field_storage = $field->getFieldStorageDefinition();

      // Only keep the field if it matches all conditions.
      foreach ($conditions as $key => $value) {
        // Extract the actual value against which the condition is checked.
        switch ($key) {
          case 'field_name':
            $checked_value = $field_storage->getName();
            break;

          case 'field_id':
          case 'field_storage_uuid':
            $checked_value = $field_storage->uuid();
            break;

          case 'uuid';
            $checked_value = $field->uuid();
            break;

          case 'deleted';
            $checked_value = $field->isDeleted();
            break;

          default:
            $checked_value = $field->get($key);
            break;
        }

        // Skip to the next field as soon as one condition does not match.
        if ($checked_value != $value) {
          continue 2;
        }
      }

      // When returning deleted fields, key the results by UUID since they
      // can include several fields with the same ID.
      $key = $include_deleted ? $field->uuid() : $field->id();
      $matching_fields[$key] = $field;
    }

    return $matching_fields;
  }

}
