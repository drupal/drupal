<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MigrationLookup
 * @group migrate
 */
class MigrationLookupTest extends MigrationLookupTestCase {

  /**
   * @covers ::transform
   */
  public function testTransformWithStubSkipping(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize(MigrationInterface::class);
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $destination_id_map->lookupDestinationIds([1])->willReturn(NULL);

    // Ensure the migration plugin manager returns our migration.
    $migration_plugin_manager->createInstances(Argument::exact(['destination_migration']))
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);

    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $migration_plugin->id()->willReturn('actual_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldNotBeCalled();

    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * @covers ::transform
   *
   * @dataProvider providerTestTransformWithStubbing
   */
  public function testTransformWithStubbing($exception_class, $exception_message, $expected_message): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('destination_migration', [1])->willReturn(NULL);
    $this->migrateStub->createStub('destination_migration', [1], [], FALSE)->willReturn([2]);

    $configuration = [
      'no_stub' => FALSE,
      'migration' => 'destination_migration',
    ];

    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);

    $this->migrateStub->createStub('destination_migration', [1], [], FALSE)->willThrow(new $exception_class($exception_message));
    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $this->expectException($exception_class);
    $this->expectExceptionMessage($expected_message);
    $migration->transform(1, $this->migrateExecutable, $this->row, '');

  }

  /**
   * Provides data for testTransformWithStubbing().
   */
  public static function providerTestTransformWithStubbing(): array {
    return [
      [
        \Exception::class,
        'Oh noes!',
        'Exception was thrown while attempting to stub: Oh noes!',
      ],
      [
        MigrateSkipRowException::class,
        'Oh noes!',
        "Migration lookup for destination '' attempted to create a stub using migration destination_migration, which resulted in a row skip, with message 'Oh noes!'",
      ],
      [
        MigrateSkipRowException::class,
        '',
        "Migration lookup for destination '' attempted to create a stub using migration destination_migration, which resulted in a row skip",
      ],
    ];
  }

  /**
   * Tests that processing is skipped when the input value is invalid.
   *
   * @param mixed $value
   *   An invalid value.
   *
   * @dataProvider skipInvalidDataProvider
   */
  public function testSkipInvalid($value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);

    $configuration = [
      'migration' => 'foo',
    ];
    $migration_plugin->id()->willReturn(uniqid());
    $migration_plugin_manager->createInstances(['foo'])
      ->willReturn(['foo' => $migration_plugin->reveal()]);
    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $migration->transform($value, $this->migrateExecutable, $this->row, 'foo');
    $this->assertTrue($migration->isPipelineStopped());
    $this->assertNull($result);
  }

  /**
   * Provides data for the SkipInvalid test.
   *
   * @return array
   *   Empty values.
   */
  public static function skipInvalidDataProvider() {
    return [
      'Empty String' => [''],
      'Boolean False' => [FALSE],
      'Empty Array' => [[]],
      'Null' => [NULL],
    ];
  }

  /**
   * Tests that valid, but technically empty values are not skipped.
   *
   * @param mixed $value
   *   A valid value.
   *
   * @dataProvider noSkipValidDataProvider
   */
  public function testNoSkipValid($value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);
    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationIds([$value])->willReturn([]);
    $migration_plugin->getIdMap()->willReturn($id_map->reveal());

    $configuration = [
      'migration' => 'foo',
      'no_stub' => TRUE,
    ];
    $migration_plugin->id()->willReturn(uniqid());
    $migration_plugin_manager->createInstances(['foo'])
      ->willReturn(['foo' => $migration_plugin->reveal()]);
    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $lookup = $migration->transform($value, $this->migrateExecutable, $this->row, 'foo');

    /* We provided no values and asked for no stub, so we should get NULL. */
    $this->assertNull($lookup);
  }

  /**
   * Provides data for the NoSkipValid test.
   *
   * @return array
   *   Empty values.
   */
  public static function noSkipValidDataProvider() {
    return [
      'Integer Zero' => [0],
      'String Zero' => ['0'],
      'Float Zero' => [0.0],
    ];
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
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   *
   * @dataProvider successfulLookupDataProvider
   */
  public function testSuccessfulLookup(array $source_id_values, array $destination_id_values, $source_value, $expected_value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('foo', $source_id_values)->willReturn([$destination_id_values]);

    $configuration = [
      'migration' => 'foo',
    ];

    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $this->assertSame($expected_value, $migration->transform($source_value, $this->migrateExecutable, $this->row, 'foo'));
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   *   The data.
   */
  public static function successfulLookupDataProvider() {
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
      // Test 0 as data source ID.
      [
        // Source ID of the migration map.
        [0],
        // Destination ID of the migration map.
        [3],
        // Input value for the migration plugin.
        0,
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

  /**
   * Tests processing multiple source IDs.
   */
  public function testMultipleSourceIds(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('foo', ['id', 6])->willReturn([[2]]);
    $configuration = [
      'migration' => 'foo',
    ];
    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $migration->transform(['id', 6], $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

  /**
   * Tests processing multiple migrations and source IDs.
   */
  public function testMultipleMigrations(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('example', [1])->willReturn([[2]]);
    $this->migrateLookup->lookup('example', [2])->willReturn([]);
    $this->migrateLookup->lookup('foobar', [1, 2])->willReturn([]);
    $this->migrateLookup->lookup('foobar', [3, 4])->willReturn([[5]]);
    $configuration = [
      'migration' => ['foobar', 'example'],
      'source_ids' => [
        'foobar' => ['foo', 'bar'],
      ],
    ];
    $migration = MigrationLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());

    $row1 = $this->row;
    $row2 = clone $this->row;

    $row1->expects($this->any())
      ->method('getMultiple')
      ->willReturn([1, 2]);
    $result = $migration->transform([1], $this->migrateExecutable, $row1, '');
    $this->assertEquals(2, $result);

    $row2->expects($this->any())
      ->method('getMultiple')
      ->willReturn([3, 4]);
    $result = $migration->transform([2], $this->migrateExecutable, $row2, '');
    $this->assertEquals(5, $result);
  }

}
