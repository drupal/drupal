<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrateSourceInterface;
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
    $migration_entity = $this->prophesize(MigrationInterface::class);
    $migration_storage = $this->prophesize(EntityStorageInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize(MigrationInterface::class);
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $migration_storage->loadMultiple(['destination_migration'])
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);
    $destination_id_map->lookupDestinationId([1])->willReturn(NULL);

    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $migration_entity->id()->willReturn('actual_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldNotBeCalled();

    $migration = new Migration($configuration, '', [], $migration_entity->reveal(), $migration_storage->reveal(), $process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * @covers ::transform
   */
  public function testTransformWithStubbing() {
    $migration_entity = $this->prophesize(MigrationInterface::class);
    $migration_storage = $this->prophesize(EntityStorageInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize(MigrationInterface::class);
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $migration_storage->loadMultiple(['destination_migration'])
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);
    $destination_id_map->lookupDestinationId([1])->willReturn(NULL);

    $configuration = [
      'no_stub' => FALSE,
      'migration' => 'destination_migration',
    ];

    $migration_entity->id()->willReturn('actual_migration');
    $destination_migration->id()->willReturn('destination_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldBeCalled();
    $destination_migration->get('process')->willReturn([]);
    $destination_migration->get('source')->willReturn([]);

    $source_plugin = $this->prophesize(MigrateSourceInterface::class);
    $source_plugin->getIds()->willReturn(['nid']);
    $destination_migration->getSourcePlugin()->willReturn($source_plugin->reveal());
    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $destination_plugin->import(Argument::any())->willReturn([2]);
    $destination_migration->getDestinationPlugin(TRUE)->willReturn($destination_plugin->reveal());

    $migration = new Migration($configuration, '', [], $migration_entity->reveal(), $migration_storage->reveal(), $process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

}
