<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ExtensionInstallStorage.
 */

namespace Drupal\Core\Config;

/**
 * Storage to access configuration and schema in enabled extensions.
 *
 * @see \Drupal\Core\Config\ConfigInstaller
 * @see \Drupal\Core\Config\TypedConfigManager
 */
class ExtensionInstallStorage extends InstallStorage {

  /**
   * The active configuration store.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Overrides \Drupal\Core\Config\InstallStorage::__construct().
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   * @param string $directory
   *   The directory to scan in each extension to scan for files. Defaults to
   *   'config/install'.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct(StorageInterface $config_storage, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->configStorage = $config_storage;
    $this->directory = $directory;
    $this->collection = $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->configStorage,
      $this->directory,
      $collection
    );
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * The list is based on enabled modules and themes. The active configuration
   * storage is used rather than \Drupal\Core\Extension\ModuleHandler and
   *  \Drupal\Core\Extension\ThemeHandler in order to resolve circular
   * dependencies between these services and \Drupal\Core\Config\ConfigInstaller
   * and \Drupal\Core\Config\TypedConfigManager.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = array();
      $this->folders += $this->getComponentNames('core', array('core'));

      $extensions = $this->configStorage->read('core.extension');
      if (!empty($extensions['module'])) {
        $this->folders += $this->getComponentNames('module', array_keys($extensions['module']));
      }
      if (!empty($extensions['theme'])) {
        $this->folders += $this->getComponentNames('theme', array_keys($extensions['theme']));
      }

      // The install profile can override module default configuration. We do
      // this by replacing the config file path from the module/theme with the
      // install profile version if there are any duplicates.
      $profile_folders = $this->getComponentNames('profile', array(drupal_get_profile()));
      $folders_to_replace = array_intersect_key($profile_folders, $this->folders);
      if (!empty($folders_to_replace)) {
        $this->folders = array_merge($this->folders, $folders_to_replace);
      }
    }
    return $this->folders;
  }
}

