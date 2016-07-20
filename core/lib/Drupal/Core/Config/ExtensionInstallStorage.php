<?php

namespace Drupal\Core\Config;

use Drupal\Core\Site\Settings;
use Drupal\Core\Extension\ExtensionDiscovery;

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
   * Flag to include the profile in the list of enabled modules.
   *
   * @var bool
   */
  protected $includeProfile = TRUE;

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
   * @param bool $include_profile
   *   (optional) Whether to include the install profile in extensions to
   *   search and to get overrides from.
   */
  public function __construct(StorageInterface $config_storage, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE) {
    parent::__construct($directory, $collection);
    $this->configStorage = $config_storage;
    $this->includeProfile = $include_profile;
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
      $this->folders += $this->getCoreNames();

      $install_profile = Settings::get('install_profile');
      $profile = drupal_get_profile();
      $extensions = $this->configStorage->read('core.extension');
      // @todo Remove this scan as part of https://www.drupal.org/node/2186491
      $listing = new ExtensionDiscovery(\Drupal::root());
      if (!empty($extensions['module'])) {
        $modules = $extensions['module'];
        // Remove the install profile as this is handled later.
        unset($modules[$install_profile]);
        $profile_list = $listing->scan('profile');
        if ($profile && isset($profile_list[$profile])) {
          // Prime the drupal_get_filename() static cache with the profile info
          // file location so we can use drupal_get_path() on the active profile
          // during the module scan.
          // @todo Remove as part of https://www.drupal.org/node/2186491
          drupal_get_filename('profile', $profile, $profile_list[$profile]->getPathname());
        }
        $module_list_scan = $listing->scan('module');
        $module_list = array();
        foreach (array_keys($modules) as $module) {
          if (isset($module_list_scan[$module])) {
            $module_list[$module] = $module_list_scan[$module];
          }
        }
        $this->folders += $this->getComponentNames($module_list);
      }
      if (!empty($extensions['theme'])) {
        $theme_list_scan = $listing->scan('theme');
        foreach (array_keys($extensions['theme']) as $theme) {
          if (isset($theme_list_scan[$theme])) {
            $theme_list[$theme] = $theme_list_scan[$theme];
          }
        }
        $this->folders += $this->getComponentNames($theme_list);
      }

      if ($this->includeProfile) {
        // The install profile can override module default configuration. We do
        // this by replacing the config file path from the module/theme with the
        // install profile version if there are any duplicates.
        if (isset($profile)) {
          if (!isset($profile_list)) {
            $profile_list = $listing->scan('profile');
          }
          if (isset($profile_list[$profile])) {
            $profile_folders = $this->getComponentNames(array($profile_list[$profile]));
            $this->folders = $profile_folders + $this->folders;
          }
        }
      }
    }
    return $this->folders;
  }

}
