<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Migration
 * @group migrate
 */
class MigrationTest extends MigrateProcessTestCase {

  /**
   * @covers ::transform
   */
  public function testTransformWithStubSkipping() {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize(MigrationInterface::class);
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $destination_id_map->lookupDestinationId([1])->willReturn(NULL);

    // Ensure the migration plugin manager returns our migration.
    $migration_plugin_manager->createInstances(Argument::exact(['destination_migration']))
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);

    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $migration_plugin->id()->willReturn('actual_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldNotBeCalled();

    $migration = new Migration($configuration, '', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * @covers ::transform
   */
  public function testTransformWithStubbing() {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize('Drupal\migrate\Plugin\Migration');
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $migration_plugin_manager->createInstances(['destination_migration'])
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);
    $destination_id_map->lookupDestinationId([1])->willReturn(NULL);
    $destination_id_map->saveIdMapping(Argument::any(), Argument::any(), MigrateIdMapInterface::STATUS_NEEDS_UPDATE)->willReturn(NULL);

    $configuration = [
      'no_stub' => FALSE,
      'migration' => 'destination_migration',
    ];

    $migration_plugin->id()->willReturn('actual_migration');
    $destination_migration->id()->willReturn('destination_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldBeCalled();
    $destination_migration->getProcess()->willReturn([]);
    $destination_migration->getSourceConfiguration()->willReturn([]);

    $source_plugin = $this->prophesize(MigrateSourceInterface::class);
    $source_plugin->getIds()->willReturn(['nid']);
    $destination_migration->getSourcePlugin()->willReturn($source_plugin->reveal());
    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $destination_plugin->import(Argument::any())->willReturn([2]);
    $destination_migration->getDestinationPlugin(TRUE)->willReturn($destination_plugin->reveal());

    $migration = new Migration($configuration, '', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

}
