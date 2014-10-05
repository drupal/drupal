<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigInstaller implements ConfigInstallerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

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
    $this->activeStorage = $active_storage;
    $this->typedConfig = $typed_config;
    $this->configManager = $config_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function installDefaultConfig($type, $name) {
    $extension_path = drupal_get_path($type, $name);
    // If the extension provides configuration schema clear the definitions.
    if (is_dir($extension_path . '/' . InstallStorage::CONFIG_SCHEMA_DIRECTORY)) {
      // Refresh the schema cache if installing default configuration and the
      // extension has a configuration schema directory.
      $this->typedConfig->clearCachedDefinitions();
    }

    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    $old_state = $this->configFactory->getOverrideState();
    $this->configFactory->setOverrideState(FALSE);

    // Read enabled extensions directly from configuration to avoid circular
    // dependencies with ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = array_keys((array) $extension_config->get('module'));
    $enabled_extensions += array_keys((array) $extension_config->get('theme'));
    // Core can provide configuration.
    $enabled_extensions[] = 'core';

    foreach ($collection_info->getCollectionNames(TRUE) as $collection) {
      $config_to_install = $this->listDefaultConfigCollection($collection, $type, $name, $enabled_extensions);
      if (!empty($config_to_install)) {
        $this->createConfiguration($collection, $config_to_install);
      }
    }
    $this->configFactory->setOverrideState($old_state);
    // Reset all the static caches and list caches.
    $this->configFactory->reset();
  }

  /**
   * Installs default configuration for a particular collection.
   *
   * @param string $collection
   *  The configuration collection to install.
   * @param string $type
   *   The extension type; e.g., 'module' or 'theme'.
   * @param string $name
   *   The name of the module or theme to install default configuration for.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   *
   * @return array
   *   The list of configuration objects to create.
   */
  protected function listDefaultConfigCollection($collection, $type, $name, array $enabled_extensions) {
    // Get all default configuration owned by this extension.
    $source_storage = $this->getSourceStorage($collection);
    $config_to_install = $source_storage->listAll($name . '.');

    // If not installing the core base system default configuration, work out if
    // this extension provides default configuration for any other enabled
    // extensions.
    $extension_path = drupal_get_path($type, $name);
    if ($type !== 'core' && is_dir($extension_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY)) {
      $default_storage = new FileStorage($extension_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection);
      $other_module_config = array_filter($default_storage->listAll(), function ($value) use ($name) {
        return !preg_match('/^' . $name . '\./', $value);
      });

      $other_module_config = array_filter($other_module_config, function ($config_name) use ($enabled_extensions) {
        $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
        return in_array($provider, $enabled_extensions);
      });

      $config_to_install = array_merge($config_to_install, $other_module_config);
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
    $config_to_install = array_diff($config_to_install, $this->getActiveStorage($collection)->listAll());

    foreach ($config_to_install as $name) {
      // Allow config factory overriders to use a custom configuration object if
      // they are responsible for the collection.
      $overrider = $this->configManager->getConfigCollectionInfo()->getOverrideService($collection);
      if ($overrider) {
        $new_config = $overrider->createConfigObject($name, $collection);
      }
      else {
        $new_config = new Config($name, $this->getActiveStorage($collection), $this->eventDispatcher, $this->typedConfig);
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
        if ($this->getActiveStorage($collection)->exists($name)) {
          $id = $entity_storage->getIDFromConfigName($name, $entity_storage->getEntityType()->getConfigPrefix());
          $entity = $entity_storage->load($id);
          foreach ($new_config->get() as $property => $value) {
            $entity->set($property, $value);
          }
          $entity->save();
        }
        else {
          $entity_storage
            ->create($new_config->get())
            ->save();
        }
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
      $old_state = $this->configFactory->getOverrideState();
      $this->configFactory->setOverrideState(FALSE);
      $this->createConfiguration($collection, $config_to_install);
      $this->configFactory->setOverrideState($old_state);
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
      // config directories for default configuration.
      $this->sourceStorage = new ExtensionInstallStorage($this->activeStorage, InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection);
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
  protected function getActiveStorage($collection = StorageInterface::DEFAULT_COLLECTION) {
    if ($this->activeStorage->getCollectionName() != $collection) {
      $this->activeStorage = $this->activeStorage->createCollection($collection);
    }
    return $this->activeStorage;
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
}
