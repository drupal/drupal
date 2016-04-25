<?php

namespace Drupal\Core\Field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Reacts to field definition CRUD on behalf of the Entity system.
 */
class FieldDefinitionListener implements FieldDefinitionListenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new FieldDefinitionListener.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, KeyValueFactoryInterface $key_value_factory, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->keyValueFactory = $key_value_factory;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    // Notify the storage about the new field.
    $this->entityTypeManager->getStorage($entity_type_id)->onFieldDefinitionCreate($field_definition);

    // Update the bundle field map key value collection, add the new field.
    $bundle_field_map = $this->keyValueFactory->get('entity.definitions.bundle_field_map')->get($entity_type_id);
    if (!isset($bundle_field_map[$field_name])) {
      // This field did not exist yet, initialize it with the type and empty
      // bundle list.
      $bundle_field_map[$field_name] = [
        'type' => $field_definition->getType(),
        'bundles' => [],
      ];
    }
    $bundle_field_map[$field_name]['bundles'][$bundle] = $bundle;
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->set($entity_type_id, $bundle_field_map);

    // Delete the cache entry.
    $this->cacheBackend->delete('entity_field_map');

    // If the field map is initialized, update it as well, so that calls to it
    // do not have to rebuild it again.
    if ($field_map = $this->entityFieldManager->getFieldMap()) {
      if (!isset($field_map[$entity_type_id][$field_name])) {
        // This field did not exist yet, initialize it with the type and empty
        // bundle list.
        $field_map[$entity_type_id][$field_name] = [
          'type' => $field_definition->getType(),
          'bundles' => [],
        ];
      }
      $field_map[$entity_type_id][$field_name]['bundles'][$bundle] = $bundle;
      $this->entityFieldManager->setFieldMap($field_map);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original) {
    // Notify the storage about the updated field.
    $this->entityTypeManager->getStorage($field_definition->getTargetEntityTypeId())->onFieldDefinitionUpdate($field_definition, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    // Notify the storage about the field deletion.
    $this->entityTypeManager->getStorage($entity_type_id)->onFieldDefinitionDelete($field_definition);

    // Unset the bundle from the bundle field map key value collection.
    $bundle_field_map = $this->keyValueFactory->get('entity.definitions.bundle_field_map')->get($entity_type_id);
    unset($bundle_field_map[$field_name]['bundles'][$bundle]);
    if (empty($bundle_field_map[$field_name]['bundles'])) {
      // If there are no bundles left, remove the field from the map.
      unset($bundle_field_map[$field_name]);
    }
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->set($entity_type_id, $bundle_field_map);

    // Delete the cache entry.
    $this->cacheBackend->delete('entity_field_map');

    // If the field map is initialized, update it as well, so that calls to it
    // do not have to rebuild it again.
    if ($field_map = $this->entityFieldManager->getFieldMap()) {
      unset($field_map[$entity_type_id][$field_name]['bundles'][$bundle]);
      if (empty($field_map[$entity_type_id][$field_name]['bundles'])) {
        unset($field_map[$entity_type_id][$field_name]);
      }
      $this->entityFieldManager->setFieldMap($field_map);
    }
  }

}
