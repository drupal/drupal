<?php

/**
 * @file
 * Contains Drupal\Core\Config\ConfigInstallerInterface.
 */

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
   * core/modules/node/config/install/views.view.frontpage.yml
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
   *
   * @return $this
   */
  public function setSourceStorage(StorageInterface $storage);

  /**
   * Resets the configuration storage that provides the default configuration.
   *
   * @return $this
   */
  public function resetSourceStorage();

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
   * @param string $type
   *   Type of extension to install.
   * @param string $name
   *   Name of extension to install.
   *
   * @return array
   *   Array of configuration objects that already exist keyed by collection.
   */
  public function findPreExistingConfiguration($type, $name);

}
