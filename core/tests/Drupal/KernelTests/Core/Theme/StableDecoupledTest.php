<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that themes do not depend on Stable assets.
 *
 * These tests exist to facilitate the process of decoupling theme from Stable.
 * The decoupling process includes:
 *   1. Identifying Stable assets that differ from core ones
 *   2. Of those differing assets, identify those that are inherited by
 *     the themes being tested.
 *   3. Determine if a copy of that Stable asset must be added to the theme,
 *     or if it's acceptable for the theme to inherit the more recent version
 *     of the asset from core.
 * This test will identify these assets and confirm that the theme has properly
 * decoupled itself from them by either having a theme-specific copy of the
 * asset, or it being determined that it's acceptable for the theme to use
 * core's version (in which case it will be skipped by this test).
 *
 * @group Theme
 */
class StableDecoupledTest extends KernelTestBase {

  /**
   * Populates an array with CSS and templates used within a given directory.
   *
   * @param string[] &$assets
   *   Modified parameter, an array of asset filenames.
   * @param string $path
   *   The path being checked.
   */
  protected function getAssets(array &$assets, $path) {
    $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
    $iterator = new \RecursiveIteratorIterator($directory);
    foreach ($iterator as $fileinfo) {
      if ($fileinfo->getExtension() !== 'twig' && $fileinfo->getExtension() !== 'css') {
        continue;
      }
      $filename = $fileinfo->getFilename();
      $filepath = $fileinfo->getPathname();
      // Tests can have assets with the same filename, so skip assets in those
      // directories.
      if (strpos($filepath, '/test') === FALSE) {
        $assets[$filename] = file_get_contents($filepath);
      }
    }
  }

  /**
   * Returns a list of asset filenames that are actually different in Stable.
   *
   * @return string[]
   *   Filenames of Stable assets that differ form their core equivalents.
   */
  protected function assetsThatDifferInStable() {
    static $non_identical_files = [];
    if (!empty($non_identical_files)) {
      return $non_identical_files;
    }
    $core_assets = [];
    $stable_assets = [];
    $non_identical_files = [];

    $this->getAssets($core_assets, 'core/modules');
    $this->getAssets($core_assets, 'core/misc');
    $this->getAssets($stable_assets, 'core/themes/stable');

    // Assets that are in core but not Stable.
    $in_core_only = array_diff(array_keys($core_assets), array_keys($stable_assets));

    // Files that are included in Stable but never loaded by core themes. This
    // files file.admin.css and filter.admin.css were removed in core so
    // Stable's libraries-override never replaces them with core's version. The
    // simpletest-result-summary.html.twig template is skipped because the
    // Simpletest module was removed from core.
    $in_stable_but_never_loaded = [
      'file.admin.css',
      'filter.admin.css',
      'simpletest-result-summary.html.twig',
    ];

    // Files related to normalize.css can be skipped. These files are related to
    // the core/normalize library and not inherited in the same manner.
    $normalize_css_assets = ['normalize.css', 'normalize-fixes.css'];

    // Create an array of skippable asset files.
    $assets_to_skip = array_merge($in_core_only, $in_stable_but_never_loaded, $normalize_css_assets);

    foreach ($stable_assets as $filename => $stable_asset_contents) {
      if (in_array($filename, $assets_to_skip)) {
        continue;
      }

      $core_asset_contents = $core_assets[$filename];

      // Most of the remaining logic in this loop is replacing expected
      // differences between assets so the string comparison only surfaces
      // functional differences between the two.
      // Off canvas and layout builder CSS have different relative paths to
      // image files than all other CSS files being tested.
      if (strpos($filename, 'off-canvas') !== FALSE) {
        $core_asset_contents = str_replace('(../icons', '(../../../images/core/icons', $core_asset_contents);
      }
      elseif (strpos($filename, 'layout-builder') !== FALSE || strpos($filename, 'settings_tray') !== FALSE) {
        $core_asset_contents = str_replace('../misc', '../../misc', $core_asset_contents);
      }
      else {
        $core_asset_contents = str_replace('(../../../../misc/', '(../../../images/core/', $core_asset_contents);
        $core_asset_contents = str_replace('(../../../misc/', '(../../images/core/', $core_asset_contents);
      }

      // Account for several additional differences between core assets and the
      // Stable equivalents.
      $before = [
        '* Default theme implementation',
        'Default template for',
        "*\n * @ingroup themeable\n */",
        "*\n* @ingroup themeable\n*/",
        'Default theme override',
        'Default view template',
        "* Theme override of a container used to wrap the media library's\n * modal dialog interface.",
      ];
      $after = [
        '* Theme override',
        'Theme override for',
        '*/',
        '*/',
        'Theme override',
        'Theme override',
        "* Theme override of a container used to wrap the media library's modal dialog\n * interface.",
      ];
      $core_asset_contents = str_replace($before, $after, $core_asset_contents);

      // Account for the module-specific subdirectories used by Stable CSS
      // files that consume image files.
      foreach (['shortcut', 'quickedit', 'color', 'views_ui', 'image'] as $module) {
        $stable_asset_contents = str_replace("/../images/$module/", '/images/', $stable_asset_contents);
      }

      // If the asset contents still differ after being changed to account for
      // expected differences between core and stable, they are not identical
      // and should be added to the array returned by this method.
      if ($stable_asset_contents !== $core_asset_contents) {
        $non_identical_files[] = $filename;
      }
    }

    return $non_identical_files;
  }

  /**
   * Confirms that theme assets are decoupled from Stable.
   *
   * @param string $path
   *   The path to the theme being tested.
   * @param string[] $to_skip
   *   Assets to skip in the test.
   *
   * @dataProvider providerTestDecoupledStable
   */
  public function testDecoupledStable($path, array $to_skip) {
    $assets = [];

    // Get a list of asset files that differ between core and Stable.
    $files_to_check = $this->assetsThatDifferInStable();

    // Get all non-test .twig and .css assets within $path.
    $this->getAssets($assets, $path);

    // Of the assets to check, exclude any that appear within the theme being
    // tested as that means they're already overridden. Also exclude those
    // listed in $to_skip.
    $files_inherited_from_stable = array_diff($files_to_check, array_keys($assets), $to_skip);
    $this->assertEmpty($files_inherited_from_stable);
  }

  /**
   * Data provider.
   *
   * The to-skip arrays should become increasingly smaller as issues that
   * address Stable library dependencies are completed.
   *
   * @return array[]
   *   Themes and the asset filenames to be ignored.
   */
  public function providerTestDecoupledStable() {
    return [
      'claro' => [
        'path' => 'core/themes/claro',
        'to-skip' => [
          // The changes in the core version have acceptable results.
          'block.admin.css',
          // The only difference is to address an issue with normalize.css 3.0.3
          // that is fixed by updating to version 8.0.1.
          // @see https://drupal.org/node/2821525
          'filter.caption.css',
          // The only difference is overridden in Claro CSS.
          'progress.module.css',
          // This template is not used by core themes.
          'status-report.html.twig',
          // The following two templates can be skipped as the only difference
          // is the removal of an unnecessary data-striping attribute in core's
          // version.
          'system-modules-details.html.twig',
          'system-modules-uninstall.html.twig',
        ],
      ],
      'seven' => [
        'path' => 'core/themes/seven',
        'to-skip' => [
          // The changes in the core version have acceptable results.
          'block.admin.css',
          // The only difference is to address an issue with normalize.css 3.0.3
          // that is fixed by updating to version 8.0.1.
          // @see https://drupal.org/node/2821525
          'filter.caption.css',
          // The only difference is overridden in Seven CSS.
          'progress.module.css',
          // This template is not used by core themes.
          'status-report.html.twig',
          // The difference is a desired markup change.
          // @see https://drupal.org/node/3113211
          'views-ui-views-listing-table.html.twig',
          // The following two templates can be skipped as the only difference
          // is the removal of an unnecessary data-striping attribute in core's
          // version.
          'system-modules-details.html.twig',
          'system-modules-uninstall.html.twig',
        ],
      ],
      'bartik' => [
        'path' => 'core/themes/bartik',
        'to-skip' => [
          // The only difference is overridden in Bartik CSS.
          'container-inline.module.css',
          // The only difference is overridden in Bartik CSS.
          'progress.module.css',
          // The changes are acceptable as Bartik is not an admin theme.
          'block.admin.css',
          // The only difference is to address an issue with normalize.css 3.0.3
          // that is fixed by updating to version 8.0.1.
          // @see https://drupal.org/node/2821525
          'filter.caption.css',
          // The only difference is a desired markup change.
          // @see https://drupal.org/node/2528420
          'install-page.html.twig',
          // This template is not used by core themes.
          'status-report.html.twig',
          // The differences in Stable vs. core are acceptable ones.
          'status-report-counter.html.twig',
          // The only difference is one column of inconsequential whitespace.
          'status-report-general-info.html.twig',
          // The differences in Stable vs. core are acceptable ones.
          'system-status-counter.css',
          // The difference is a desired markup change.
          // @see https://drupal.org/node/3113211
          'views-ui-views-listing-table.html.twig',
          // The following two templates can be skipped as the only difference
          // is the removal of an unnecessary data-striping attribute in core's
          // version.
          'system-modules-details.html.twig',
          'system-modules-uninstall.html.twig',
        ],
      ],
      'umami' => [
        'path' => 'core/profiles/demo_umami/themes/umami',
        'to-skip' => [
          // The only difference is overridden in Umami CSS.
          'container-inline.module.css',
          // The only difference is overridden in Umami CSS.
          'progress.module.css',
          // The changes are acceptable as Umami is not an admin theme.
          'block.admin.css',
          // The only difference is to address an issue with normalize.css 3.0.3
          // that is fixed by updating to version 8.0.1.
          // @see https://drupal.org/node/2821525
          'filter.caption.css',
          // The only difference is a desired markup change.
          // @see https://drupal.org/node/2528420
          'install-page.html.twig',
          // This template is not used by core themes.
          'status-report.html.twig',
          // The differences in Stable vs. core are acceptable ones.
          'status-report-counter.html.twig',
          // The only difference is one column of inconsequential whitespace.
          'status-report-general-info.html.twig',
          // The differences in Stable vs. core are acceptable ones.
          'system-status-counter.css',
          // The difference is a desired markup change.
          // @see https://drupal.org/node/3113211
          'views-ui-views-listing-table.html.twig',
          // The following two templates can be skipped as the only difference
          // is the removal of an unnecessary data-striping attribute in core's
          // version.
          'system-modules-details.html.twig',
          'system-modules-uninstall.html.twig',
        ],
      ],
    ];
  }

}
