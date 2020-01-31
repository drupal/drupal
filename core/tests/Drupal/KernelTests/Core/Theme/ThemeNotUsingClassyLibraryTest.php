<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that themes do not depend on Classy libraries.
 *
 * These tests exist to facilitate the process of decoupling theme from Classy.
 * The decoupling process includes replacing the use of all Classy libraries
 * with theme-specific ones. These tests ensure these replacements are properly
 * implemented.
 *
 * @group Theme
 */
class ThemeNotUsingClassyLibraryTest extends KernelTestBase {

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
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Classy's libraries.
   *
   * These are the libraries defined in classy.libraries.yml.
   *
   * @var string[][]
   *
   * @see \Drupal\Core\Asset\LibraryDiscoveryInterface::getLibrariesByExtension()
   */
  protected $classyLibraries;

  /**
   * Libraries that Classy extends.
   *
   * These are the libraries listed in `libraries-extend` in classy.info.yml.
   *
   * @var string[][]
   */
  protected $classyLibrariesExtend;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->themeInitialization = $this->container->get('theme.initialization');
    $this->libraryDiscovery = $this->container->get('library.discovery');
    $this->themeHandler = $this->container->get('theme_handler');
    $this->container->get('theme_installer')->install([
      'umami',
      'bartik',
      'seven',
      'claro',
    ]);
    $this->classyLibraries = $this->libraryDiscovery->getLibrariesByExtension('classy');
    $this->assertNotEmpty($this->classyLibraries);
    $this->classyLibrariesExtend = $this->themeHandler->getTheme('classy')->info['libraries-extend'];
    $this->assertNotEmpty($this->classyLibrariesExtend);
  }

  /**
   * Ensures that a theme is decoupled from Classy libraries.
   *
   * This confirms that none of the libraries defined in classy.libraries.yml
   * are loaded by the current theme. For this to happen, the current theme
   * must override the Classy library so no assets from Classy are loaded.
   *
   * @param string $theme
   *   The theme being tested.
   * @param string[] $libraries_to_skip
   *   Libraries excluded from the test.
   *
   * @dataProvider providerTestThemeNotUsingClassyLibraries
   */
  public function testThemeNotUsingClassyLibraries($theme, array $libraries_to_skip) {
    // In some cases an overridden Classy library does not use any copied assets
    // from Classy. This array collects those so this test knows to skip
    // assertions specific to those copied assets.
    $skip_asset_matching_assertions = [];
    $theme_path = $this->themeHandler->getTheme($theme)->getPath();

    // A list of all libraries that the current theme is overriding. In a
    // theme's info.yml file, these are the libraries listed in
    // `libraries-override:`, and are libraries altered by the current theme.
    // This will be used for confirming that all of Classy's libraries are
    // overridden.
    $theme_library_overrides = $this->themeInitialization->getActiveThemeByName($theme)->getLibrariesOverride()[$theme_path] ?? [];

    // A list of all libraries created by the current theme.
    $theme_libraries = $this->libraryDiscovery->getLibrariesByExtension($theme);
    $this->assertNotEmpty($theme_libraries);

    // Loop through all libraries overridden by the theme. For those that are
    // Classy libraries, confirm that the overrides prevent the loading of any
    // Classy asset.
    foreach ($theme_library_overrides as $library_name => $library_definition) {
      $in_skip_list = in_array(str_replace('classy/', '', $library_name), $libraries_to_skip);

      // If the library name does not begin with `classy/`, it's not a Classy
      // library.
      $not_classy_library = substr($library_name, 0, 7) !== 'classy/';

      // If $library_definition is false or a string, the override is preventing
      // the Classy library from loading altogether.
      $library_fully_replaced = $library_definition === FALSE || gettype($library_definition) === 'string';

      // If the library is fully replaced, it may need to be added to the
      // $skip_asset_matching_assertions array.
      if ($library_fully_replaced) {
        // Libraries with names that begin with `$theme/classy.` are copies of
        // Classy libraries.
        $not_copied_from_classy = gettype($library_definition) === 'string' && substr($library_definition, 0, (8 + strlen($theme))) !== "$theme/classy.";

        // If the overridden library is not copied from Classy or is FALSE (i.e.
        // not loaded at all), it is customized and should skip the tests that
        // check for a 1:1 asset match between the Classy library and its
        // override in the current theme.
        if ($library_definition === FALSE || $not_copied_from_classy) {
          $skip_asset_matching_assertions[] = $library_name;
        }
      }

      // If any of these three conditions are true, there's no need for the
      // remaining asset-specific assertions in this loop.
      if ($in_skip_list || $not_classy_library || $library_fully_replaced) {
        continue;
      }

      // If the library override has a 'css' key, some Classy CSS files may
      // still be loading. Confirm this is not the case.
      if (isset($library_definition['css'])) {
        $this->confirmNoClassyAssets($library_name, $library_definition, 'css');

        // If the override has no JS and all Classy CSS is accounted for, add it
        // to the list of libraries already fully overridden. It won't be
        // necessary to copy the library from Classy.
        if (!isset($library_definition['js'])) {
          $skip_asset_matching_assertions[] = $library_name;
        }
      }
      if (isset($library_definition['js'])) {
        $this->confirmNoClassyAssets($library_name, $library_definition, 'js');

        // CSS has already been checked. So, if all JS in the library is
        // accounted for, add it to the list of libraries already fully
        // overridden. It won't be necessary to copy the library from Classy.
        $skip_asset_matching_assertions[] = $library_name;
      }
    }

    // Confirm that every Classy library is copied or fully overridden by the
    // current theme.
    foreach ($this->classyLibraries as $classy_library_name => $classy_library) {
      // If a Classy library is in the $skip_asset_matching_assertions
      // array, it does not use any assets copied from Classy and can skip the
      // tests in this loop.
      $fully_overridden = in_array("classy/$classy_library_name", $skip_asset_matching_assertions);
      $skip = in_array($classy_library_name, $libraries_to_skip);
      if ($skip || $fully_overridden) {
        continue;
      }
      // Confirm the Classy Library is overridden so assets aren't loaded twice.
      $this->assertArrayHasKey("classy/$classy_library_name", $theme_library_overrides, "The classy/$classy_library_name library is not overridden in $theme");

      // Confirm there is a theme-specific version of the Classy library.
      $this->assertArrayHasKey("classy.$classy_library_name", $theme_libraries, "There is not a $theme equivalent for classy/$classy_library_name");
      $theme_copy_of_classy_library = $theme_libraries["classy.$classy_library_name"];

      // If the Classy library includes CSS, confirm the theme's copy has the
      // same CSS with the same properties.
      if (!empty($classy_library['css'])) {
        $this->confirmMatchingAssets($classy_library_name, $classy_library, $theme_copy_of_classy_library, $theme_path, 'css');
      }

      // If the Classy library includes JavaScript, confirm the theme's copy has
      // the same JavaScript with the same properties.
      if (!empty($classy_library['js'])) {
        $this->confirmMatchingAssets($classy_library_name, $classy_library, $theme_copy_of_classy_library, $theme_path, 'js');
      }
    }
  }

  /**
   * Checks for theme-specific equivalents of all Classy library-extends.
   *
   * Classy extends several core libraries with its own assets, these are
   * defined in the `libraries-extend:` list in classy.info.yml. Classy adds
   * additional assets to these libraries (e.g. when the `file/drupal.file`
   * library loads, the assets of `classy/file` are loaded as well). For a theme
   * to be properly decoupled from Classy's libraries, these core library
   * extensions must become the responsibility of that theme.
   *
   * @param string $theme
   *   The theme being tested.
   * @param string[] $extends_to_skip
   *   Classy library-extends excluded from the test.
   *
   * @dataProvider providerTestThemeAccountsForClassyExtensions
   */
  public function testThemeAccountsForClassyExtensions($theme, array $extends_to_skip) {
    $theme_path = $this->themeHandler->getTheme($theme)->getPath();

    // Get a list of libraries overridden by the current theme. In a theme's
    // info.yml file, these are the libraries listed in `libraries-override:`.
    // They are libraries altered by the current theme.
    $theme_library_overrides = $this->themeInitialization->getActiveThemeByName($theme)->getLibrariesOverride()[$theme_path] ?? [];

    // Get a list of libraries extended by the current theme. In a theme's
    // info.yml file, these are the libraries listed in `libraries-extend:`.
    // The current theme adds additional files to these libraries.
    $theme_extends = $this->themeHandler->getTheme($theme)->info['libraries-extend'] ?? [];

    // Some Classy libraries extend core libraries (i.e. they are not standalone
    // libraries. Rather, they extend the functionality of existing core
    // libraries). These extensions that were implemented in Classy need to be
    // accounted for in the current theme by either 1) The current theme
    // extending the core library with local copy of the Classy library 2)
    // Overriding the core library altogether.
    // The following iterates through each library extended by Classy to confirm
    // that the current theme accounts for these these extensions.
    foreach ($this->classyLibrariesExtend as $library_extended => $info) {
      if (in_array($library_extended, $extends_to_skip)) {
        continue;
      }

      $extends_core_library = isset($theme_extends[$library_extended]);
      $overrides_core_library = isset($theme_library_overrides[$library_extended]);

      // Every core library extended by Classy must be extended or overridden by
      // the current theme.
      $this->assertTrue(($extends_core_library || $overrides_core_library), "$library_extended is extended by Classy and should be extended or overridden by $theme");

      // If the core library is overridden, confirm that the override does not
      // include any Classy assets.
      if ($overrides_core_library) {
        $overridden_with = $theme_library_overrides[$library_extended];

        // A library override variable can be one of three types:
        // - bool (set to false): this means the override simply prevents the
        //   library from loading.
        // - array: this means some files in the overridden library are changed,
        //   but not necessarily all of them.
        // - string (which is what is being looked for here): this means the
        //   library is replaced with a completely different library.
        $override_replaces_library = (gettype($overridden_with) === 'string');
        if ($override_replaces_library) {
          // Make sure the replacement library does not come from Classy.
          $this->assertFalse(substr($overridden_with, 0, 7) === 'classy/', "$library_extended is replaced with $overridden_with. The replacement should not be a Classy library.");
        }

        // If the override doesn't prevent the core library from loading
        // entirely, and it doesn't replace it with another library, each asset
        // must be checked to confirm it isn't coming from Classy.
        if ($overridden_with !== FALSE && !$override_replaces_library) {
          foreach (['component', 'layout'] as $category) {
            if (isset($overridden_with['css'][$category])) {
              foreach ($overridden_with['css'][$category] as $css_file) {
                $this->assertFalse(strpos($css_file, 'core/themes/classy/css'), "Override is loading a Classy asset: $css_file");
              }
            }
          }
          if (isset($overridden_with['js'])) {
            foreach ($overridden_with['js'] as $js_file) {
              $this->assertFalse(strpos($js_file, 'core/themes/classy/js'), "Override is loading a Classy asset: $js_file");
            }
          }
        }
      }

      // If the library is extended, make sure it's not being extended with a
      // Classy library.
      if ($extends_core_library) {
        foreach ($theme_extends[$library_extended] as $library) {
          $this->assertFalse(substr($library, 0, 7) === 'classy/', "$theme is extending the core library: $library_extended with $library. Core libraries should not be extended with a Classy library.");
        }
      }
    }
  }

  /**
   * Confirms a library is not loading any Classy assets.
   *
   * @param string $library_name
   *   The library name.
   * @param string[][] $library_definition
   *   The data for a library, as defined in a theme's `.libraries.yml` file.
   * @param string $type
   *   The type of asset, either 'js' or 'css'.
   */
  protected function confirmNoClassyAssets($library_name, array $library_definition, $type) {
    // Get the Classy version of the library being overridden.
    $classy_library = $this->classyLibraries[str_replace('classy/', '', $library_name)];

    // Get a list of all CSS or JS files loaded by the Classy library.
    $files_used_in_classy_library = array_map(function ($item) {
      return str_replace('core/themes/classy/', '', $item['data']);
    }, $classy_library[$type]);

    $files_used_by_library_override = [];
    if ($type === 'js') {
      foreach ($library_definition[$type] as $js_file => $options) {
        $files_used_by_library_override[] = $js_file;
      }
    }
    elseif ($type === 'css') {
      foreach (['component', 'layout'] as $category) {
        if (isset($library_definition[$type][$category])) {
          foreach ($library_definition[$type][$category] as $css_file => $options) {
            $files_used_by_library_override[] = $css_file;
          }
        }
      }
    }

    $classy_files_still_loading = array_diff($files_used_in_classy_library, $files_used_by_library_override);
    $this->assertEmpty($classy_files_still_loading, "$library_name is overridden, but the theme is still loading these files from Classy. " . print_r($classy_files_still_loading, 1));
  }

  /**
   * Confirms that the assets of a copied Classy library match the original's.
   *
   * @param string $classy_library_name
   *   The name of the Classy library.
   * @param array[] $classy_library_data
   *   The Classy library's data.
   * @param array[] $theme_copy_of_classy_library
   *   The theme's copy of the Classy library.
   * @param string $theme_path
   *   The path to the current theme.
   * @param string $type
   *   The asset type, either 'js' or 'css'.
   */
  protected function confirmMatchingAssets($classy_library_name, array $classy_library_data, array $theme_copy_of_classy_library, $theme_path, $type) {
    $this->assertArrayHasKey($type, $theme_copy_of_classy_library);
    $theme_assets = [];
    $classy_assets = [];

    // Create arrays of Classy and copied assets with a structure that
    // facilitates easy comparison.
    foreach ($theme_copy_of_classy_library[$type] as $item) {
      $key = str_replace("$theme_path/$type/classy/", '', $item['data']);
      $theme_assets[$key] = $item;

      // Remove the data key as it's the only one that shouldn't match.
      unset($theme_assets[$key]['data']);
    }
    foreach ($classy_library_data[$type] as $item) {
      $key = str_replace("core/themes/classy/$type/", '', $item['data']);
      $classy_assets[$key] = $item;

      // Remove the data key as it's the only one that shouldn't match.
      unset($classy_assets[$key]['data']);
    }

    $this->assertNotEmpty($theme_assets);
    $this->assertNotEmpty($classy_assets);
    $this->assertEmpty(array_diff_key($theme_assets, $classy_assets), "Missing the inclusion of one or more files from classy/$classy_library_name.");

    // Confirm the properties of each copied file are identical.
    foreach ($classy_assets as $file => $properties) {
      foreach ($properties as $property => $value) {
        $this->assertEqual($theme_assets[$file][$property], $value, "The copied file: $file from classy/$classy_library_name has a non-matching property: $property");
      }
    }
  }

  /**
   * Data provider.
   *
   * The to-skip arrays should become increasingly smaller as issues that
   * remove Classy library dependencies are completed.
   *
   * @return array[]
   *   Themes and the libraries to be ignored.
   */
  public function providerTestThemeNotUsingClassyLibraries() {
    return [
      'claro' => [
        'theme-name' => 'claro',
        'to-skip' => [],
      ],
      'umami' => [
        'theme-name' => 'umami',
        'to-skip' => [],
      ],
      'bartik' => [
        'theme-name' => 'bartik',
        'to-skip' => [],
      ],
      'seven' => [
        'theme-name' => 'seven',
        'to-skip' => [],
      ],
    ];
  }

  /**
   * Data provider.
   *
   * The to-skip arrays should become increasingly smaller as issues that
   * remove Classy library dependencies are completed.
   *
   * @return array[]
   *   Themes and the extensions to be ignored.
   */
  public function providerTestThemeAccountsForClassyExtensions() {
    return [
      [
        'theme-name' => 'claro',
        'to-skip' => [],
      ],
      [
        'theme-name' => 'umami',
        'to-skip' => [],
      ],
      [
        'theme-name' => 'bartik',
        'to-skip' => [],
      ],
      [
        'theme-name' => 'seven',
        'to-skip' => [],
      ],
    ];
  }

}
