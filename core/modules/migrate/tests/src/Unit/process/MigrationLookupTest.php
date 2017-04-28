<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MigrationLookup
 * @group migrate
 */
class MigrationLookupTest extends MigrateProcessTestCase {

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

    $migration = new MigrationLookup($configuration, '', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
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

    $migration = new MigrationLookup($configuration, '', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

  /**
   * Tests that processing is skipped when the input value is empty.
   */
  public function testSkipOnEmpty() {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $configuration = [
      'migration' => 'foobaz',
    ];
    $migration_plugin->id()->willReturn(uniqid());
    $migration = new MigrationLookup($configuration, 'migration_lookup', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
    $this->setExpectedException(MigrateSkipProcessException::class);
    $migration->transform(0, $this->migrateExecutable, $this->row, 'foo');
  }

  /**
   * Tests a successful lookup.
   *
   * @dataProvider successfulLookupDataProvider
   *
   * @param array $source_id_values
   *   The source id(s) of the migration map.
   * @param array $destination_id_values
   *   The destination id(s) of the migration map.
   * @param string|array $source_value
   *   The source value(s) for the migration process plugin.
   * @param string|array $expected_value
   *   The expected value(s) of the migration process plugin.
   */
  public function testSuccessfulLookup($source_id_values, $destination_id_values, $source_value, $expected_value) {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $process_plugin_manager = $this->prophesize(MigratePluginManager::class);

    $configuration = [
      'migration' => 'foobaz',
    ];
    $migration_plugin->id()->willReturn(uniqid());

    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationId($source_id_values)->willReturn($destination_id_values);
    $migration_plugin->getIdMap()->willReturn($id_map->reveal());

    $migration_plugin_manager->createInstances(['foobaz'])
      ->willReturn(['foobaz' => $migration_plugin->reveal()]);

    $migrationStorage = $this->prophesize(EntityStorageInterface::class);
    $migrationStorage
      ->loadMultiple(['foobaz'])
      ->willReturn([$migration_plugin->reveal()]);

    $migration = new MigrationLookup($configuration, 'migration_lookup', [], $migration_plugin->reveal(), $migration_plugin_manager->reveal(), $process_plugin_manager->reveal());
    $this->assertSame($expected_value, $migration->transform($source_value, $this->migrateExecutable, $this->row, 'foo'));
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   */
  public function successfulLookupDataProvider() {
    return [
      // Test data for scalar to scalar.
      [
        // Source ID of the migration map.
        [1],
        // Destination ID of the migration map.
        [3],
        // Input value for the migration plugin.
        1,
        // Expected output value of the migration plugin.
        3,
      ],
      // Test data for scalar to array.
      [
        // Source ID of the migration map.
        [1],
        // Destination IDs of the migration map.
        [3, 'foo'],
        // Input value for the migration plugin.
        1,
        // Expected output values of the migration plugin.
        [3, 'foo'],
      ],
      // Test data for array to scalar.
      [
        // Source IDs of the migration map.
        [1, 3],
        // Destination ID of the migration map.
        ['foo'],
        // Input values for the migration plugin.
        [1, 3],
        // Expected output value of the migration plugin.
        'foo',
      ],
      // Test data for array to array.
      [
        // Source IDs of the migration map.
        [1, 3],
        // Destination IDs of the migration map.
        [3, 'foo'],
        // Input values for the migration plugin.
        [1, 3],
        // Expected output values of the migration plugin.
        [3, 'foo'],
      ],
    ];
  }

}
