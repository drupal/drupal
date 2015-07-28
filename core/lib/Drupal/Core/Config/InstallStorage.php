<?php

/**
 * @file
 * Contains \Drupal\Core\Config\InstallStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\Extension;

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
   * Extension sub-directory containing optional configuration for installation.
   */
  const CONFIG_OPTIONAL_DIRECTORY = 'config/optional';

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
    throw new StorageException("Missing configuration file: $name");
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
      $this->folders += $this->getCoreNames();
      // Perform an ExtensionDiscovery scan as we cannot use drupal_get_path()
      // yet because the system module may not yet be enabled during install.
      // @todo Remove as part of https://www.drupal.org/node/2186491
      $listing = new ExtensionDiscovery(\Drupal::root());
      if ($profile = drupal_get_profile()) {
        $profile_list = $listing->scan('profile');
        if (isset($profile_list[$profile])) {
          // Prime the drupal_get_filename() static cache with the profile info
          // file location so we can use drupal_get_path() on the active profile
          // during the module scan.
          // @todo Remove as part of https://www.drupal.org/node/2186491
          drupal_get_filename('profile', $profile, $profile_list[$profile]->getPathname());
          $this->folders += $this->getComponentNames(array($profile_list[$profile]));
        }
      }
      // @todo Remove as part of https://www.drupal.org/node/2186491
      $this->folders += $this->getComponentNames($listing->scan('module'));
      $this->folders += $this->getComponentNames($listing->scan('theme'));
    }
    return $this->folders;
  }

  /**
   * Get all configuration names and folders for a list of modules or themes.
   *
   * @param \Drupal\Core\Extension\Extension[] $list
   *   An associative array of Extension objects, keyed by extension name.
   *
   * @return array
   *   Folders indexed by configuration name.
   */
  public function getComponentNames(array $list) {
    $extension = '.' . $this->getFileExtension();
    $folders = array();
    foreach ($list as $extension_object) {
      // We don't have to use ExtensionDiscovery here because our list of
      // extensions was already obtained through an ExtensionDiscovery scan.
      $directory = $this->getComponentFolder($extension_object);
      if (file_exists($directory)) {
        $files = new \GlobIterator(\Drupal::root() . '/' . $directory . '/*' . $extension);
        foreach ($files as $file) {
          $folders[$file->getBasename($extension)] = $directory;
        }
      }
    }
    return $folders;
  }

  /**
   * Get all configuration names and folders for Drupal core.
   *
   * @return array
   *   Folders indexed by configuration name.
   */
  public function getCoreNames() {
    $extension = '.' . $this->getFileExtension();
    $folders = array();
    $directory = $this->getCoreFolder();
    if (file_exists($directory)) {
      $files = new \GlobIterator(\Drupal::root() . '/' . $directory . '/*' . $extension);
      foreach ($files as $file) {
        $folders[$file->getBasename($extension)] = $directory;
      }
    }
    return $folders;
  }

  /**
   * Get folder inside each component that contains the files.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The Extension object for the component.
   *
   * @return string
   *   The configuration folder name for this component.
   */
  protected function getComponentFolder(Extension $extension) {
    return $extension->getPath() . '/' . $this->getCollectionDirectory();
  }

  /**
   * Get folder inside Drupal core that contains the files.
   *
   * @return string
   *   The configuration folder name for core.
   */
  protected function getCoreFolder() {
    return drupal_get_path('core', 'core') . '/' . $this->getCollectionDirectory();
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
