<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\Plugin\MigrateSourcePluginManager;
use Drupal\migrate\Plugin\NoSourcePluginDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\NoSourcePluginDecorator
 * @group migrate
 */
class NoSourcePluginDecoratorTest extends UnitTestCase {

  /**
   * @covers ::getDefinitions
   * @dataProvider providerGetDefinitions
   */
  public function testGetDefinitions(array $definition, bool $source_exists): void {
    $source_manager = $this->createMock(MigrateSourcePluginManager::class);
    $source_manager->expects($this->any())
      ->method('hasDefinition')
      ->willReturn($source_exists);
    $container = new ContainerBuilder();
    $container->set('plugin.manager.migrate.source', $source_manager);
    \Drupal::setContainer($container);

    $discovery_interface = $this->createMock(DiscoveryInterface::class);
    $discovery_interface->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([$definition]);

    $decorator = new NoSourcePluginDecorator($discovery_interface);
    $results = $decorator->getDefinitions();
    if ($source_exists) {
      $this->assertEquals([$definition], $results);
    }
    else {
      $this->assertEquals([], $results);
    }
  }

  /**
   * Provides data for testGetDefinitions().
   */
  public static function providerGetDefinitions(): array {
    return [
      'source exists' => [
        [
          'source' => ['plugin' => 'valid_plugin'],
          'process' => [],
          'destination' => [],
        ],
        TRUE,
      ],
      'source does not exist' => [
        [
          'source' => ['plugin' => 'invalid_plugin'],
          'process' => [],
          'destination' => [],
        ],
        FALSE,
      ],
      'source is not defined' => [
        [
          'process' => [],
          'destination' => [],
        ],
        FALSE,
      ],
    ];
  }

}
