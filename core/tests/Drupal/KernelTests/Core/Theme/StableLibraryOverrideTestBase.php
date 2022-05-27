<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of a theme when base_theme info key is missing.
 *
 * @group Theme
 */
abstract class StableLibraryOverrideTestBase extends KernelTestBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * A list of all core modules.
   *
   * @var string[]
   */
  protected $allModules;

  /**
   * A list of libraries to skip checking, in the format extension/library_name.
   *
   * @var string[]
   */
  protected $librariesToSkip = [
    // This is a deprecated library that will trigger warnings.
    'image/quickedit.inPlaceEditor.image',
  ];

  /**
   * Enable all core modules that are not hidden or experimental.
   */
  protected function enableVisibleAndStableCoreModules(): void {
    $all_modules = $this->container->get('extension.list.module')->getList();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, experimental, deprecated, and already enabled
      // modules, and modules in the Testing package.
      if ($module->origin !== 'core'
        || !empty($module->info['hidden'])
        || $module->status == TRUE
        || $module->info['package'] == 'Testing'
        || $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::EXPERIMENTAL
        || $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::DEPRECATED) {
        return FALSE;
      }
      return TRUE;
    });
    $this->allModules = array_keys($all_modules);
    $this->allModules[] = 'system';
    $this->allModules[] = 'user';
    $this->allModules[] = 'path_alias';
    $database_module = \Drupal::database()->getProvider();
    if ($database_module !== 'core') {
      $this->allModules[] = $database_module;
    }
    sort($this->allModules);
    $this->container->get('module_installer')->install($this->allModules);
  }

  /**
   * Removes all vendor libraries and assets from the library definitions.
   *
   * @param array[] $all_libraries
   *   An associative array of libraries keyed by extension, then by library
   *   name, then by asset type.
   *
   * @return array[]
   *   The reduced array of libraries.
   */
  protected function removeVendorAssets(array $all_libraries) {
    foreach ($all_libraries as $extension => $libraries) {
      foreach ($libraries as $library_name => $library) {
        if (isset($library['remote'])) {
          unset($all_libraries[$extension][$library_name]);
        }
        foreach (['css', 'js'] as $asset_type) {
          foreach ($library[$asset_type] as $index => $asset) {
            if (strpos($asset['data'], 'core/assets/vendor') !== FALSE) {
              unset($all_libraries[$extension][$library_name][$asset_type][$index]);
              // Re-key the array of assets. This is needed because
              // libraries-override doesn't always preserve the order.
              if (!empty($all_libraries[$extension][$library_name][$asset_type])) {
                $all_libraries[$extension][$library_name][$asset_type] = array_values($all_libraries[$extension][$library_name][$asset_type]);
              }
            }
          }
        }
      }
    }
    return $all_libraries;
  }

  /**
   * Gets all libraries for core and all installed modules.
   *
   * @return array[]
   *   An associative array of libraries keyed by extension, then by library
   *   name, then by asset type.
   */
  protected function getAllLibraries() {
    $modules = \Drupal::moduleHandler()->getModuleList();
    $module_list = array_keys($modules);
    sort($module_list);
    $this->assertEquals($this->allModules, $module_list, 'All core modules are installed.');

    $libraries['core'] = $this->libraryDiscovery->getLibrariesByExtension('core');

    foreach ($modules as $module_name => $module) {
      $library_file = $module->getPath() . '/' . $module_name . '.libraries.yml';
      if (is_file($this->root . '/' . $library_file)) {
        $libraries[$module_name] = $this->libraryDiscovery->getLibrariesByExtension($module_name);
      }
    }
    return $libraries;
  }

}
