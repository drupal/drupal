<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Libraries;

use Behat\Mink\Element\NodeElement;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the loading of many weighted assets.
 */
#[Group('libraries')]
#[RunTestsInSeparateProcesses]
class ManyAssetsLoadOrderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['many_assets_test', 'system'];

  /**
   * Loads a page with many assets that have different but close weights.
   *
   * Confirms the load order reflects the configured weights for each asset.
   */
  public function testLoadOrder(): void {
    $this->drupalGet('many_assets_test');

    $js = $this->getSession()->getPage()->findAll('css', 'script[data-weight]');
    $js_files = array_map(fn (NodeElement $item) => $item->getAttribute('data-weight'), $js);
    $this->assertGreaterThan(0, count($js_files));
    $js_files_sorted = $js_files;
    asort($js_files_sorted);

    // If the JavaScript files are loading in the proper order, the sorted array
    // should match the unsorted one.
    $this->assertSame($js_files_sorted, $js_files);

    $css = $this->getSession()->getPage()->findAll('css', 'link[data-weight]');
    $css_files = array_map(fn(NodeElement $item) => $item->getAttribute('data-weight'), $css);
    $this->assertGreaterThan(0, count($css_files));
    $css_files_sorted = $css_files;
    asort($css_files_sorted);

    // If the CSS files are loading in the proper order, the sorted array should
    // match the unsorted one.
    $this->assertSame($css_files_sorted, $css_files);
  }

}
