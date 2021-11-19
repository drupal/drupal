<?php

namespace Drupal\Core\Config;

/**
 * Interface for classes that install config.
 */
interface ConfigInstallerInterface {

  /**
   * Installs the default configuration of a given extension.
   *
   * When an extension is installed, it searches all the default configuration
   * directories for all other extensions to locate any configuration with its
   * name prefix. For example, the Node module provides the frontpage view as a
   * default configuration file:
   * core/modules/node/config/optional/views.view.frontpage.yml
   * When the Views module is installed after the Node module is already
   * enabled, the frontpage view will be installed.
   *
   * Additionally, the default configuration directory for the extension being
   * installed is searched to discover if it contains default configuration
   * that is owned by other enabled extensions. So, the frontpage view will also
   * be installed when the Node module is installed after Views.
   *
   * @param string $type
   *   The extension type; e.g., 'module' or 'theme'.
   * @param string $name
   *   The name of the module or theme to install default configuration for.
   *
   * @see \Drupal\Core\Config\ExtensionInstallStorage
   */
  public function installDefaultConfig($type, $name);

  /**
   * Installs optional configuration.
   *
   * Optional configuration is only installed if:
   * - the configuration does not exist already.
   * - it's a configuration entity.
   * - its dependencies can be met.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   (optional) The configuration storage to search for optional
   *   configuration. If not provided, all enabled extension's optional
   *   configuration directories including the install profile's will be
   *   searched.
   * @param array $dependency
   *   (optional) If set, ensures that the configuration being installed has
   *   this dependency. The format is dependency type as the key ('module',
   *   'theme', or 'config') and the dependency name as the value
   *   ('book', 'bartik', 'views.view.frontpage').
   */
  public function installOptionalConfig(StorageInterface $storage = NULL, $dependency = []);

  /**
   * Installs all default configuration in the specified collection.
   *
   * The function is useful if the site needs to respond to an event that has
   * just created another collection and we need to check all the installed
   * extensions for any matching configuration. For example, if a language has
   * just been created.
   *
   * @param string $collection
   *   The configuration collection.
   */
  public function installCollectionDefaultConfig($collection);

  /**
   * Sets the configuration storage that provides the default configuration.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage.
   *
   * @return $this
   */
  public function setSourceStorage(StorageInterface $storage);

  /**
   * Gets the configuration storage that provides the default configuration.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   The configuration storage that provides the default configuration.
   *   Returns null if the source storage has not been set.
   */
  public function getSourceStorage();

  /**
   * Sets the status of the isSyncing flag.
   *
   * @param bool $status
   *   The status of the sync flag.
   *
   * @return $this
   */
  public function setSyncing($status);

  /**
   * Gets the syncing state.
   *
   * @return bool
   *   Returns TRUE is syncing flag set.
   */
  public function isSyncing();

  /**
   * Checks the configuration that will be installed for an extension.
   *
   * @param string $type
   *   Type of extension to install.
   * @param string $name
   *   Name of extension to install.
   *
   * @throws \Drupal\Core\Config\UnmetDependenciesException
   * @throws \Drupal\Core\Config\PreExistingConfigException
   */
  public function checkConfigurationToInstall($type, $name);

}
