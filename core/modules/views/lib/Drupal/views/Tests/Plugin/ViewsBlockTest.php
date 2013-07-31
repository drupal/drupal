<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\ViewsBlockTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the block views plugin.
 */
class ViewsBlockTest extends ViewUnitTestBase {

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

  public static function getInfo() {
    return array(
      'name' => 'Views block',
      'description' => 'Tests the block views plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('block_test_views'));
  }

  /**
   * Tests generateBlockInstanceID.
   *
   * @see \Drupal\views\Plugin\Block::generateBlockInstanceID().
   */
  public function testGenerateBlockInstanceID() {

    $plugin_definition = array(
      'module' => 'views',
    );
    $plugin_id = 'views_block:test_view_block-block_1';
    $views_block = ViewsBlock::create($this->container, array(), $plugin_id, $plugin_definition);

    $storage_controller = $this->container->get('plugin.manager.entity')->getStorageController('block');

    // Generate a instance ID on a block without any instances.
    $this->assertEqual($views_block->generateBlockInstanceID($storage_controller), 'views_block__test_view_block_block_1');

    $values = array(
      'plugin' => $plugin_id,
      'id' => 'stark.views_block__test_view_block_block_1',
      'module' => 'views',
      'settings' => array(
        'module' => 'views',
      )
    );
    $storage_controller->create($values)->save();
    $this->assertEqual($views_block->generateBlockInstanceID($storage_controller), 'views_block__test_view_block_block_1_2');

    // Add another one block instance and ensure the block instance went up.
    $values['id'] = 'stark.views_block__test_view_block_block_1_2';
    $storage_controller->create($values)->save();
    $this->assertEqual($views_block->generateBlockInstanceID($storage_controller), 'views_block__test_view_block_block_1_3');
  }

}
