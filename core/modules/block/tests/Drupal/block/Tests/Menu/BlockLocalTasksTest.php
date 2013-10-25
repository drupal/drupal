<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Menu\BlockLocalTasksTest.
 */

namespace Drupal\block\Tests\Menu {

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of block local tasks.
 *
 * @group Drupal
 * @group Block
 */
class BlockLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Block local tasks test',
      'description' => 'Test block local tasks.',
      'group' => 'Block',
    );
  }

  public function setUp() {
    $this->moduleList = array('block' => 'core/modules/block/block.module');
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub(array('system.theme' => array(
      'default' => 'test_c',
    )));
    \Drupal::getContainer()->set('config.factory', $config_factory);
  }

  /**
   * Tests the admin edit local task.
   */
  public function testBlockAdminLocalTasks() {
    $this->assertLocalTasks('block.admin_edit', array(array('block.admin_edit')));
  }

  /**
   * Tests the block admin display local tasks.
   *
   * @dataProvider providerTestBlockAdminDisplay
   */
  public function testBlockAdminDisplay($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function providerTestBlockAdminDisplay() {
    return array(
      array('block.admin_display', array(array('block.admin_display'), array('block.admin_display_theme:test_b', 'block.admin_display_theme:test_c'))),
      array('block.admin_display_theme', array(array('block.admin_display'), array('block.admin_display_theme:test_b', 'block.admin_display_theme:test_c'))),
    );
  }

}

}

namespace {
  if (!function_exists('list_themes')) {
    function list_themes() {
      $themes = array();
      $themes['test_a'] = (object) array(
        'status' => 0,
      );
      $themes['test_b'] = (object) array(
        'status' => 1,
        'info' => array(
          'name' => 'test_b',
        ),
      );
      $themes['test_c'] = (object) array(
        'status' => 1,
        'info' => array(
          'name' => 'test_c',
        ),
      );

      return $themes;
    }
  }
}
