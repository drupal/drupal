<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\ViewsBlockTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewKernelTestBase;

/**
 * Tests native behaviors of the block views plugin.
 *
 * @group views
 */
class ViewsBlockTest extends ViewKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('block_test_views'));
  }

  /**
   * Tests that ViewsBlock::getMachineNameSuggestion() produces the right value.
   *
   * @see \Drupal\views\Plugin\Block::getmachineNameSuggestion().
   */
  public function testMachineNameSuggestion() {
    $plugin_definition = array(
      'provider' => 'views',
    );
    $plugin_id = 'views_block:test_view_block-block_1';
    $views_block = ViewsBlock::create($this->container, array(), $plugin_id, $plugin_definition);

    $this->assertEqual($views_block->getMachineNameSuggestion(), 'views_block__test_view_block_block_1');
  }

}
