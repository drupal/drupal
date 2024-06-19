<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests views block config dependencies functionality.
 *
 * @group views
 */
class BlockDependenciesTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_exposed_block'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'block', 'user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Tests that exposed filter blocks have the correct dependencies.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock::getDerivativeDefinitions()
   */
  public function testExposedBlock(): void {
    $block = $this->createBlock('views_exposed_filter_block:test_exposed_block-page_1');
    $dependencies = $block->calculateDependencies()->getDependencies();
    $expected = [
      'config' => ['views.view.test_exposed_block'],
      'module' => ['views'],
      'theme' => ['stark'],
    ];
    $this->assertSame($expected, $dependencies);
  }

  /**
   * Tests that exposed filter blocks have the correct dependencies.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsBlock::getDerivativeDefinitions()
   */
  public function testViewsBlock(): void {
    $block = $this->createBlock('views_block:content_recent-block_1');
    $dependencies = $block->calculateDependencies()->getDependencies();
    $expected = [
      'config' => ['views.view.content_recent'],
      'module' => ['views'],
      'theme' => ['stark'],
    ];
    $this->assertSame($expected, $dependencies);
  }

  /**
   * Creates a block instance based on default settings.
   *
   * @param string $plugin_id
   *   The plugin ID of the block type for this block instance.
   * @param array $settings
   *   (optional) An associative array of settings for the block entity.
   *   Override the defaults by specifying the key and value in the array, for
   *   example:
   *   @code
   *     $this->createBlock('system_powered_by_block', [
   *       'label' => 'Hello, world!',
   *     ]);
   *   @endcode
   *   The following defaults are provided:
   *   - label: Random string.
   *   - id: Random string.
   *   - region: 'sidebar_first'.
   *   - theme: The default theme.
   *   - visibility: Empty array.
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   */
  protected function createBlock($plugin_id, array $settings = []) {
    $settings += [
      'plugin' => $plugin_id,
      'region' => 'sidebar_first',
      'id' => $this->randomMachineName(8),
      'theme' => $this->config('system.theme')->get('default'),
      'label' => $this->randomMachineName(8),
      'visibility' => [],
      'weight' => 0,
    ];
    $values = [];
    foreach (['region', 'id', 'theme', 'plugin', 'weight', 'visibility'] as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    foreach ($values['visibility'] as $id => $visibility) {
      $values['visibility'][$id]['id'] = $id;
    }
    $values['settings'] = $settings;
    $block = Block::create($values);
    $block->save();
    return $block;
  }

}
