<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Stable 9's library overrides.
 *
 * @group Theme
 */
class Stable9LibraryOverrideTest extends KernelTestBase {

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
  protected $librariesToSkip = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stable9']);

    // Enable all core modules.
    $all_modules = $this->container->get('extension.list.module')->getList();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, experimental, already enabled modules, and
      // modules in the Testing package.
      if ($module->origin !== 'core' || !empty($module->info['hidden']) || $module->status == TRUE || $module->info['package'] == 'Testing' || $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::EXPERIMENTAL) {
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

    $this->themeManager = $this->container->get('theme.manager');
    $this->themeInitialization = $this->container->get('theme.initialization');
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Ensures that Stable 9 overrides all relevant core library assets.
   */
  public function testStable9LibraryOverrides() {
    // First get the clean library definitions with no active theme.
    $libraries_before = $this->getAllLibraries();
    $libraries_before = $this->removeVendorAssets($libraries_before);

    $this->themeManager->setActiveTheme($this->themeInitialization->getActiveThemeByName('stable9'));
    $this->libraryDiscovery->clearCachedDefinitions();

    // Now get the library definitions with Stable 9 as the active theme.
    $libraries_after = $this->getAllLibraries();
    $libraries_after = $this->removeVendorAssets($libraries_after);

    foreach ($libraries_before as $extension => $libraries) {
      foreach ($libraries as $library_name => $library) {
        // Allow skipping libraries.
        if (in_array("$extension/$library_name", $this->librariesToSkip)) {
          continue;
        }
        $library_after = $libraries_after[$extension][$library_name];

        // Check that all the CSS assets are overridden.
        foreach ($library['css'] as $index => $asset) {
          $clean_path = $asset['data'];
          $stable_path = $library_after['css'][$index]['data'];
          // Make core/misc assets look like they are coming from a "core"
          // module.
          $replacements = [
            'core/misc/' => "core/modules/core/css/",
          ];
          $expected_path = strtr($clean_path, $replacements);

          // Adjust the module asset paths to correspond with the Stable 9
          // folder structure.
          $replacements = [
            "core/modules/$extension/css/" => "core/themes/stable9/css/$extension/",
            "core/modules/$extension/layouts/" => "core/themes/stable9/layouts/$extension/",
          ];
          $expected_path = strtr($expected_path, $replacements);
          $assert_path = str_replace("core/modules/$extension/", '', $clean_path);

          $this->assertEquals($expected_path, $stable_path, "$assert_path from the $extension/$library_name library is overridden in Stable 9.");
          $this->assertFileExists("{$this->root}/$clean_path");
          $this->assertFileExists("{$this->root}/$stable_path");
        }
      }
    }
  }

  /**
   * Removes all vendor libraries and assets from the library definitions.
   *
   * @param array[] $all_libraries
   *   An associative array of libraries keyed by extension, then by library
   *   name, and so on.
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
   *   name, and so on.
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
