<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Unit\Menu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests existence of block_content local tasks.
 *
 * @group block_content
 */
class BlockContentLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = [
      'system' => 'core/modules/system',
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
    $theme_handler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $theme_handler->expects($this->any())
      ->method('listInfo')
      ->willReturn($themes);

    // Add services required for block local tasks.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([]);

    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('theme_handler', $theme_handler);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Checks block_content listing local tasks.
   *
   * @dataProvider getBlockContentListingRoutes
   */
  public function testBlockContentListLocalTasks($route): void {
    $this->assertLocalTasks($route, [
      0 => [
        'system.admin_content',
        'entity.block_content.collection',
      ],
    ]);
  }

  /**
   * Provides a list of routes to test.
   */
  public static function getBlockContentListingRoutes() {
    return [
      ['entity.block_content.collection', 'system.admin_content'],
    ];
  }

}
