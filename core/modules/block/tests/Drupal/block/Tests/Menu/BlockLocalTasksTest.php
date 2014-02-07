<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Menu\BlockLocalTasksTest.
 */

namespace Drupal\block\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
    $this->directoryList = array('block' => 'core/modules/block');
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub(array('system.theme' => array(
      'default' => 'test_c',
    )));

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
    $theme_handler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $theme_handler->expects($this->any())
      ->method('listInfo')
      ->will($this->returnValue($themes));

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('theme_handler', $theme_handler);
    \Drupal::setContainer($container);
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
