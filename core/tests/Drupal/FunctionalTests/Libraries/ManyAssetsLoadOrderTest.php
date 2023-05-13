<?php

namespace Drupal\FunctionalTests\Libraries;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of many weighted assets.
 *
 * @group libraries
 */
class ManyAssetsLoadOrderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['many_assets_test'];

  /**
   * Loads a page with many assets that have different but close weights.
   *
   * Confirms the load order reflects the configured weights for each asset.
   */
  public function testLoadOrder() {
    $this->drupalGet('many_assets_test');

    $js = $this->getSession()->getPage()->findAll('css', 'script[data-weight]');
    $js_files = array_map(function ($item) {
      return $item->getAttribute('data-weight');
    }, $js);
    $this->assertGreaterThan(0, count($js_files));
    $js_files_sorted = $js_files;
    asort($js_files_sorted);

    // If the JavaScript files are loading in the proper order, the sorted array
    // should match the unsorted one.
    $this->assertSame($js_files_sorted, $js_files);

    $css = $this->getSession()->getPage()->findAll('css', 'link[data-weight]');
    $css_files = array_map(function ($item) {
      return $item->getAttribute('data-weight');
    }, $css);
    $this->assertGreaterThan(0, count($css_files));
    $css_files_sorted = $css_files;
    asort($css_files_sorted);

    // If the CSS files are loading in the proper order, the sorted array should
    // match the unsorted one.
    $this->assertSame($css_files_sorted, $css_files);
  }

}
