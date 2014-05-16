<?php

/**
 * @file
 * Contains Drupal\Core\Config\InstallStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Storage used by the Drupal installer.
 *
 * This storage performs a full filesystem scan to discover all available
 * extensions and reads from all default config directories that exist.
 *
 * This special implementation MUST NOT be used anywhere else than the early
 * installer environment.
 *
 * @see \Drupal\Core\DependencyInjection\InstallServiceProvider
 */
class InstallStorage extends FileStorage {

  /**
   * Extension sub-directory containing default configuration for installation.
   */
  const CONFIG_INSTALL_DIRECTORY = 'config/install';

  /**
   * Extension sub-directory containing configuration schema.
   */
  const CONFIG_SCHEMA_DIRECTORY = 'config/schema';

  /**
   * Folder map indexed by configuration name.
   *
   * @var array
   */
  protected $folders;

  /**
   * The directory to scan in each extension to scan for files.
   *
   * @var string
   */
  protected $directory;

  /**
   * Constructs an InstallStorage object.
   *
   * @param string $directory
   *   The directory to scan in each extension to scan for files. Defaults to
   *   'config/install'.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct($directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->directory = $directory;
    $this->collection = $collection;
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::getFilePath().
   *
   * Returns the path to the configuration file.
   *
   * Determines the owner and path to the default configuration file of a
   * requested config object name located in the installation profile, a module,
   * or a theme (in this order).
   *
   * @return string
   *   The path to the configuration file.
   *
   * @todo Improve this when figuring out how we want to handle configuration in
   *   installation profiles. E.g., a config object actually has to be searched
   *   in the profile first (whereas the profile is never the owner), only
   *   afterwards check for a corresponding module or theme.
   */
  public function getFilePath($name) {
    $folders = $this->getAllFolders();
    if (isset($folders[$name])) {
      return $folders[$name] . '/' . $name . '.' . $this->getFileExtension();
    }
    // If any code in the early installer requests a configuration object that
    // does not exist anywhere as default config, then that must be mistake.
    throw new StorageException(format_string('Missing configuration file: @name', array(
      '@name' => $name,
    )));
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return array_key_exists($name, $this->getAllFolders());
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::write().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function write($name, array $data) {
    throw new StorageException('Write operation is not allowed.');
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::delete().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function delete($name) {
    throw new StorageException('Delete operation is not allowed.');
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::rename().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function rename($name, $new_name) {
    throw new StorageException('Rename operation is not allowed.');
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   */
  public function listAll($prefix = '') {
    $names = array_keys($this->getAllFolders());
    if (!$prefix) {
      return $names;
    }
    else {
      $return = array();
      foreach ($names as $index => $name) {
        if (strpos($name, $prefix) === 0 ) {
          $return[$index] = $names[$index];
        }
      }
      return $return;
    }
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = array();
      $this->folders += $this->getComponentNames('core', array('core'));
      // @todo Refactor getComponentNames() to use the extension list directly.
      if ($profile = drupal_get_profile()) {
        $this->folders += $this->getComponentNames('profile', array($profile));
      }
      $listing = new ExtensionDiscovery();
      $this->folders += $this->getComponentNames('module', array_keys($listing->scan('module')));
      $this->folders += $this->getComponentNames('theme', array_keys($listing->scan('theme')));
    }
    return $this->folders;
  }

  /**
   * Get all configuration names and folders for a list of modules or themes.
   *
   * @param string $type
   *   Type of components: 'module' | 'theme' | 'profile'
   * @param array $list
   *   Array of theme or module names.
   *
   * @return array
   *   Folders indexed by configuration name.
   */
  public function getComponentNames($type, array $list) {
    $extension = '.' . $this->getFileExtension();
    $folders = array();
    foreach ($list as $name) {
      $directory = $this->getComponentFolder($type, $name);
      if (file_exists($directory)) {
        $files = new \GlobIterator(DRUPAL_ROOT . '/' . $directory . '/*' . $extension);
        foreach ($files as $file) {
          $folders[$file->getBasename($extension)] = $directory;
        }
      }
    }
    return $folders;
  }

  /**
   * Get folder inside each component that contains the files.
   *
   * @param string $type
   *   Component type: 'module' | 'theme' | 'profile'
   * @param string $name
   *   Component name.
   *
   * @return string
   *   The configuration folder name for this component.
   */
  protected function getComponentFolder($type, $name) {
    return drupal_get_path($type, $name) . '/' . $this->getCollectionDirectory();
  }

  /**
   * Overrides Drupal\Core\Config\FileStorage::deleteAll().
   *
   * @throws \Drupal\Core\Config\StorageException
   */
  public function deleteAll($prefix = '') {
    throw new StorageException('Delete operation is not allowed.');
  }

  /**
   * Resets the static cache.
   */
  public function reset() {
    $this->folders = NULL;
  }

}
