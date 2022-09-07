<?php

namespace Drupal\KernelTests\Core\Theme;

/**
 * Tests Stable 9's library overrides.
 *
 * @group Theme
 */
class Stable9LibraryOverrideTest extends StableLibraryOverrideTestBase {

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
    $this->enableVisibleAndStableCoreModules();

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
        // Skip internal libraries.
        if (substr($library_name, 0, 9) === 'internal.') {
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

}
