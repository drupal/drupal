<?php

namespace Drupal\field;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Controller class for "field storage" configuration entities.
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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Component\Plugin\PluginManagerInterface\FieldTypePluginManagerInterface
   *   The field type plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, StateInterface $state, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler'),
      $container->get('state'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $conditions = array()) {
    // Include deleted fields if specified in the $conditions parameters.
    $include_deleted = isset($conditions['include_deleted']) ? $conditions['include_deleted'] : FALSE;
    unset($conditions['include_deleted']);

    /** @var \Drupal\field\FieldStorageConfigInterface[] $storages */
    $storages = array();

    // Get field storages living in configuration. If we are explicitly looking
    // for deleted storages only, this can be skipped, because they will be
    // retrieved from state below.
    if (empty($conditions['deleted'])) {
      if (isset($conditions['entity_type']) && isset($conditions['field_name'])) {
        // Optimize for the most frequent case where we do have a specific ID.
        $id = $conditions['entity_type'] . $conditions['field_name'];
        $storages = $this->loadMultiple(array($id));
      }
      else {
        // No specific ID, we need to examine all existing storages.
        $storages = $this->loadMultiple();
      }
    }

    // Merge deleted field storages (living in state) if needed.
    if ($include_deleted || !empty($conditions['deleted'])) {
      $deleted_storages = $this->state->get('field.storage.deleted') ?: array();
      foreach ($deleted_storages as $id => $config) {
        $storages[$id] = $this->create($config);
      }
    }

    // Collect matching fields.
    $matches = array();
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
    foreach ($records as &$record) {
      $class = $this->fieldTypeManager->getPluginClass($record['type']);
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
