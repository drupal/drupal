<?php

namespace Drupal\field;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\DeletedFieldsRepositoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Storage handler for "field storage" configuration entities.
 */
class FieldStorageConfigStorage extends ConfigEntityStorage {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The deleted fields repository.
   *
   * @var \Drupal\Core\Field\DeletedFieldsRepositoryInterface
   */
  protected $deletedFieldsRepository;

  /**
   * Constructs a FieldStorageConfigStorage object.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Field\DeletedFieldsRepositoryInterface $deleted_fields_repository
   *   The deleted fields repository.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, FieldTypePluginManagerInterface $field_type_manager, DeletedFieldsRepositoryInterface $deleted_fields_repository) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->fieldTypeManager = $field_type_manager;
    $this->deletedFieldsRepository = $deleted_fields_repository;
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
      $container->get('module_handler'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.deleted_fields_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $conditions = []) {
    // Include deleted fields if specified in the $conditions parameters.
    $include_deleted = isset($conditions['include_deleted']) ? $conditions['include_deleted'] : FALSE;
    unset($conditions['include_deleted']);

    /** @var \Drupal\field\FieldStorageConfigInterface[] $storages */
    $storages = [];

    // Get field storages living in configuration. If we are explicitly looking
    // for deleted storages only, this can be skipped, because they will be
    // retrieved from the deleted fields repository below.
    if (empty($conditions['deleted'])) {
      if (isset($conditions['entity_type']) && isset($conditions['field_name'])) {
        // Optimize for the most frequent case where we do have a specific ID.
        $id = $conditions['entity_type'] . $conditions['field_name'];
        $storages = $this->loadMultiple([$id]);
      }
      else {
        // No specific ID, we need to examine all existing storages.
        $storages = $this->loadMultiple();
      }
    }

    // Merge deleted field storage definitions from the deleted fields
    // repository if needed.
    if ($include_deleted || !empty($conditions['deleted'])) {
      $deleted_storage_definitions = $this->deletedFieldsRepository->getFieldStorageDefinitions();
      foreach ($deleted_storage_definitions as $id => $field_storage_definition) {
        if ($field_storage_definition instanceof FieldStorageConfigInterface) {
          $storages[$id] = $field_storage_definition;
        }
      }
    }

    // Collect matching fields.
    $matches = [];
    foreach ($storages as $field) {
      foreach ($conditions as $key => $value) {
        // Extract the actual value against which the condition is checked.
        $checked_value = $field->get($key);
        // Skip to the next field as soon as one condition does not match.
        if ($checked_value != $value) {
          continue 2;
        }
      }

      // When returning deleted fields, key the results by UUID since they can
      // include several fields with the same ID.
      $key = $include_deleted ? $field->uuid() : $field->id();
      $matches[$key] = $field;
    }

    return $matches;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    foreach ($records as $id => &$record) {
      $class = $this->fieldTypeManager->getPluginClass($record['type']);
      if (empty($class)) {
        $config_id = $this->getPrefix() . $id;
        throw new \RuntimeException("Unable to determine class for field type '{$record['type']}' found in the '$config_id' configuration");
      }
      $record['settings'] = $class::storageSettingsFromConfigData($record['settings']);
    }
    return parent::mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = parent::mapToStorageRecord($entity);
    $class = $this->fieldTypeManager->getPluginClass($record['type']);
    $record['settings'] = $class::storageSettingsToConfigData($record['settings']);
    return $record;
  }

}
