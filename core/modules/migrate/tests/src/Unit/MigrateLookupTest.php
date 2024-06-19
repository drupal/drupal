<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\MigrateLookup;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Provides unit testing for the migration lookup service.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\MigrateLookup
 */
class MigrateLookupTest extends MigrateTestCase {

  /**
   * Tests the lookup function.
   *
   * @covers ::lookup
   */
  public function testLookup(): void {
    $source_ids = ['id' => '1'];

    $destination_ids = [[2]];

    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationIds($source_ids)->willReturn($destination_ids);

    $destination = $this->prophesize(MigrateDestinationInterface::class);
    $destination->getIds()->willReturn(['id' => ['type' => 'integer']]);

    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()->willReturn($id_map->reveal());
    $migration->getDestinationPlugin()->willReturn($destination->reveal());

    $plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $plugin_manager->createInstances('test_migration')->willReturn([$migration->reveal()]);

    $lookup = new MigrateLookup($plugin_manager->reveal());

    $this->assertSame([['id' => 2]], $lookup->lookup('test_migration', $source_ids));
  }

  /**
   * Tests message logged when a single migration is not found.
   *
   * @dataProvider providerExceptionOnMigrationNotFound
   */
  public function testExceptionOnMigrationNotFound($migrations, $message): void {
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $migration_plugin_manager->createInstances($migrations)->willReturn([]);
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage($message);
    $lookup = new MigrateLookup($migration_plugin_manager->reveal());
    $lookup->lookup($migrations, [1]);
  }

  /**
   * Provides data for testExceptionOnMigrationNotFound.
   */
  public static function providerExceptionOnMigrationNotFound() {
    return [
      'string' => [
        'bad_plugin',
        "Plugin ID 'bad_plugin' was not found.",
      ],
      'array one item' => [
        ['bad_plugin'],
        "Plugin ID 'bad_plugin' was not found.",
      ],
    ];
  }

  /**
   * Tests message logged when multiple migrations are not found.
   *
   * @dataProvider providerExceptionOnMultipleMigrationsNotFound
   */
  public function testExceptionOnMultipleMigrationsNotFound($migrations, $message): void {
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $migration_plugin_manager->createInstances($migrations)->willReturn([]);
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage($message);
    $lookup = new MigrateLookup($migration_plugin_manager->reveal());
    $lookup->lookup($migrations, [1]);
  }

  /**
   * Provides data for testExceptionOnMultipleMigrationsNotFound.
   */
  public static function providerExceptionOnMultipleMigrationsNotFound() {
    return [
      'array two items' => [
        ['foo', 'bar'],
        "Plugin IDs 'foo', 'bar' were not found.",
      ],
      'empty array' => [
        [],
        "Plugin IDs '' were not found.",
      ],
    ];
  }

}
