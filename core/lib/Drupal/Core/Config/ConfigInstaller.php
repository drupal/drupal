<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigInstaller implements ConfigInstallerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storages, keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $activeStorages;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration storage that provides the default configuration.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * Is configuration being created as part of a configuration sync.
   *
   * @var bool
   */
  protected $isSyncing = FALSE;

  /**
   * Constructs the configuration installer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StorageInterface $active_storage, TypedConfigManagerInterface $typed_config, ConfigManagerInterface $config_manager, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->activeStorages[$active_storage->getCollectionName()] = $active_storage;
    $this->typedConfig = $typed_config;
    $this->configManager = $config_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function installDefaultConfig($type, $name) {
    $extension_path = drupal_get_path($type, $name);
    // Refresh the schema cache if the extension provides configuration schema
    // or is a theme.
    if (is_dir($extension_path . '/' . InstallStorage::CONFIG_SCHEMA_DIRECTORY) || $type == 'theme') {
      $this->typedConfig->clearCachedDefinitions();
    }

    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    // Read enabled extensions directly from configuration to avoid circular
    // dependencies with ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $modules = (array) $extension_config->get('module');
    // Unless we are installing the profile, remove it from the list.
    if ($install_profile = Settings::get('install_profile')) {
      if ($name !== $install_profile) {
        unset($modules[$install_profile]);
      }
    }
    $enabled_extensions = array_keys($modules);
    $enabled_extensions += array_keys((array) $extension_config->get('theme'));

    // Core can provide configuration.
    $enabled_extensions[] = 'core';

    foreach ($collection_info->getCollectionNames(TRUE) as $collection) {
      $config_to_install = $this->listDefaultConfigToInstall($type, $name, $collection, $enabled_extensions);
      if (!empty($config_to_install)) {
        $this->createConfiguration($collection, $config_to_install);
      }
    }

    // Reset all the static caches and list caches.
    $this->configFactory->reset();
  }

  /**
   * Lists default configuration for an extension that is available to install.
   *
   * This looks in the extension's config/install directory and all of the
   * currently enabled extensions config/install directories for configuration
   * that begins with the extension's name.
   *
   * @param string $type
   *   The extension type; e.g., 'module' or 'theme'.
   * @param string $name
   *   The name of the module or theme to install default configuration for.
   * @param string $collection
   *  The configuration collection to install.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   *
   * @return array
   *   The list of configuration objects to create.
   */
  protected function listDefaultConfigToInstall($type, $name, $collection, array $enabled_extensions) {
    // Get all default configuration owned by this extension.
    $source_storage = $this->getSourceStorage($collection);
    $config_to_install = $source_storage->listAll($name . '.');

    // If not installing the core base system default configuration, work out if
    // this extension provides default configuration for any other enabled
    // extensions.
    $extension_path = drupal_get_path($type, $name);
    if ($type !== 'core' && is_dir($extension_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY)) {
      $default_storage = new FileStorage($extension_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection);
      $extension_provided_config = array_filter($default_storage->listAll(), function ($config_name) use ($config_to_install, $enabled_extensions) {
        // Ensure that we have not already discovered the config to install.
        if (in_array($config_name, $config_to_install)) {
          return FALSE;
        }
        // Ensure the configuration is provided by an enabled module.
        $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
        return in_array($provider, $enabled_extensions);
      });

      $config_to_install = array_merge($config_to_install, $extension_provided_config);
    }

    return $config_to_install;
  }

  /**
   * Creates configuration in a collection based on the provided list.
   *
   * @param string $collection
   *   The configuration collection.
   * @param array $config_to_install
   *   A list of configuration object names to create.
   */
  protected function createConfiguration($collection, array $config_to_install) {
    // Order the configuration to install in the order of dependencies.
    $data = $this->getSourceStorage($collection)->readMultiple($config_to_install);
    $config_entity_support = $this->configManager->supportsConfigurationEntities($collection);
    if ($config_entity_support) {
      $dependency_manager = new ConfigDependencyManager();
      $config_to_install = $dependency_manager
        ->setData($data)
        ->sortAll();
    }

    // Remove configuration that already exists in the active storage.
    $config_to_install = array_diff($config_to_install, $this->getActiveStorages($collection)->listAll());

    foreach ($config_to_install as $name) {
      // Allow config factory overriders to use a custom configuration object if
      // they are responsible for the collection.
      $overrider = $this->configManager->getConfigCollectionInfo()->getOverrideService($collection);
      if ($overrider) {
        $new_config = $overrider->createConfigObject($name, $collection);
      }
      else {
        $new_config = new Config($name, $this->getActiveStorages($collection), $this->eventDispatcher, $this->typedConfig);
      }
      if ($data[$name] !== FALSE) {
        $new_config->setData($data[$name]);
      }
      if ($config_entity_support && $entity_type = $this->configManager->getEntityTypeIdByName($name)) {

        // If we are syncing do not create configuration entities. Pluggable
        // configuration entities can have dependencies on modules that are
        // not yet enabled. This approach means that any code that expects
        // default configuration entities to exist will be unstable after the
        // module has been enabled and before the config entity has been
        // imported.
        if ($this->isSyncing) {
          continue;
        }
        $entity_storage = $this->configManager
          ->getEntityManager()
          ->getStorage($entity_type);
        // It is possible that secondary writes can occur during configuration
        // creation. Updates of such configuration are allowed.
        if ($this->getActiveStorages($collection)->exists($name)) {
          $id = $entity_storage->getIDFromConfigName($name, $entity_storage->getEntityType()->getConfigPrefix());
          $entity = $entity_storage->load($id);
          $entity = $entity_storage->updateFromStorageRecord($entity, $new_config->get());
        }
        else {
          $entity = $entity_storage->createFromStorageRecord($new_config->get());
        }
        $entity->save();
      }
      else {
        $new_config->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function installCollectionDefaultConfig($collection) {
    $config_to_install = $this->getSourceStorage($collection)->listAll();
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = array_keys((array) $extension_config->get('module'));
    $enabled_extensions += array_keys((array) $extension_config->get('theme'));
    $config_to_install = array_filter($config_to_install, function ($config_name) use ($enabled_extensions) {
      $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
      return in_array($provider, $enabled_extensions);
    });
    if (!empty($config_to_install)) {
      $this->createConfiguration($collection, $config_to_install);
      // Reset all the static caches and list caches.
      $this->configFactory->reset();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceStorage(StorageInterface $storage) {
    $this->sourceStorage = $storage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetSourceStorage() {
    $this->sourceStorage = null;
    return $this;
  }

  /**
   * Gets the configuration storage that provides the default configuration.
   *
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  public function getSourceStorage($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->sourceStorage)) {
      // Default to using the ExtensionInstallStorage which searches extension's
      // config directories for default configuration. Only include the profile
      // configuration during Drupal installation.
      $this->sourceStorage = new ExtensionInstallStorage($this->getActiveStorages(StorageInterface::DEFAULT_COLLECTION), InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection, drupal_installation_attempted());
    }
    if ($this->sourceStorage->getCollectionName() != $collection) {
      $this->sourceStorage = $this->sourceStorage->createCollection($collection);
    }
    return $this->sourceStorage;
  }

  /**
   * Gets the configuration storage that provides the active configuration.
   *
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getActiveStorages($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->activeStorages[$collection])) {
      $this->activeStorages[$collection] = reset($this->activeStorages)->createCollection($collection);
    }
    return $this->activeStorages[$collection];
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($status) {
    $this->isSyncing = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->isSyncing;
  }

  /**
   * {@inheritdoc}
   */
  public function findPreExistingConfiguration($type, $name) {
    $existing_configuration = array();
    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    // Read enabled extensions directly from configuration to avoid circular
    // dependencies on ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = array_keys((array) $extension_config->get('module'));
    $enabled_extensions += array_keys((array) $extension_config->get('theme'));
    // Add the extension that will be enabled to the list of enabled extensions.
    $enabled_extensions[] = $name;
    foreach ($collection_info->getCollectionNames(TRUE) as $collection) {
      $config_to_install = $this->listDefaultConfigToInstall($type, $name, $collection, $enabled_extensions);
      $active_storage = $this->getActiveStorages($collection);
      foreach ($config_to_install as $config_name) {
        if ($active_storage->exists($config_name)) {
          $existing_configuration[$collection][] = $config_name;
        }
      }
    }
    return $existing_configuration;
  }
}
