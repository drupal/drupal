<?php

/**
 * @file
 * Contains \Drupal\Tests\block_content\Unit\Menu\BlockContentLocalTasksTest.
 */

namespace Drupal\Tests\block_content\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests existence of block_content local tasks.
 *
 * @group block_content
 */
class BlockContentLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
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
        'entity.block_content.collection',
      ),
      1 => array(
        'block_content.list_sub',
        'entity.block_content_type.collection',
      ),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBlockContentListingRoutes() {
    return array(
      array('entity.block_content.collection', 'entity.block_content_type.collection'),
    );
  }

}
