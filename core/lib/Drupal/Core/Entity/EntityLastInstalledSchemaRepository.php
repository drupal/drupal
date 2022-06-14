<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Provides a repository for installed entity definitions.
 */
class EntityLastInstalledSchemaRepository implements EntityLastInstalledSchemaRepositoryInterface {

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The loaded installed entity type definitions.
   *
   * @var array|null
   */
  protected $entityTypeDefinitions = NULL;

  /**
   * Constructs a new EntityLastInstalledSchemaRepository.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, CacheBackendInterface $cache) {
    $this->keyValueFactory = $key_value_factory;
    $this->cacheBackend = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledDefinition($entity_type_id) {
    return $this->getLastInstalledDefinitions()[$entity_type_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledDefinitions() {
    if ($this->entityTypeDefinitions) {
      return $this->entityTypeDefinitions;
    }
    elseif ($cache = $this->cacheBackend->get('entity_type_definitions.installed')) {
      $this->entityTypeDefinitions = $cache->data;
      return $this->entityTypeDefinitions;
    }

    $all_definitions = $this->keyValueFactory->get('entity.definitions.installed')->getAll();

    // Filter out field storage definitions.
    $filtered_keys = array_filter(array_keys($all_definitions), function ($key) {
        return substr($key, -12) === '.entity_type';
    });
    $entity_type_definitions = array_intersect_key($all_definitions, array_flip($filtered_keys));

    // Ensure that the returned array is keyed by the entity type ID.
    $keys = array_keys($entity_type_definitions);
    $keys = array_map(function ($key) {
      $parts = explode('.', $key);
      return $parts[0];
    }, $keys);

    $this->entityTypeDefinitions = array_combine($keys, $entity_type_definitions);
    $this->cacheBackend->set('entity_type_definitions.installed', $this->entityTypeDefinitions, Cache::PERMANENT);
    return $this->entityTypeDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledDefinition(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.entity_type', $entity_type);
    $this->cacheBackend->delete('entity_type_definitions.installed');
    $this->entityTypeDefinitions = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLastInstalledDefinition($entity_type_id) {
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.entity_type');
    // Clean up field storage definitions as well. Even if the entity type
    // isn't currently fieldable, there might be legacy definitions or an
    // empty array stored from when it was.
    $this->keyValueFactory->get('entity.definitions.installed')->delete($entity_type_id . '.field_storage_definitions');
    $this->cacheBackend->deleteMultiple(['entity_type_definitions.installed', $entity_type_id . '.field_storage_definitions.installed']);
    $this->entityTypeDefinitions = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastInstalledFieldStorageDefinitions($entity_type_id) {
    if ($cache = $this->cacheBackend->get($entity_type_id . '.field_storage_definitions.installed')) {
      return $cache->data;
    }
    $definitions = $this->keyValueFactory->get('entity.definitions.installed')->get($entity_type_id . '.field_storage_definitions', []);
    $this->cacheBackend->set($entity_type_id . '.field_storage_definitions.installed', $definitions, Cache::PERMANENT);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledFieldStorageDefinitions($entity_type_id, array $storage_definitions) {
    $this->keyValueFactory->get('entity.definitions.installed')->set($entity_type_id . '.field_storage_definitions', $storage_definitions);
    $this->cacheBackend->delete($entity_type_id . '.field_storage_definitions.installed');
  }

  /**
   * {@inheritdoc}
   */
  public function setLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    $definitions[$storage_definition->getName()] = $storage_definition;
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLastInstalledFieldStorageDefinition(FieldStorageDefinitionInterface $storage_definition) {
    $entity_type_id = $storage_definition->getTargetEntityTypeId();
    $definitions = $this->getLastInstalledFieldStorageDefinitions($entity_type_id);
    unset($definitions[$storage_definition->getName()]);
    $this->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
  }

}
