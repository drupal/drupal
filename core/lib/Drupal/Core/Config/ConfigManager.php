<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The ConfigManager provides helper functions for the configuration system.
 */
class ConfigManager implements ConfigManagerInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration collection info.
   *
   * @var \Drupal\Core\Config\ConfigCollectionInfo
   */
  protected $configCollectionInfo;

  /**
   * The configuration storages keyed by collection name.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $storages;

  /**
   * Creates ConfigManager objects.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, TranslationInterface $string_translation, StorageInterface $active_storage, EventDispatcherInterface $event_dispatcher) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;
    $this->stringTranslation = $string_translation;
    $this->activeStorage = $active_storage;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeIdByName($name) {
    $entities = array_filter($this->entityManager->getDefinitions(), function (EntityTypeInterface $entity_type) use ($name) {
      return ($entity_type instanceof ConfigEntityTypeInterface && $config_prefix = $entity_type->getConfigPrefix()) && strpos($name, $config_prefix . '.') === 0;
    });
    return key($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadConfigEntityByName($name) {
    $entity_type_id = $this->getEntityTypeIdByName($name);
    if ($entity_type_id) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $id = substr($name, strlen($entity_type->getConfigPrefix()) + 1);
      return $this->entityManager->getStorage($entity_type_id)->load($id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function diff(StorageInterface $source_storage, StorageInterface $target_storage, $source_name, $target_name = NULL, $collection = StorageInterface::DEFAULT_COLLECTION) {
    if ($collection != StorageInterface::DEFAULT_COLLECTION) {
      $source_storage = $source_storage->createCollection($collection);
      $target_storage = $target_storage->createCollection($collection);
    }
    if (!isset($target_name)) {
      $target_name = $source_name;
    }
    // The output should show configuration object differences formatted as YAML.
    // But the configuration is not necessarily stored in files. Therefore, they
    // need to be read and parsed, and lastly, dumped into YAML strings.
    $source_data = explode("\n", Yaml::encode($source_storage->read($source_name)));
    $target_data = explode("\n", Yaml::encode($target_storage->read($target_name)));

    // Check for new or removed files.
    if ($source_data === array('false')) {
      // Added file.
      // Cast the result of t() to a string, as the diff engine doesn't know
      // about objects.
      $source_data = array((string) $this->t('File added'));
    }
    if ($target_data === array('false')) {
      // Deleted file.
      // Cast the result of t() to a string, as the diff engine doesn't know
      // about objects.
      $target_data = array((string) $this->t('File removed'));
    }

    return new Diff($source_data, $target_data);
  }

  /**
   * {@inheritdoc}
   */
  public function createSnapshot(StorageInterface $source_storage, StorageInterface $snapshot_storage) {
    // Empty the snapshot of all configuration.
    $snapshot_storage->deleteAll();
    foreach ($snapshot_storage->getAllCollectionNames() as $collection) {
      $snapshot_collection = $snapshot_storage->createCollection($collection);
      $snapshot_collection->deleteAll();
    }
    foreach ($source_storage->listAll() as $name) {
      $snapshot_storage->write($name, $source_storage->read($name));
    }
    // Copy collections as well.
    foreach ($source_storage->getAllCollectionNames() as $collection) {
      $source_collection = $source_storage->createCollection($collection);
      $snapshot_collection = $snapshot_storage->createCollection($collection);
      foreach ($source_collection->listAll() as $name) {
        $snapshot_collection->write($name, $source_collection->read($name));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall($type, $name) {
    $entities = $this->getConfigEntitiesToChangeOnDependencyRemoval($type, [$name], FALSE);
    // Fix all dependent configuration entities.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    foreach ($entities['update'] as $entity) {
      $entity->save();
    }
    // Remove all dependent configuration entities.
    foreach ($entities['delete'] as $entity) {
      $entity->setUninstalling(TRUE);
      $entity->delete();
    }

    $config_names = $this->configFactory->listAll($name . '.');
    foreach ($config_names as $config_name) {
      $this->configFactory->getEditable($config_name)->delete();
    }

    // Remove any matching configuration from collections.
    foreach ($this->activeStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->activeStorage->createCollection($collection);
      $collection_storage->deleteAll($name . '.');
    }

    $schema_dir = drupal_get_path($type, $name) . '/' . InstallStorage::CONFIG_SCHEMA_DIRECTORY;
    if (is_dir($schema_dir)) {
      // Refresh the schema cache if uninstalling an extension that provides
      // configuration schema.
      $this->typedConfigManager->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyManager() {
    $dependency_manager = new ConfigDependencyManager();
    // Read all configuration using the factory. This ensures that multiple
    // deletes during the same request benefit from the static cache. Using the
    // factory also ensures configuration entity dependency discovery has no
    // dependencies on the config entity classes. Assume data with UUID is a
    // config entity. Only configuration entities can be depended on so we can
    // ignore everything else.
    $data = array_map(function($config) {
      $data = $config->get();
      if (isset($data['uuid'])) {
        return $data;
      }
      return FALSE;
    }, $this->configFactory->loadMultiple($this->activeStorage->listAll()));
    $dependency_manager->setData(array_filter($data));
    return $dependency_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function findConfigEntityDependents($type, array $names, ConfigDependencyManager $dependency_manager = NULL) {
    if (!$dependency_manager) {
      $dependency_manager = $this->getConfigDependencyManager();
    }
    $dependencies = array();
    foreach ($names as $name) {
      $dependencies = array_merge($dependencies, $dependency_manager->getDependentEntities($type, $name));
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function findConfigEntityDependentsAsEntities($type, array $names, ConfigDependencyManager $dependency_manager = NULL) {
    $dependencies = $this->findConfigEntityDependents($type, $names, $dependency_manager);
    $entities = array();
    $definitions = $this->entityManager->getDefinitions();
    foreach ($dependencies as $config_name => $dependency) {
      // Group by entity type to efficient load entities using
      // \Drupal\Core\Entity\EntityStorageInterface::loadMultiple().
      $entity_type_id = $this->getEntityTypeIdByName($config_name);
      // It is possible that a non-configuration entity will be returned if a
      // simple configuration object has a UUID key. This would occur if the
      // dependents of the system module are calculated since system.site has
      // a UUID key.
      if ($entity_type_id) {
        $id = substr($config_name, strlen($definitions[$entity_type_id]->getConfigPrefix()) + 1);
        $entities[$entity_type_id][] = $id;
      }
    }
    $entities_to_return = array();
    foreach ($entities as $entity_type_id => $entities_to_load) {
      $storage = $this->entityManager->getStorage($entity_type_id);
      // Remove the keys since there are potential ID clashes from different
      // configuration entity types.
      $entities_to_return = array_merge($entities_to_return, array_values($storage->loadMultiple($entities_to_load)));
    }
    return $entities_to_return;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigEntitiesToChangeOnDependencyRemoval($type, array $names, $dry_run = TRUE) {
    // Determine the current list of dependent configuration entities and set up
    // initial values.
    $dependency_manager = $this->getConfigDependencyManager();
    $dependents = $this->findConfigEntityDependentsAsEntities($type, $names, $dependency_manager);
    $original_dependencies = $dependents;
    $delete_uuids = $update_uuids = [];

    $return = [
      'update' => [],
      'delete' => [],
      'unchanged' => [],
    ];

    // Try to fix any dependencies and find out what will happen to the
    // dependency graph. Entities are processed in the order of most dependent
    // first. For example, this ensures that fields are removed before
    // field storages.
    while ($dependent = array_pop($dependents)) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $dependent */
      if ($dry_run) {
        // Clone the entity so any changes do not change any static caches.
        $dependent = clone $dependent;
      }
      $fixed = FALSE;
      if ($this->callOnDependencyRemoval($dependent, $original_dependencies, $type, $names)) {
        // Recalculate dependencies and update the dependency graph data.
        $dependent->calculateDependencies();
        $dependency_manager->updateData($dependent->getConfigDependencyName(), $dependent->getDependencies());
        // Based on the updated data rebuild the list of dependents. This will
        // remove entities that are no longer dependent after the recalculation.
        $dependents = $this->findConfigEntityDependentsAsEntities($type, $names, $dependency_manager);
        // Remove any entities that we've already marked for deletion.
        $dependents = array_filter($dependents, function ($dependent) use ($delete_uuids) {
          return !in_array($dependent->uuid(), $delete_uuids);
        });
        // Ensure that the dependency has actually been fixed. It is possible
        // that the dependent has multiple dependencies that cause it to be in
        // the dependency chain.
        $fixed = TRUE;
        foreach ($dependents as $key => $entity) {
          if ($entity->uuid() == $dependent->uuid()) {
            $fixed = FALSE;
            unset($dependents[$key]);
            break;
          }
        }
        if ($fixed) {
          $return['update'][] = $dependent;
          $update_uuids[] = $dependent->uuid();
        }
      }
      // If the entity cannot be fixed then it has to be deleted.
      if (!$fixed) {
        $delete_uuids[] = $dependent->uuid();
        $return['delete'][] = $dependent;
      }
    }
    // Use the lists of UUIDs to filter the original list to work out which
    // configuration entities are unchanged.
    $return['unchanged'] = array_filter($original_dependencies, function ($dependent) use ($delete_uuids, $update_uuids) {
      return !(in_array($dependent->uuid(), $delete_uuids) || in_array($dependent->uuid(), $update_uuids));
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigCollectionInfo() {
    if (!isset($this->configCollectionInfo)) {
      $this->configCollectionInfo = new ConfigCollectionInfo();
      $this->eventDispatcher->dispatch(ConfigEvents::COLLECTION_INFO, $this->configCollectionInfo);
    }
    return $this->configCollectionInfo;
  }

  /**
   * Calls an entity's onDependencyRemoval() method.
   *
   * A helper method to call onDependencyRemoval() with the correct list of
   * affected entities. This list should only contain dependencies on the
   * entity. Configuration and content entity dependencies will be converted
   * into entity objects.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to call onDependencyRemoval() on.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $dependent_entities
   *   The list of dependent configuration entities.
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param array $names
   *   The specific names to check. If $type equals 'module' or 'theme' then it
   *   should be a list of module names or theme names. In the case of 'config'
   *   or 'content' it should be a list of configuration dependency names.
   *
   * @return bool
   *   TRUE if the entity has changed as a result of calling the
   *   onDependencyRemoval() method, FALSE if not.
   */
  protected function callOnDependencyRemoval(ConfigEntityInterface $entity, array $dependent_entities, $type, array $names) {
    $entity_dependencies = $entity->getDependencies();
    if (empty($entity_dependencies)) {
      // No dependent entities nothing to do.
      return FALSE;
    }

    $affected_dependencies = array(
      'config' => array(),
      'content' => array(),
      'module' => array(),
      'theme' => array(),
    );

    // Work out if any of the entity's dependencies are going to be affected.
    if (isset($entity_dependencies[$type])) {
      // Work out which dependencies the entity has in common with the provided
      // $type and $names.
      $affected_dependencies[$type] = array_intersect($entity_dependencies[$type], $names);

      // If the dependencies are entities we need to convert them into objects.
      if ($type == 'config' || $type == 'content') {
        $affected_dependencies[$type] = array_map(function ($name) use ($type) {
          if ($type == 'config') {
            return $this->loadConfigEntityByName($name);
          }
          else {
            // Ignore the bundle.
            list($entity_type_id,, $uuid) = explode(':', $name);
            return $this->entityManager->loadEntityByConfigTarget($entity_type_id, $uuid);
          }
        }, $affected_dependencies[$type]);
      }
    }

    // Merge any other configuration entities into the list of affected
    // dependencies if necessary.
    if (isset($entity_dependencies['config'])) {
      foreach ($dependent_entities as $dependent_entity) {
        if (in_array($dependent_entity->getConfigDependencyName(), $entity_dependencies['config'])) {
          $affected_dependencies['config'][] = $dependent_entity;
        }
      }
    }

    // Key the entity arrays by config dependency name to make searching easy.
    foreach (['config', 'content'] as $dependency_type) {
      $affected_dependencies[$dependency_type] = array_combine(
        array_map(function ($entity) { return $entity->getConfigDependencyName(); }, $affected_dependencies[$dependency_type]),
        $affected_dependencies[$dependency_type]
      );
    }

    // Inform the entity.
    return $entity->onDependencyRemoval($affected_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function findMissingContentDependencies() {
    $content_dependencies = array();
    $missing_dependencies = array();
    foreach ($this->activeStorage->readMultiple($this->activeStorage->listAll()) as $config_data) {
      if (isset($config_data['dependencies']['content'])) {
        $content_dependencies = array_merge($content_dependencies, $config_data['dependencies']['content']);
      }
      if (isset($config_data['dependencies']['enforced']['content'])) {
        $content_dependencies = array_merge($content_dependencies, $config_data['dependencies']['enforced']['content']);
      }
    }
    foreach (array_unique($content_dependencies) as $content_dependency) {
      // Format of the dependency is entity_type:bundle:uuid.
      list($entity_type, $bundle, $uuid) = explode(':', $content_dependency, 3);
      if (!$this->entityManager->loadEntityByUuid($entity_type, $uuid)) {
        $missing_dependencies[$uuid] = array(
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'uuid' => $uuid,
        );
      }
    }
    return $missing_dependencies;
  }

}
