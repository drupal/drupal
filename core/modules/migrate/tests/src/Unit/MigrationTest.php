<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit;

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
   * Tests checking requirements for source plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForSourcePlugin() {
    $migration = new TestMigration();

    $source_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareSourceInterface');
    $source_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing source requirement', ['key' => 'value']));
    $destination_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $this->setExpectedException(RequirementsException::class, 'Missing source requirement');
    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForDestinationPlugin() {
    $migration = new TestMigration();

    $source_plugin = $this->createMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $destination_plugin = $this->createMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');
    $destination_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing destination requirement', ['key' => 'value']));

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $this->setExpectedException(RequirementsException::class, 'Missing destination requirement');
    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   */
  public function testRequirementsForMigrations() {
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

    $this->setExpectedException(RequirementsException::class, 'Missing migrations test_a, test_c');
    $migration->checkRequirements();
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
