<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Menu\BlockContentLocalTasksTest.
 */

namespace Drupal\block_content\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests existence of block_content local tasks.
 *
 * @group Drupal
 * @group Block
 */
class BlockContentLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Custom Block local tasks test',
      'description' => 'Test block_content local tasks.',
      'group' => 'Block',
    );
  }

  public function setUp() {
    $this->directoryList = array(
      'block' => 'core/modules/block',
      'block_content' => 'core/modules/block_content',
    );
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
   * Checks block_content listing local tasks.
   *
   * @dataProvider getBlockContentListingRoutes
   */
  public function testBlockContentListLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array(
        'block.admin_display',
        'block_content.list',
      ),
      1 => array(
        'block_content.list_sub',
        'block_content.type_list',
      ),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBlockContentListingRoutes() {
    return array(
      array('block_content.list', 'block_content.type_list'),
    );
  }

}
