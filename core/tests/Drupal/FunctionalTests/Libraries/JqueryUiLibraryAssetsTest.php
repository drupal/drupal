<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Libraries;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of jQuery UI CSS and JS assets.
 *
 * @group libraries
 * @group legacy
 */
class JqueryUiLibraryAssetsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jqueryui_library_assets_test'];

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * jQuery UI CSS and JS assets keyed by their weight.
   *
   * For example, the value of $weightGroupedAssets[-11] would be an array
   * of every jQuery UI CSS and JS file asset configured with a weight of -11.
   *
   * @var array
   */
  protected $weightGroupedAssets = [];

  /**
   * The core libraries that load assets from jQuery UI.
   *
   * @var array
   */
  protected $coreLibrariesWithJqueryUiAssets = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->libraryDiscovery = $this->container->get('library.discovery');
    $core_libraries = $this->libraryDiscovery->getLibrariesByExtension('core');

    // All the core libraries that use jQuery UI assets.
    $libraries_to_check = [
      'internal.jquery_ui',
      'drupal.autocomplete',
      'drupal.dialog',
    ];

    $this->coreLibrariesWithJqueryUiAssets = array_filter($core_libraries, function ($key) use ($libraries_to_check) {
      return in_array($key, $libraries_to_check);
    }, ARRAY_FILTER_USE_KEY);

    // Loop through the core libraries with jQuery assets to build an array that
    // groups assets by their weight value.
    foreach ($this->coreLibrariesWithJqueryUiAssets as $library) {
      foreach (['js', 'css'] as $type) {
        foreach ($library[$type] as $asset) {
          $file = $asset['data'];
          if (!str_contains($file, 'jquery.ui')) {
            continue;
          }
          $weight = $asset['weight'];
          $this->weightGroupedAssets["$weight"][] = $file;
        }
      }
    }
    $this->weightGroupedAssets = array_map(function ($item) {
      return array_unique($item);
    }, $this->weightGroupedAssets);
    ksort($this->weightGroupedAssets);
  }

  /**
   * Confirm assets are weighted so they load in the correct order.
   *
   * The configured loading order is compared against the necessary loading
   * order. The necessary loading order was determined by the requirements
   * specified in each jQuery UI JavaScript file.
   */
  public function testProperlySetWeights(): void {
    $assets = [];

    // Confirm that no asset is assigned multiple weights.
    foreach ($this->weightGroupedAssets as $asset_array) {
      foreach ($asset_array as $asset) {
        $this->assertNotContains($asset, $assets);
        $assets[] = $asset;
      }
    }

    // The loading order that assets groups must be in, based on the
    // dependencies specified in every jQuery UI JavaScript file.
    $necessary_loading_order = [
      ['core/assets/vendor/jquery.ui/ui/version-min.js'],
      [
        'core/assets/vendor/jquery.ui/ui/data-min.js',
        'core/assets/vendor/jquery.ui/ui/disable-selection-min.js',
        'core/assets/vendor/jquery.ui/ui/focusable-min.js',
        'core/assets/vendor/jquery.ui/ui/form-min.js',
        'core/assets/vendor/jquery.ui/ui/ie-min.js',
        'core/assets/vendor/jquery.ui/ui/jquery-patch-min.js',
        'core/assets/vendor/jquery.ui/ui/keycode-min.js',
        'core/assets/vendor/jquery.ui/ui/plugin-min.js',
        'core/assets/vendor/jquery.ui/ui/safe-active-element-min.js',
        'core/assets/vendor/jquery.ui/ui/safe-blur-min.js',
        'core/assets/vendor/jquery.ui/ui/scroll-parent-min.js',
        'core/assets/vendor/jquery.ui/ui/unique-id-min.js',
        'core/assets/vendor/jquery.ui/ui/widget-min.js',
        'core/assets/vendor/jquery.ui/themes/base/core.css',
      ],
      [
        'core/assets/vendor/jquery.ui/ui/widgets/autocomplete-min.js',
        'core/assets/vendor/jquery.ui/ui/labels-min.js',
        'core/assets/vendor/jquery.ui/ui/widgets/menu-min.js',
        'core/assets/vendor/jquery.ui/themes/base/autocomplete.css',
        'core/assets/vendor/jquery.ui/themes/base/menu.css',
        'core/assets/vendor/jquery.ui/ui/widgets/controlgroup-min.js',
        'core/assets/vendor/jquery.ui/ui/form-reset-mixin-min.js',
        'core/assets/vendor/jquery.ui/ui/widgets/mouse-min.js',
        'core/assets/vendor/jquery.ui/themes/base/controlgroup.css',
      ],
      [
        'core/assets/vendor/jquery.ui/ui/widgets/checkboxradio-min.js',
        'core/assets/vendor/jquery.ui/ui/widgets/draggable-min.js',
        'core/assets/vendor/jquery.ui/ui/widgets/resizable-min.js',
        'core/assets/vendor/jquery.ui/themes/base/checkboxradio.css',
        'core/assets/vendor/jquery.ui/themes/base/resizable.css',
      ],
      [
        'core/assets/vendor/jquery.ui/ui/widgets/button-min.js',
        'core/assets/vendor/jquery.ui/themes/base/button.css',
      ],
      [
        'core/assets/vendor/jquery.ui/ui/widgets/dialog-min.js',
        'core/assets/vendor/jquery.ui/themes/base/dialog.css',
      ],
      ['core/assets/vendor/jquery.ui/themes/base/theme.css'],
    ];

    $configured_weights = array_keys($this->weightGroupedAssets);

    // Loop through the necessary loading order and compare to the configured
    // loading order.
    foreach ($necessary_loading_order as $stage => $assets) {
      $assets_loaded_in_stage = $this->weightGroupedAssets[$configured_weights[$stage]];
      foreach ($assets as $asset) {
        $this->assertContains($asset, $assets_loaded_in_stage);
      }
    }
  }

  /**
   * Confirm that uses of a jQuery UI asset are configured with the same weight.
   */
  public function testSameAssetSameWeight(): void {
    $asset_weights = [];
    $libraries_to_check = $this->coreLibrariesWithJqueryUiAssets;

    foreach ($libraries_to_check as $library) {
      foreach (['js', 'css'] as $type) {
        foreach ($library[$type] as $asset) {
          $file = $asset['data'];

          if (str_contains($file, 'jquery.ui')) {
            // If this is the first time a given file is checked, add the weight
            // value to an array.
            if (!isset($asset_weights[$file])) {
              $asset_weights[$file] = $asset['weight'];
            }
            else {
              // Confirm the weight of the file being loaded matches the weight
              // of when it was loaded by a library that was checked earlier.
              $this->assertEquals($asset_weights[$file], $asset['weight']);
            }
          }
        }
      }
    }
  }

  /**
   * Removes base_url() and query args from file paths.
   *
   * @param string $path
   *   The path being trimmed.
   *
   * @return string
   *   The trimmed path.
   */
  protected function trimFilePath($path) {
    $base_path_position = strpos($path, base_path());
    if ($base_path_position !== FALSE) {
      $path = substr_replace($path, '', $base_path_position, strlen(base_path()));
    }
    $query_pos = strpos($path, '?');
    return $query_pos !== FALSE ? substr($path, 0, $query_pos) : $path;
  }

  /**
   * Confirms that jQuery UI assets load on the page in the configured order.
   *
   * @dataProvider providerTestAssetLoading
   */
  public function testLibraryAssetLoadingOrder($library, array $expected_css, array $expected_js): void {
    $this->drupalGet("jqueryui_library_assets_test/$library");
    $this->assertSession()->statusCodeEquals(200);

    // A pipe character in $libraries is delimiting multiple library names.
    $libraries = str_contains($library, '|') ? explode('|', $library) : [$library];
    $files_to_check = [];

    // Populate an array with the filenames of every jQuery UI asset in the
    // libraries being tested. This will be used to confirm the configured
    // assets actually load on the test page.
    foreach ($libraries as $library_name) {
      foreach (['css', 'js'] as $type) {
        $assets = $this->coreLibrariesWithJqueryUiAssets[$library_name][$type];
        foreach ($assets as $asset) {
          if (str_contains($asset['data'], 'jquery.ui')) {
            $files_to_check[$asset['data']] = TRUE;
          }
        }
      }
    }
    $this->assertNotEmpty($files_to_check);

    // Find all jQuery UI CSS files loaded to the page, and compare their
    // loading order to the weights configured in core.libraries.yml.
    $css = $this->getSession()->getPage()->findAll('css', 'link[href*="jquery.ui"]');
    $css_weight = -100;
    foreach ($css as $item) {
      $file = $this->trimFilePath($item->getAttribute('href'));
      $found = FALSE;
      foreach ($this->weightGroupedAssets as $key => $array) {
        if (in_array($file, $array)) {
          $found = TRUE;
          $this->assertGreaterThanOrEqual($css_weight, $key, "The file $file not loading in the expected order based on its weight value.");
          $css_weight = $key;
          unset($files_to_check[$file]);
        }
      }
      $this->assertTrue($found, "A jQuery UI file: $file was loaded by the page, but is not taken into account by the test.");
    }
    $this->assertGreaterThan(-100, $css_weight);

    $js_weight = -100;
    $js = $this->getSession()->getPage()->findAll('css', 'script[src*="jquery.ui"]');
    foreach ($js as $item) {
      $file = $this->trimFilePath($item->getAttribute('src'));
      $found = FALSE;
      foreach ($this->weightGroupedAssets as $key => $array) {
        if (in_array($file, $array)) {
          $found = TRUE;
          $this->assertGreaterThanOrEqual($js_weight, $key, "The file $file not loading in the expected order based on its weight value.");
          $js_weight = $key;
          unset($files_to_check[$file]);
        }
      }
      $this->assertTrue($found, "A jQuery UI file: $file was loaded by the page, but is not taken into account by the test.");
    }
    $this->assertGreaterThan(-100, $js_weight);
    $this->assertEmpty($files_to_check);
  }

  /**
   * Confirms jQuery UI assets load as expected.
   *
   * Compares the jQuery assets that currently load against a list of the assets
   * that loaded prior to the deprecation of all remaining core jQuery UI
   * libraries.
   *
   * While this is similar to testLibraryAssetLoadingOrder(), it is a separate
   * test so it can be run in a test-only context, thus confirming that asset
   * loading is truly unchanged before and after the deprecations.
   *
   * @param string $library
   *   A pipe delimited list of libraries to check.
   * @param string[] $expected_css
   *   The jQuery UI CSS files expected to load.
   * @param string[] $expected_js
   *   The jQuery UI JavaScript files expected to load.
   *
   * @dataProvider providerTestAssetLoading
   */
  public function testAssetLoadingUnchanged($library, array $expected_css, array $expected_js): void {
    $this->drupalGet("jqueryui_library_assets_test/$library");
    $this->assertSession()->statusCodeEquals(200);

    // Find all jQuery UI CSS files loaded to the page.
    $css = $this->getSession()->getPage()->findAll('css', 'link[href*="jquery.ui"]');
    $css_loaded_by_page = [];
    foreach ($css as $item) {
      $file = $this->trimFilePath($item->getAttribute('href'));
      $css_loaded_by_page[] = $file;
    }

    $this->assertEmpty(array_diff($css_loaded_by_page, $expected_css));

    // Find all jQuery UI JavaScript files loaded to the page.
    $js = $this->getSession()->getPage()->findAll('css', 'script[src*="jquery.ui"]');
    $js_loaded_by_page = [];
    foreach ($js as $item) {
      $file = $this->trimFilePath($item->getAttribute('src'));
      $js_loaded_by_page[] = $file;
    }

    // assertEmpty() is used instead of assertSame() because we can only test
    // for the presence of assets, not their loading order. The test is designed
    // to pass before and after the jQuery UI asset changes in
    // http://drupal.org/node/3113400, which, by necessity, results in loading
    // order changes.
    $this->assertEmpty(array_diff($js_loaded_by_page, $expected_js));
  }

  /**
   * Data provider for confirming jQuery UI assets load as expected.
   *
   * Provides arrays that list how jQuery UI CSS and JavaScript dependencies
   * loaded prior to the change from dependencies to direct asset loading.
   *
   * @return array
   *   An array of test cases, where each test case is an array with the
   *   following values:
   *   - A pipe delimited string of the library/libraries to test.
   *   - An array of the jQuery UI CSS files that loaded for a given library
   *     prior to the change from jQuery UI library dependencies to direct file
   *     inclusion.
   *   - An array of the jQuery UI JavaScript files that loaded for a given
   *     library prior to the change from jQuery UI library dependencies to
   *     direct file inclusion.
   */
  public static function providerTestAssetLoading() {
    return [
      'drupal.autocomplete' => [
        'library' => 'drupal.autocomplete',
        'expected_css' => [
          'core/assets/vendor/jquery.ui/themes/base/core.css',
          'core/assets/vendor/jquery.ui/themes/base/menu.css',
          'core/assets/vendor/jquery.ui/themes/base/autocomplete.css',
          'core/assets/vendor/jquery.ui/themes/base/theme.css',
        ],
        'expected_js' => [
          'core/assets/vendor/jquery.ui/ui/data-min.js',
          'core/assets/vendor/jquery.ui/ui/disable-selection-min.js',
          'core/assets/vendor/jquery.ui/ui/form-min.js',
          'core/assets/vendor/jquery.ui/ui/labels-min.js',
          'core/assets/vendor/jquery.ui/ui/jquery-patch-min.js',
          'core/assets/vendor/jquery.ui/ui/scroll-parent-min.js',
          'core/assets/vendor/jquery.ui/ui/unique-id-min.js',
          'core/assets/vendor/jquery.ui/ui/version-min.js',
          'core/assets/vendor/jquery.ui/ui/focusable-min.js',
          'core/assets/vendor/jquery.ui/ui/ie-min.js',
          'core/assets/vendor/jquery.ui/ui/keycode-min.js',
          'core/assets/vendor/jquery.ui/ui/plugin-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-active-element-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-blur-min.js',
          'core/assets/vendor/jquery.ui/ui/widget-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/menu-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/autocomplete-min.js',
        ],
      ],
      'drupal.dialog' => [
        'library' => 'drupal.dialog',
        'expected_css' => [
          'core/assets/vendor/jquery.ui/themes/base/core.css',
          'core/assets/vendor/jquery.ui/themes/base/resizable.css',
          'core/assets/vendor/jquery.ui/themes/base/checkboxradio.css',
          'core/assets/vendor/jquery.ui/themes/base/controlgroup.css',
          'core/assets/vendor/jquery.ui/themes/base/button.css',
          'core/assets/vendor/jquery.ui/themes/base/dialog.css',
          'core/assets/vendor/jquery.ui/themes/base/theme.css',
        ],
        'expected_js' => [
          'core/assets/vendor/jquery.ui/ui/data-min.js',
          'core/assets/vendor/jquery.ui/ui/disable-selection-min.js',
          'core/assets/vendor/jquery.ui/ui/form-min.js',
          'core/assets/vendor/jquery.ui/ui/labels-min.js',
          'core/assets/vendor/jquery.ui/ui/jquery-patch-min.js',
          'core/assets/vendor/jquery.ui/ui/scroll-parent-min.js',
          'core/assets/vendor/jquery.ui/ui/unique-id-min.js',
          'core/assets/vendor/jquery.ui/ui/version-min.js',
          'core/assets/vendor/jquery.ui/ui/focusable-min.js',
          'core/assets/vendor/jquery.ui/ui/keycode-min.js',
          'core/assets/vendor/jquery.ui/ui/plugin-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-active-element-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-blur-min.js',
          'core/assets/vendor/jquery.ui/ui/widget-min.js',
          'core/assets/vendor/jquery.ui/ui/ie-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/mouse-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/draggable-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/resizable-min.js',
          'core/assets/vendor/jquery.ui/ui/form-reset-mixin-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/checkboxradio-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/controlgroup-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/button-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/dialog-min.js',
        ],
      ],
      // A few instances of multiple libraries being checked simultaneously are
      // here to ensure that multiple libraries requesting the same asset does
      // not impact the expected loading order.
      'drupal.autocomplete|drupal.dialog' => [
        'library' => 'drupal.autocomplete|drupal.dialog',
        'expected_css' => [
          'core/assets/vendor/jquery.ui/themes/base/core.css',
          'core/assets/vendor/jquery.ui/themes/base/menu.css',
          'core/assets/vendor/jquery.ui/themes/base/autocomplete.css',
          'core/assets/vendor/jquery.ui/themes/base/resizable.css',
          'core/assets/vendor/jquery.ui/themes/base/checkboxradio.css',
          'core/assets/vendor/jquery.ui/themes/base/controlgroup.css',
          'core/assets/vendor/jquery.ui/themes/base/button.css',
          'core/assets/vendor/jquery.ui/themes/base/dialog.css',
          'core/assets/vendor/jquery.ui/themes/base/theme.css',
        ],
        'expected_js' => [
          'core/assets/vendor/jquery.ui/ui/data-min.js',
          'core/assets/vendor/jquery.ui/ui/disable-selection-min.js',
          'core/assets/vendor/jquery.ui/ui/form-min.js',
          'core/assets/vendor/jquery.ui/ui/labels-min.js',
          'core/assets/vendor/jquery.ui/ui/jquery-patch-min.js',
          'core/assets/vendor/jquery.ui/ui/scroll-parent-min.js',
          'core/assets/vendor/jquery.ui/ui/unique-id-min.js',
          'core/assets/vendor/jquery.ui/ui/version-min.js',
          'core/assets/vendor/jquery.ui/ui/focusable-min.js',
          'core/assets/vendor/jquery.ui/ui/keycode-min.js',
          'core/assets/vendor/jquery.ui/ui/plugin-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-active-element-min.js',
          'core/assets/vendor/jquery.ui/ui/safe-blur-min.js',
          'core/assets/vendor/jquery.ui/ui/widget-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/menu-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/autocomplete-min.js',
          'core/assets/vendor/jquery.ui/ui/ie-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/mouse-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/draggable-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/resizable-min.js',
          'core/assets/vendor/jquery.ui/ui/form-reset-mixin-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/checkboxradio-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/controlgroup-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/button-min.js',
          'core/assets/vendor/jquery.ui/ui/widgets/dialog-min.js',
        ],
      ],
    ];

  }

}
