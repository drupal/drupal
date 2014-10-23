<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Diff\Diff;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\Entity\ConfigEntityDependency;
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
      return ($config_prefix = $entity_type->getConfigPrefix()) && strpos($name, $config_prefix . '.') === 0;
    });
    return key($entities);
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
      $source_data = array($this->t('File added'));
    }
    if ($target_data === array('false')) {
      // Deleted file.
      $target_data = array($this->t('File removed'));
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
    // Remove all dependent configuration entities.
    $extension_dependent_entities = $this->findConfigEntityDependentsAsEntities($type, array($name));

    // Give config entities a chance to become independent of the entities we
    // are going to delete.
    foreach ($extension_dependent_entities as $entity) {
      $entity_dependencies = $entity->getDependencies();
      if (empty($entity_dependencies)) {
        // No dependent entities nothing to do.
        continue;
      }
      // Work out if any of the entity's dependencies are going to be affected
      // by the uninstall.
      $affected_dependencies = array(
        'config' => array(),
        'module' => array(),
        'theme' => array(),
      );
      if (isset($entity_dependencies['config'])) {
        foreach ($extension_dependent_entities as $extension_dependent_entity) {
          if (in_array($extension_dependent_entity->getConfigDependencyName(), $entity_dependencies['config'])) {
            $affected_dependencies['config'][] = $extension_dependent_entity;
          }
        }
      }
      // Check if the extension being uninstalled is a dependency of the entity.
      if (isset($entity_dependencies[$type]) && in_array($name, $entity_dependencies[$type])) {
        $affected_dependencies[$type] = array($name);
      }
      // Inform the entity.
      $entity->onDependencyRemoval($affected_dependencies);
    }

    // Recalculate the dependencies, some config entities may have fixed their
    // dependencies on the to-be-removed entities.
    $extension_dependent_entities = $this->findConfigEntityDependentsAsEntities($type, array($name));
    // Reverse the array to that entities are removed in the correct order of
    // dependence. For example, this ensures that fields are removed before
    // field storages.
    foreach (array_reverse($extension_dependent_entities) as $extension_dependent_entity) {
      $extension_dependent_entity->setUninstalling(TRUE);
      $extension_dependent_entity->delete();
    }

    $config_names = $this->configFactory->listAll($name . '.');
    foreach ($config_names as $config_name) {
      $this->configFactory->get($config_name)->delete();
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
  public function findConfigEntityDependents($type, array $names) {
    $dependency_manager = new ConfigDependencyManager();
    // This uses the configuration storage directly to avoid blowing the static
    // caches in the configuration factory and the configuration entity system.
    // Additionally this ensures that configuration entity dependency discovery
    // has no dependencies on the config entity classes. Assume data with UUID
    // is a config entity. Only configuration entities can be depended on so we
    // can ignore everything else.
    $data = array_filter($this->activeStorage->readMultiple($this->activeStorage->listAll()), function($config) {
      return isset($config['uuid']);
    });
    $dependency_manager->setData($data);
    $dependencies = array();
    foreach ($names as $name) {
      $dependencies = array_merge($dependencies, $dependency_manager->getDependentEntities($type, $name));
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function findConfigEntityDependentsAsEntities($type, array $names) {
    $dependencies = $this->findConfigEntityDependents($type, $names);
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
  public function supportsConfigurationEntities($collection) {
    return $collection == StorageInterface::DEFAULT_COLLECTION;
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

}
