<?php

namespace Drupal\Tests\migrate\Unit;

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
  public function testLookup() {
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
   * Tests that an appropriate message is logged if a PluginException is thrown.
   */
  public function testExceptionOnMigrationNotFound() {
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $migration_plugin_manager->createInstances('bad_plugin')->willReturn([]);
    $this->expectException(PluginNotFoundException::class);
    $lookup = new MigrateLookup($migration_plugin_manager->reveal());
    $lookup->lookup('bad_plugin', [1]);
  }

}
