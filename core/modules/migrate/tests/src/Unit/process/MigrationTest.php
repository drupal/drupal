<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Migration
 * @group migrate
 * @group legacy
 */
class MigrationTest extends MigrationLookupTestCase {

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
   * Tests a lookup with no stubbing.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Not passing the migrate lookup service as the fifth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateLookupInterface. See https://www.drupal.org/node/3047268
   * @expectedDeprecation Not passing the migrate stub service as the sixth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateStubInterface. See https://www.drupal.org/node/3047268
   */
  public function testTransformWithStubSkipping() {
    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $this->migrateLookup->lookup('destination_migration', [1])->willReturn([]);
    $this->prepareContainer();
    $migration = new Migration($configuration, '', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * Tests a lookup with stubbing.
   *
   * @covers ::transform
   *
   * @expectedDeprecation Not passing the migrate lookup service as the fifth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateLookupInterface. See https://www.drupal.org/node/3047268
   * @expectedDeprecation Not passing the migrate stub service as the sixth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateStubInterface. See https://www.drupal.org/node/3047268
   */
  public function testTransformWithStubbing() {
    $configuration = [
      'no_stub' => FALSE,
      'migration' => 'destination_migration',
    ];
    $this->migrateLookup->lookup('destination_migration', [1])->willReturn([]);
    $this->migrateStub->createStub('destination_migration', [1], [], FALSE)->willReturn([2]);
    $this->prepareContainer();
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
    $id_map->lookupDestinationIds([1])->willReturn(NULL);
    $id_map->saveIdMapping(Argument::any(), Argument::any(), MigrateIdMapInterface::STATUS_NEEDS_UPDATE)->willReturn(NULL);

    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()->willReturn($id_map->reveal());
    return $migration;
  }

  /**
   * Tests that processing is skipped when the input value is empty.
   *
   * @expectedDeprecation Not passing the migrate lookup service as the fifth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateLookupInterface. See https://www.drupal.org/node/3047268
   * @expectedDeprecation Not passing the migrate stub service as the sixth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateStubInterface. See https://www.drupal.org/node/3047268
   */
  public function testSkipOnEmpty() {
    $configuration = [
      'migration' => 'foobaz',
    ];
    $this->migration_plugin->id()->willReturn(uniqid());
    $this->migration_plugin_manager->createInstances(['foobaz'])
      ->willReturn(['foobaz' => $this->migration_plugin->reveal()]);
    $this->prepareContainer();
    $migration = new Migration($configuration, 'migration', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $this->expectException(MigrateSkipProcessException::class);
    $migration->transform(FALSE, $this->migrateExecutable, $this->row, 'foo');
  }

  /**
   * Tests a successful lookup.
   *
   * @param array $source_id_values
   *   The source id(s) of the migration map.
   * @param array $destination_id_values
   *   The destination id(s) of the migration map.
   * @param string|array $source_value
   *   The source value(s) for the migration process plugin.
   * @param string|array $expected_value
   *   The expected value(s) of the migration process plugin.
   *
   * @dataProvider successfulLookupDataProvider
   *
   * @expectedDeprecation Not passing the migrate lookup service as the fifth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateLookupInterface. See https://www.drupal.org/node/3047268
   * @expectedDeprecation Not passing the migrate stub service as the sixth parameter to Drupal\migrate\Plugin\migrate\process\MigrationLookup::__construct is deprecated in drupal:8.8.0 and will throw a type error in drupal:9.0.0. Pass an instance of \Drupal\migrate\MigrateStubInterface. See https://www.drupal.org/node/3047268
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function testSuccessfulLookup(array $source_id_values, array $destination_id_values, $source_value, $expected_value) {
    $configuration = [
      'migration' => 'foobaz',
    ];
    $this->migrateLookup->lookup('foobaz', $source_id_values)->willReturn([$destination_id_values]);
    $this->prepareContainer();
    $migration = new Migration($configuration, 'migration', [], $this->migration_plugin->reveal(), $this->migration_plugin_manager->reveal(), $this->process_plugin_manager->reveal());
    $this->assertSame($expected_value, $migration->transform($source_value, $this->migrateExecutable, $this->row, 'foo'));
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   *   The data.
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
