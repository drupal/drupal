<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\MigrateSkipProcessException;
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
 * @group legacy
 */
class MigrationTest extends MigrateProcessTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration_plugin;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migration_plugin_manager;

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $process_plugin_manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $this->process_plugin_manager = $this->prophesize(MigratePluginManager::class);
  }

  /**
   * @covers ::transform
   */
  public function testTransformWithStubSkipping() {
    $destination_migration = $this->getMigration();
    $destination_migration->getDestinationPlugin(TRUE)->shouldNotBeCalled();

    // Ensure the migration plugin manager returns our migration.
    $this->migration_plugin_manager->createInstances(Argument::exact(['destination_migration']))
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);

    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $this->migration_plugin->id()->willReturn('actual_migration');

    $migration = new Migration($configuration, '', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * @covers ::transform
   */
  public function testTransformWithStubbing() {
    $destination_migration = $this->getMigration();
    $this->migration_plugin_manager->createInstances(['destination_migration'])
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);

    $configuration = [
      'no_stub' => FALSE,
      'migration' => 'destination_migration',
    ];

    $this->migration_plugin->id()->willReturn('actual_migration');
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

    $migration = new Migration($configuration, '', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

  /**
   * Creates a mock Migration instance.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   A mock Migration instance.
   */
  protected function getMigration() {
    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationId([1])->willReturn(NULL);
    $id_map->saveIdMapping(Argument::any(), Argument::any(), MigrateIdMapInterface::STATUS_NEEDS_UPDATE)->willReturn(NULL);

    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()->willReturn($id_map->reveal());
    return $migration;
  }

  /**
   * Tests that processing is skipped when the input value is empty.
   */
  public function testSkipOnEmpty() {
    $configuration = [
      'migration' => 'foobaz',
    ];
    $this->migration_plugin->id()->willReturn(uniqid());
    $this->migration_plugin_manager->createInstances(['foobaz'])
      ->willReturn(['foobaz' => $this->migration_plugin->reveal()]);
    $migration = new Migration($configuration, 'migration', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
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
    $configuration = [
      'migration' => 'foobaz',
    ];
    $this->migration_plugin->id()->willReturn(uniqid());

    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationId($source_id_values)->willReturn($destination_id_values);
    $this->migration_plugin->getIdMap()->willReturn($id_map->reveal());

    $this->migration_plugin_manager->createInstances(['foobaz'])
      ->willReturn(['foobaz' => $this->migration_plugin->reveal()]);

    $migrationStorage = $this->prophesize(EntityStorageInterface::class);
    $migrationStorage
      ->loadMultiple(['foobaz'])
      ->willReturn([$this->migration_plugin->reveal()]);

    $migration = new Migration($configuration, 'migration', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $this->assertSame($expected_value, $migration->transform($source_value, $this->migrateExecutable, $this->row, 'foo'));
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   */
  public function successfulLookupDataProvider() {
    return [
      'scalar_to_scalar' => [
        'source_ids' => [1],
        'destination_ids' => [3],
        'input_value' => 1,
        'expected_value' => 3,
      ],
      'scalar_to_array' => [
        'source_ids' => [1],
        'destination_ids' => [3, 'foo'],
        'input_value' => 1,
        'expected_value' => [3, 'foo'],
      ],
      'array_to_scalar' => [
        'source_ids' => [1, 3],
        'destination_ids' => ['foo'],
        'input_value' => [1, 3],
        'expected_value' => 'foo',
      ],
      'array_to_array' => [
        'source_ids' => [1, 3],
        'destination_ids' => [3, 'foo'],
        'input_value' => [1, 3],
        'expected_value' => [3, 'foo'],
      ],
    ];
  }

}
