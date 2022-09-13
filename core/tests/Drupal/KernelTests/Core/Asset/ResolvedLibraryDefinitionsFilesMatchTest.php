<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the asset files for all core libraries exist.
 *
 * This test also changes the active theme to each core theme to verify
 * the libraries after theme-level libraries-override and libraries-extend are
 * applied.
 *
 * @group Asset
 */
class ResolvedLibraryDefinitionsFilesMatchTest extends KernelTestBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

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
   * A list of all core themes.
   *
   * We hardcode this because test themes don't use a 'package' or 'hidden' key
   * so we don't have a good way of filtering to only get "real" themes.
   *
   * @var string[]
   */
  protected $allThemes = [
    'claro',
    'olivero',
    'stable9',
    'stark',
  ];

  /**
   * A list of libraries to skip checking, in the format extension/library_name.
   *
   * @var string[]
   */
  protected $librariesToSkip = [
    // Locale has a "dummy" library that does not actually exist.
    'locale/translations',
    // Core has a "dummy" library that does not actually exist.
    'core/ckeditor5.translations',
  ];

  /**
   * A list of all paths that have been checked.
   *
   * @var array[]
   */
  protected $pathsChecked;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable all core modules.
    $all_modules = $this->container->get('extension.list.module')->getList();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, already enabled modules and modules in the
      // Testing package.
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

    // Install the System module configuration as Olivero's block configuration
    // depends on the system menus.
    // @todo Remove this in https://www.drupal.org/node/3219959
    $this->installConfig('system');
    // Install the 'user' entity schema because the workspaces module's install
    // hook creates a workspace with default uid of 1. Then the layout_builder
    // module's implementation of hook_entity_presave will cause
    // \Drupal\Core\TypedData\Validation\RecursiveValidator::validate() to run
    // on the workspace which will fail because the user table is not present.
    // @todo Remove this in https://www.drupal.org/node/3039217.
    $this->installEntitySchema('user');

    // Remove demo_umami_content module as its install hook creates content
    // that relies on the presence of entity tables and various other elements
    // not present in a kernel test.
    unset($all_modules['demo_umami_content']);
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

    // Install all core themes.
    sort($this->allThemes);
    $this->container->get('theme_installer')->install($this->allThemes);

    $this->themeHandler = $this->container->get('theme_handler');
    $this->themeInitialization = $this->container->get('theme.initialization');
    $this->themeManager = $this->container->get('theme.manager');
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Ensures that all core module and theme library files exist.
   */
  public function testCoreLibraryCompleteness() {
    // First verify all libraries with no active theme.
    $this->verifyLibraryFilesExist($this->getAllLibraries());

    // Then verify all libraries for each core theme. This may seem like
    // overkill but themes can override and extend other extensions' libraries
    // and these changes are only applied for the active theme.
    foreach ($this->allThemes as $theme) {
      $this->themeManager->setActiveTheme($this->themeInitialization->getActiveThemeByName($theme));
      $this->libraryDiscovery->clearCachedDefinitions();

      $this->verifyLibraryFilesExist($this->getAllLibraries());
    }
  }

  /**
   * Checks that all the library files exist.
   *
   * @param array[] $library_definitions
   *   An array of library definitions, keyed by extension, then by library, and
   *   so on.
   */
  protected function verifyLibraryFilesExist($library_definitions) {
    foreach ($library_definitions as $extension => $libraries) {
      foreach ($libraries as $library_name => $library) {
        if (in_array("$extension/$library_name", $this->librariesToSkip)) {
          continue;
        }

        // Check that all the assets exist.
        foreach (['css', 'js'] as $asset_type) {
          foreach ($library[$asset_type] as $asset) {
            $file = $asset['data'];
            $path = $this->root . '/' . $file;
            // Only check and assert each file path once.
            if (!isset($this->pathsChecked[$path])) {
              $this->assertFileExists($path, "$file file referenced from the $extension/$library_name library does not exist.");
              $this->pathsChecked[$path] = TRUE;
            }
          }
        }
      }
    }
  }

  /**
   * Gets all libraries for core and all installed modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   */
  protected function getAllLibraries() {
    $modules = \Drupal::moduleHandler()->getModuleList();
    $extensions = $modules;
    $module_list = array_keys($modules);
    sort($module_list);
    $this->assertEquals($this->allModules, $module_list, 'All core modules are installed.');

    $themes = $this->themeHandler->listInfo();
    $extensions += $themes;
    $theme_list = array_keys($themes);
    sort($theme_list);
    $this->assertEquals($this->allThemes, $theme_list, 'All core themes are installed.');

    $libraries['core'] = $this->libraryDiscovery->getLibrariesByExtension('core');

    foreach ($extensions as $extension_name => $extension) {
      $library_file = $extension->getPath() . '/' . $extension_name . '.libraries.yml';
      if (is_file($this->root . '/' . $library_file)) {
        $libraries[$extension_name] = $this->libraryDiscovery->getLibrariesByExtension($extension_name);
      }
    }
    return $libraries;
  }

}
