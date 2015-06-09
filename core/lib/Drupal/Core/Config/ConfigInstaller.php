<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstaller.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\Entity\ConfigEntityDependency;
use Drupal\Core\Entity\EntityTypeInterface;
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
    $extension_path = $this->drupalGetPath($type, $name);
    // Refresh the schema cache if the extension provides configuration schema
    // or is a theme.
    if (is_dir($extension_path . '/' . InstallStorage::CONFIG_SCHEMA_DIRECTORY) || $type == 'theme') {
      $this->typedConfig->clearCachedDefinitions();
    }

    $default_install_path = $this->getDefaultConfigDirectory($type, $name);
    if (is_dir($default_install_path)) {
      if (!$this->isSyncing()) {
        $storage = new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION);
        $prefix = '';
      }
      else {
        // The configuration importer sets the source storage on the config
        // installer. The configuration importer handles all of the
        // configuration entity imports. We only need to ensure that simple
        // configuration is created when the extension is installed.
        $storage = $this->getSourceStorage();
        $prefix = $name . '.';
      }

      // Gets a profile storage to search for overrides if necessary.
      $profile_storage = $this->getProfileStorage($name);

      // Gather information about all the supported collections.
      $collection_info = $this->configManager->getConfigCollectionInfo();
      foreach ($collection_info->getCollectionNames() as $collection) {
        $config_to_create = $this->getConfigToCreate($storage, $collection, $prefix, $profile_storage);
        // If we're installing a profile ensure configuration that is overriding
        // is excluded.
        if ($name == $this->drupalGetProfile()) {
          $existing_configuration = $this->getActiveStorages($collection)->listAll();
          $config_to_create = array_diff_key($config_to_create, array_flip($existing_configuration));
        }
        if (!empty($config_to_create)) {
          $this->createConfiguration($collection, $config_to_create);
        }
      }
    }

    // During a drupal installation optional configuration is installed at the
    // end of the installation process.
    // @see install_install_profile()
    if (!$this->isSyncing() && !$this->drupalInstallationAttempted()) {
      $optional_install_path = $extension_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      if (is_dir($optional_install_path)) {
        // Install any optional config the module provides.
        $storage = new FileStorage($optional_install_path, StorageInterface::DEFAULT_COLLECTION);
        $this->installOptionalConfig($storage, '');
      }
      // Install any optional configuration entities whose dependencies can now
      // be met. This searches all the installed modules config/optional
      // directories.
      $storage = new ExtensionInstallStorage($this->getActiveStorages(StorageInterface::DEFAULT_COLLECTION), InstallStorage::CONFIG_OPTIONAL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION, FALSE);
      $this->installOptionalConfig($storage, [$type => $name]);
    }

    // Reset all the static caches and list caches.
    $this->configFactory->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function installOptionalConfig(StorageInterface $storage = NULL, $dependency = []) {
    $profile = $this->drupalGetProfile();
    if (!$storage) {
      // Search the install profile's optional configuration too.
      $storage = new ExtensionInstallStorage($this->getActiveStorages(StorageInterface::DEFAULT_COLLECTION), InstallStorage::CONFIG_OPTIONAL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION, TRUE);
      // The extension install storage ensures that overrides are used.
      $profile_storage = NULL;
    }
    elseif (isset($profile)) {
      // Creates a profile storage to search for overrides.
      $profile_install_path = $this->drupalGetPath('module', $profile) . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      $profile_storage = new FileStorage($profile_install_path, StorageInterface::DEFAULT_COLLECTION);
    }
    else {
      // Profile has not been set yet. For example during the first steps of the
      // installer or during unit tests.
      $profile_storage = NULL;
    }

    $enabled_extensions = $this->getEnabledExtensions();
    $existing_config = $this->getActiveStorages()->listAll();

    $list = array_filter($storage->listAll(), function($config_name) use ($existing_config) {
      // Only list configuration that:
      // - does not already exist
      // - is a configuration entity (this also excludes config that has an
      //   implicit dependency on modules that are not yet installed)
      return !in_array($config_name, $existing_config) && $this->configManager->getEntityTypeIdByName($config_name);
    });

    $all_config = array_merge($existing_config, $list);
    $config_to_create = $storage->readMultiple($list);
    // Check to see if the corresponding override storage has any overrides.
    if ($profile_storage) {
      $config_to_create = $profile_storage->readMultiple($list) + $config_to_create;
    }
    foreach ($config_to_create as $config_name => $data) {
      // Exclude configuration where its dependencies cannot be met.
      if (!$this->validateDependencies($config_name, $data, $enabled_extensions, $all_config)) {
        unset($config_to_create[$config_name]);
      }
      // Exclude configuration that does not have a matching dependency.
      elseif (!empty($dependency)) {
        // Create a light weight dependency object to check dependencies.
        $config_entity = new ConfigEntityDependency($config_name, $data);
        if (!$config_entity->hasDependency(key($dependency), reset($dependency))) {
          unset($config_to_create[$config_name]);
        }
      }
    }
    if (!empty($config_to_create)) {
      $this->createConfiguration(StorageInterface::DEFAULT_COLLECTION, $config_to_create, TRUE);
    }
  }

  /**
   * Gets configuration data from the provided storage to create.
   *
   * @param StorageInterface $storage
   *   The configuration storage to read configuration from.
   * @param string $collection
   *  The configuration collection to use.
   * @param string $prefix
   *   (optional) Limit to configuration starting with the provided string.
   *
   * @return array
   *   An array of configuration data read from the source storage keyed by the
   *   configuration object name.
   */
  protected function getConfigToCreate(StorageInterface $storage, $collection, $prefix = '', StorageInterface $profile_storage = NULL) {
    if ($storage->getCollectionName() != $collection) {
      $storage = $storage->createCollection($collection);
    }
    $data = $storage->readMultiple($storage->listAll($prefix));

    // Check to see if the corresponding override storage has any overrides.
    if ($profile_storage) {
      if ($profile_storage->getCollectionName() != $collection) {
        $profile_storage = $profile_storage->createCollection($collection);
      }
      $data = $profile_storage->readMultiple(array_keys($data)) + $data;
    }
    return $data;
  }

  /**
   * Creates configuration in a collection based on the provided list.
   *
   * @param string $collection
   *   The configuration collection.
   * @param array $config_to_create
   *   An array of configuration data to create, keyed by name.
   */
  protected function createConfiguration($collection, array $config_to_create) {
    // Order the configuration to install in the order of dependencies.
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      $dependency_manager = new ConfigDependencyManager();
      $config_names = $dependency_manager
        ->setData($config_to_create)
        ->sortAll();
    }
    else {
      $config_names = array_keys($config_to_create);
    }

    foreach ($config_names as $name) {
      // Allow config factory overriders to use a custom configuration object if
      // they are responsible for the collection.
      $overrider = $this->configManager->getConfigCollectionInfo()->getOverrideService($collection);
      if ($overrider) {
        $new_config = $overrider->createConfigObject($name, $collection);
      }
      else {
        $new_config = new Config($name, $this->getActiveStorages($collection), $this->eventDispatcher, $this->typedConfig);
      }
      if ($config_to_create[$name] !== FALSE) {
        $new_config->setData($config_to_create[$name]);
      }
      if ($collection == StorageInterface::DEFAULT_COLLECTION && $entity_type = $this->configManager->getEntityTypeIdByName($name)) {
        // If we are syncing do not create configuration entities. Pluggable
        // configuration entities can have dependencies on modules that are
        // not yet enabled. This approach means that any code that expects
        // default configuration entities to exist will be unstable after the
        // module has been enabled and before the config entity has been
        // imported.
        if ($this->isSyncing()) {
          continue;
        }
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
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
        if ($entity->isInstallable()) {
          $entity->trustData()->save();
        }
      }
      else {
        $new_config->save(TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function installCollectionDefaultConfig($collection) {
    $storage = new ExtensionInstallStorage($this->getActiveStorages(StorageInterface::DEFAULT_COLLECTION), InstallStorage::CONFIG_INSTALL_DIRECTORY, $collection, $this->drupalInstallationAttempted());
    // Only install configuration for enabled extensions.
    $enabled_extensions = $this->getEnabledExtensions();
    $config_to_install = array_filter($storage->listAll(), function ($config_name) use ($enabled_extensions) {
      $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
      return in_array($provider, $enabled_extensions);
    });
    if (!empty($config_to_install)) {
      $this->createConfiguration($collection, $storage->readMultiple($config_to_install));
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
   * Gets the configuration storage that provides the default configuration.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   The configuration storage that provides the default configuration.
   *   Returns null if the source storage has not been set.
   */
  public function getSourceStorage() {
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
    if (!$status) {
      $this->sourceStorage = NULL;
    }
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
   * Finds pre-existing configuration objects for the provided extension.
   *
   * Extensions can not be installed if configuration objects exist in the
   * active storage with the same names. This can happen in a number of ways,
   * commonly:
   * - if a user has created configuration with the same name as that provided
   *   by the extension.
   * - if the extension provides default configuration that does not depend on
   *   it and the extension has been uninstalled and is about to the
   *   reinstalled.
   *
   * @return array
   *   Array of configuration object names that already exist keyed by
   *   collection.
   */
  protected function findPreExistingConfiguration(StorageInterface $storage) {
    $existing_configuration = array();
    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    foreach ($collection_info->getCollectionNames() as $collection) {
      $config_to_create = array_keys($this->getConfigToCreate($storage, $collection));
      $active_storage = $this->getActiveStorages($collection);
      foreach ($config_to_create as $config_name) {
        if ($active_storage->exists($config_name)) {
          $existing_configuration[$collection][] = $config_name;
        }
      }
    }
    return $existing_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function checkConfigurationToInstall($type, $name) {
    if ($this->isSyncing()) {
      // Configuration is assumed to already be checked by the config importer
      // validation events.
      return;
    }
    $config_install_path = $this->getDefaultConfigDirectory($type, $name);
    if (!is_dir($config_install_path)) {
      return;
    }

    $storage = new FileStorage($config_install_path, StorageInterface::DEFAULT_COLLECTION);

    $enabled_extensions = $this->getEnabledExtensions();
    // Add the extension that will be enabled to the list of enabled extensions.
    $enabled_extensions[] = $name;
    // Gets a profile storage to search for overrides if necessary.
    $profile_storage = $this->getProfileStorage($name);

    // Check the dependencies of configuration provided by the module.
    $invalid_default_config = $this->findDefaultConfigWithUnmetDependencies($storage, $enabled_extensions, $profile_storage);
    if (!empty($invalid_default_config)) {
      throw UnmetDependenciesException::create($name, $invalid_default_config);
    }

    // Install profiles can not have config clashes. Configuration that
    // has the same name as a module's configuration will be used instead.
    if ($name != $this->drupalGetProfile()) {
      // Throw an exception if the module being installed contains configuration
      // that already exists. Additionally, can not continue installing more
      // modules because those may depend on the current module being installed.
      $existing_configuration = $this->findPreExistingConfiguration($storage);
      if (!empty($existing_configuration)) {
        throw PreExistingConfigException::create($name, $existing_configuration);
      }
    }
  }

  /**
   * Finds default configuration with unmet dependencies.
   *
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   *
   * @return array
   *   List of configuration that has unmet dependencies
   */
  protected function findDefaultConfigWithUnmetDependencies(StorageInterface $storage, array $enabled_extensions, StorageInterface $profile_storage = NULL) {
    $config_to_create = $this->getConfigToCreate($storage, StorageInterface::DEFAULT_COLLECTION, '', $profile_storage);
    $all_config = array_merge($this->configFactory->listAll(), array_keys($config_to_create));
    return array_filter(array_keys($config_to_create), function($config_name) use ($enabled_extensions, $all_config, $config_to_create) {
      return !$this->validateDependencies($config_name, $config_to_create[$config_name], $enabled_extensions, $all_config);
    });
  }

  /**
   * Validates an array of config data that contains dependency information.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return bool
   *   TRUE if the dependencies are met, FALSE if not.
   */
  protected function validateDependencies($config_name, array $data, array $enabled_extensions, array $all_config) {
    if (isset($data['dependencies'])) {
      $all_dependencies = $data['dependencies'];

      // Ensure enforced dependencies are included.
      if (isset($all_dependencies['enforced'])) {
        $all_dependencies = array_merge($all_dependencies, $data['dependencies']['enforced']);
        unset($all_dependencies['enforced']);
      }
      // Ensure the configuration entity type provider is in the list of
      // dependencies.
      list($provider) = explode('.', $config_name, 2);
      if (!isset($all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }
      elseif (!in_array($provider, $all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }

      foreach ($all_dependencies as $type => $dependencies) {
        $list_to_check = [];
        switch ($type) {
          case 'module':
          case 'theme':
            $list_to_check = $enabled_extensions;
            break;
          case 'config':
            $list_to_check = $all_config;
            break;
        }
        if (!empty($list_to_check)) {
          $missing = array_diff($dependencies, $list_to_check);
          if (!empty($missing)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Gets the list of enabled extensions including both modules and themes.
   *
   * @return array
   *   A list of enabled extensions which includes both modules and themes.
   */
  protected function getEnabledExtensions() {
    // Read enabled extensions directly from configuration to avoid circular
    // dependencies on ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = (array) $extension_config->get('module');
    $enabled_extensions += (array) $extension_config->get('theme');
    // Core can provide configuration.
    $enabled_extensions['core'] = 'core';
    return array_keys($enabled_extensions);
  }

  /**
   * Gets the profile storage to use to check for profile overrides.
   *
   * @param string $installing_name
   *   (optional) The name of the extension currently being installed.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   A storage to access configuration from the installation profile. If a
   *   Drupal installation is not in progress or we're installing the profile
   *   itself, then it will return NULL as the profile storage should not be
   *   used.
   */
  protected function getProfileStorage($installing_name = '') {
    $profile = $this->drupalGetProfile();
    if ($this->drupalInstallationAttempted() && $profile != $installing_name) {
      // Profiles should not contain optional configuration so always use the
      // install directory.
      $profile_install_path = $this->getDefaultConfigDirectory('module', $profile);
      $profile_storage = new FileStorage($profile_install_path, StorageInterface::DEFAULT_COLLECTION);
    }
    else {
      $profile_storage = NULL;
    }
    return $profile_storage;
  }

  /**
   * Gets an extension's default configuration directory.
   *
   * @param string $type
   *   Type of extension to install.
   * @param string $name
   *   Name of extension to install.
   *
   * @return string
   *   The extension's default configuration directory.
   */
  protected function getDefaultConfigDirectory($type, $name) {
    return $this->drupalGetPath($type, $name) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
  }

  /**
   * Wrapper for drupal_get_path().
   *
   * @param $type
   *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
   *   'theme_engine'.
   * @param $name
   *   The name of the item for which the path is requested. Ignored for
   *   $type 'core'.
   *
   * @return string
   *   The path to the requested item or an empty string if the item is not
   *   found.
   */
  protected function drupalGetPath($type, $name) {
    return drupal_get_path($type, $name);
  }

  /**
   * Gets the install profile from settings.
   *
   * @return string|null $profile
   *   The name of the installation profile or NULL if no installation profile
   *   is currently active. This is the case for example during the first steps
   *   of the installer or during unit tests.
   */
  protected function drupalGetProfile() {
    // Settings is safe to use because settings.php is written before any module
    // is installed.
    return Settings::get('install_profile');
  }

  /**
   * Wrapper for drupal_installation_attempted().
   *
   * @return bool
   *   TRUE if a Drupal installation is currently being attempted.
   */
  protected function drupalInstallationAttempted() {
    return drupal_installation_attempted();
  }

}
