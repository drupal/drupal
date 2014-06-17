<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\BlockDependenciesTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests exposed views derived blocks have the correct config dependencies.
 */
class BlockDependenciesTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_exposed_block');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'user');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Views block config dependencies',
      'description' => 'Test views block config dependencies functionality.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Tests that exposed filter blocks have the correct dependencies.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock::getDerivativeDefinitions()
   */
  public function testExposedBlock() {
    $block = $this->createBlock('views_exposed_filter_block:test_exposed_block-page_1');
    $dependencies = $block->calculateDependencies();
    $expected = array(
      'entity' => array('views.view.test_exposed_block'),
      'module' => array('views'),
      'theme' => array('stark')
    );
    $this->assertIdentical($expected, $dependencies);
  }

  /**
   * Tests that exposed filter blocks have the correct dependencies.
   *
   * @see \Drupal\views\Plugin\Derivative\ViewsBlock::getDerivativeDefinitions()
   */
  public function testViewsBlock() {
    $block = $this->createBlock('views_block:content_recent-block_1');
    $dependencies = $block->calculateDependencies();
    $expected = array(
      'entity' => array('views.view.content_recent'),
      'module' => array('views'),
      'theme' => array('stark')
    );
    $this->assertIdentical($expected, $dependencies);
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
   *     $this->createBlock('system_powered_by_block', array(
   *       'label' => t('Hello, world!'),
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - label: Random string.
   *   - id: Random string.
   *   - region: 'sidebar_first'.
   *   - theme: The default theme.
   *   - visibility: Empty array.
   *   - cache: array('max_age' => 0).
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   */
  protected function createBlock($plugin_id, array $settings = array()) {
    $settings += array(
      'plugin' => $plugin_id,
      'region' => 'sidebar_first',
      'id' => strtolower($this->randomName(8)),
      'theme' => \Drupal::config('system.theme')->get('default'),
      'label' => $this->randomName(8),
      'visibility' => array(),
      'weight' => 0,
      'cache' => array(
        'max_age' => 0,
      ),
    );
    foreach (array('region', 'id', 'theme', 'plugin', 'weight') as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    foreach ($settings['visibility'] as $id => $visibility) {
      $settings['visibility'][$id]['id'] = $id;
    }
    $values['settings'] = $settings;
    $block = entity_create('block', $values);
    $block->save();
    return $block;
  }

}
