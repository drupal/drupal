<?php

namespace Drupal\Tests\block_content\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests existence of block_content local tasks.
 *
 * @group block_content
 */
class BlockContentLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp() {
    $this->directoryList = [
      'block' => 'core/modules/block',
      'block_content' => 'core/modules/block_content',
    ];
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub([
      'system.theme' => ['default' => 'test_c'],
    ]);

    $themes = [];
    $themes['test_a'] = (object) [
      'status' => 0,
    ];
    $themes['test_b'] = (object) [
      'status' => 1,
      'info' => [
        'name' => 'test_b',
      ],
    ];
    $themes['test_c'] = (object) [
      'status' => 1,
      'info' => [
        'name' => 'test_c',
      ],
    ];
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
    $this->assertLocalTasks($route, [
      0 => [
        'block.admin_display',
        'entity.block_content.collection',
      ],
      1 => [
        'block_content.list_sub',
        'entity.block_content_type.collection',
      ],
    ]);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBlockContentListingRoutes() {
    return [
      ['entity.block_content.collection', 'entity.block_content_type.collection'],
    ];
  }

}
