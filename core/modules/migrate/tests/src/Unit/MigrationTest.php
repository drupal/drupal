<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrateSourcePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\Migration
 *
 * @group migrate
 */
class MigrationTest extends UnitTestCase {

  /**
   * Tests checking migration dependencies in the constructor.
   *
   * @param array $dependencies
   *   An array of migration dependencies.
   *
   * @covers ::__construct
   *
   * @dataProvider getInvalidMigrationDependenciesProvider
   *
   * @group legacy
   */
  public function testMigrationDependenciesInConstructor(array $dependencies): void {

    $configuration = ['migration_dependencies' => $dependencies];
    $plugin_id = 'test_migration';
    $migration_plugin_manager = $this->createMock('\Drupal\migrate\Plugin\MigrationPluginManagerInterface');
    $source_plugin_manager = $this->createMock('\Drupal\migrate\Plugin\MigratePluginManagerInterface');
    $process_plugin_manager = $this->createMock('\Drupal\migrate\Plugin\MigratePluginManagerInterface');
    $destination_plugin_manager = $this->createMock('\Drupal\migrate\Plugin\MigrateDestinationPluginManager');
    $id_map_plugin_manager = $this->createMock('\Drupal\migrate\Plugin\MigratePluginManagerInterface');

    $this->expectDeprecation("Invalid migration dependencies for {$plugin_id} is deprecated in drupal:10.1.0 and will cause an error in drupal:11.0.0. See https://www.drupal.org/node/3266691");
    new Migration($configuration, $plugin_id, [], $migration_plugin_manager, $source_plugin_manager, $process_plugin_manager, $destination_plugin_manager, $id_map_plugin_manager);
  }

  /**
   * Tests checking requirements for source plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForSourcePlugin(): void {
    $migration = new TestMigration();

    $source_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareSourceInterface');
    $source_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing source requirement', ['key' => 'value']));
    $destination_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Missing source requirement');
    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForDestinationPlugin(): void {
    $migration = new TestMigration();

    $source_plugin = $this->createMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $destination_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');
    $destination_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing destination requirement', ['key' => 'value']));

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Missing destination requirement');
    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForMigrations(): void {
    $migration = new TestMigration();

    // Setup source and destination plugins without any requirements.
    $source_plugin = $this->createMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $destination_plugin = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $plugin_manager = $this->createMock('Drupal\migrate\Plugin\MigrationPluginManagerInterface');
    $migration->setMigrationPluginManager($plugin_manager);

    // We setup the requirements that test_a doesn't exist and test_c is not
    // completed yet.
    $migration->setRequirements(['test_a', 'test_b', 'test_c', 'test_d']);

    $migration_b = $this->createMock(MigrationInterface::class);
    $migration_c = $this->createMock(MigrationInterface::class);
    $migration_d = $this->createMock(MigrationInterface::class);

    $migration_b->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(TRUE);
    $migration_c->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(FALSE);
    $migration_d->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(TRUE);

    $plugin_manager->expects($this->once())
      ->method('createInstances')
      ->with(['test_a', 'test_b', 'test_c', 'test_d'])
      ->willReturn(['test_b' => $migration_b, 'test_c' => $migration_c, 'test_d' => $migration_d]);

    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Missing migrations test_a, test_c');
    $migration->checkRequirements();
  }

  /**
   * Tests getting requirement list.
   *
   * @covers ::getRequirements
   */
  public function testGetMigrations(): void {
    $migration = new TestMigration();

    $requirements = ['test_a', 'test_b', 'test_c', 'test_d'];
    $migration->setRequirements($requirements);
    $this->assertEquals($requirements, $migration->getRequirements());
  }

  /**
   * Tests valid migration dependencies configuration returns expected values.
   *
   * @param array|null $source
   *   The migration dependencies configuration being tested.
   * @param array $expected_value
   *   The migration dependencies configuration array expected.
   *
   * @covers ::getMigrationDependencies
   * @dataProvider getValidMigrationDependenciesProvider
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testMigrationDependenciesWithValidConfig($source, array $expected_value): void {
    $migration = new TestMigration();

    // Set the plugin manager to support getMigrationDependencies().
    $plugin_manager = $this->createMock('Drupal\migrate\Plugin\MigrationPluginManagerInterface');
    $migration->setMigrationPluginManager($plugin_manager);
    $plugin_manager->expects($this->exactly(2))
      ->method('expandPluginIds')
      ->willReturnArgument(0);

    if (!is_null($source)) {
      $migration->set('migration_dependencies', $source);
    }
    $this->assertSame($migration->getMigrationDependencies(TRUE), $expected_value);
  }

  /**
   * Tests that getting migration dependencies fails with invalid configuration.
   *
   * @param array $dependencies
   *   An array of migration dependencies.
   *
   * @covers ::getMigrationDependencies
   *
   * @dataProvider getInvalidMigrationDependenciesProvider
   *
   * @group legacy
   */
  public function testMigrationDependenciesWithInvalidConfig(array $dependencies): void {
    $migration = new TestMigration();

    // Set the plugin ID to test the returned message.
    $plugin_id = 'test_migration';
    $migration->setPluginId($plugin_id);

    // Migration dependencies expects ['optional' => []] or ['required' => []]].
    $this->expectDeprecation("Invalid migration dependencies for {$plugin_id} is deprecated in drupal:10.1.0 and will cause an error in drupal:11.0.0. See https://www.drupal.org/node/3266691");
    $migration->set('migration_dependencies', $dependencies);

    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage("Invalid migration dependencies configuration for migration {$plugin_id}");
    $migration->getMigrationDependencies(TRUE);
  }

  /**
   * Provides data for valid migration configuration test.
   */
  public static function getValidMigrationDependenciesProvider() {
    return [
      [
        'source' => NULL,
        'expected_value' => ['required' => [], 'optional' => []],
      ],
      [
        'source' => [],
        'expected_value' => ['required' => [], 'optional' => []],
      ],
      [
        'source' => ['required' => ['test_migration']],
        'expected_value' => ['required' => ['test_migration'], 'optional' => []],
      ],
      [
        'source' => ['optional' => ['test_migration']],
        'expected_value' => ['optional' => ['test_migration'], 'required' => []],
      ],
      [
        'source' => ['required' => ['req_test_migration'], 'optional' => ['opt_test_migration']],
        'expected_value' => ['required' => ['req_test_migration'], 'optional' => ['opt_test_migration']],
      ],
    ];
  }

  /**
   * Provides invalid migration dependencies.
   */
  public static function getInvalidMigrationDependenciesProvider() {
    return [
      'invalid key' => [
        'dependencies' => ['bogus' => []],
      ],
      'required not array' => [
        'dependencies' => ['required' => 17, 'optional' => []],
      ],
      'optional not array' => [
        'dependencies' => ['required' => [], 'optional' => 17],
      ],
    ];
  }

  /**
   * Test trackLastImported deprecation message in Migration constructor.
   *
   * @group legacy
   */
  public function testTrackLastImportedDeprecation(): void {
    $this->expectDeprecation("The key 'trackLastImported' is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3282894");
    $migration_plugin_manager = $this->createMock(MigrationPluginManagerInterface::class);
    $source_plugin_manager = $this->createMock(MigrateSourcePluginManager::class);
    $process_Plugin_manager = $this->createMock(MigratePluginManagerInterface::class);
    $destination_plugin_manager = $this->createMock(MigrateDestinationPluginManager::class);
    $id_map_plugin_manager = $this->createMock(MigratePluginManagerInterface::class);
    new Migration([], 'test', ['trackLastImported' => TRUE], $migration_plugin_manager, $source_plugin_manager, $process_Plugin_manager, $destination_plugin_manager, $id_map_plugin_manager);
  }

  /**
   * Tests deprecation of getMigrationDependencies(FALSE).
   *
   * @group legacy
   */
  public function testGetMigrationDependencies(): void {
    $migration = new TestMigration();
    $this->expectDeprecation('Calling Migration::getMigrationDependencies() without expanding the plugin IDs is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. In most cases, use getMigrationDependencies(TRUE). See https://www.drupal.org/node/3266691');
    $migration->getMigrationDependencies();
  }

}

/**
 * Defines the TestMigration class.
 */
class TestMigration extends Migration {

  /**
   * Constructs an instance of TestMigration object.
   */
  public function __construct() {
    $this->migration_dependencies = ($this->migration_dependencies ?: []) + ['required' => [], 'optional' => []];
  }

  /**
   * Sets the migration ID (machine name).
   *
   * @param string $plugin_id
   *   The plugin_id of the plugin instance.
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * Sets the requirements values.
   *
   * @param array $requirements
   *   The array of requirement values.
   */
  public function setRequirements(array $requirements) {
    $this->requirements = $requirements;
  }

  /**
   * Sets the source Plugin.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source_plugin
   *   The source Plugin.
   */
  public function setSourcePlugin(MigrateSourceInterface $source_plugin) {
    $this->sourcePlugin = $source_plugin;
  }

  /**
   * Sets the destination Plugin.
   *
   * @param \Drupal\migrate\Plugin\MigrateDestinationInterface $destination_plugin
   *   The destination Plugin.
   */
  public function setDestinationPlugin(MigrateDestinationInterface $destination_plugin) {
    $this->destinationPlugin = $destination_plugin;
  }

  /**
   * Sets the plugin manager service.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $plugin_manager
   *   The plugin manager service.
   */
  public function setMigrationPluginManager(MigrationPluginManagerInterface $plugin_manager) {
    $this->migrationPluginManager = $plugin_manager;
  }

}

/**
 * Defines the RequirementsAwareSourceInterface.
 */
interface RequirementsAwareSourceInterface extends MigrateSourceInterface, RequirementsInterface {}

/**
 * Defines the RequirementsAwareDestinationInterface.
 */
interface RequirementsAwareDestinationInterface extends MigrateDestinationInterface, RequirementsInterface {}
