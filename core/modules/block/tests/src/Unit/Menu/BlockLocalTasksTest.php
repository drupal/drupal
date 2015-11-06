<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\Menu\BlockLocalTasksTest.
 */

namespace Drupal\Tests\block\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests block local tasks.
 *
 * @group block
 */
class BlockLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp() {
    $this->directoryList = array('block' => 'core/modules/block');
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub(array('system.theme' => array(
      'default' => 'test_c',
    )));

    $themes = array();
    $themes['test_a'] = (object) array(
      'status' => 1,
      'info' => array(
        'name' => 'test_a',
        'hidden' => TRUE,
      ),
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
    $theme_handler->expects($this->any())
      ->method('hasUi')
      ->willReturnMap([
        ['test_a', FALSE],
        ['test_b', TRUE],
        ['test_c', TRUE],
      ]);

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('theme_handler', $theme_handler);
    $container->set('app.root', $this->root);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the admin edit local task.
   */
  public function testBlockAdminLocalTasks() {
    $this->assertLocalTasks('entity.block.edit_form', array(array('entity.block.edit_form')));
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
