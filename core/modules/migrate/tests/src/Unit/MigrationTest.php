<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Entity\Migration
 * @group Migration
 */
class MigrationTest extends UnitTestCase {

  /**
   * Tests checking requirements for source plugins.
   *
   * @covers ::checkRequirements
   *
   * @expectedException \Drupal\migrate\Exception\RequirementsException
   * @expectedExceptionMessage Missing source requirement
   */
  public function testRequirementsForSourcePlugin() {
    $migration = new TestMigration();

    $source_plugin = $this->getMock('Drupal\Tests\migrate\Unit\RequirementsAwareSourceInterface');
    $source_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing source requirement', ['key' => 'value']));
    $destination_plugin = $this->getMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   *
   * @expectedException \Drupal\migrate\Exception\RequirementsException
   * @expectedExceptionMessage Missing destination requirement
   */
  public function testRequirementsForDestinationPlugin() {
    $migration = new TestMigration();

    $source_plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $destination_plugin = $this->getMock('Drupal\Tests\migrate\Unit\RequirementsAwareDestinationInterface');
    $destination_plugin->expects($this->once())
      ->method('checkRequirements')
      ->willThrowException(new RequirementsException('Missing destination requirement', ['key' => 'value']));

    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $migration->checkRequirements();
  }

  /**
   * Tests checking requirements for destination plugins.
   *
   * @covers ::checkRequirements
   *
   * @expectedException \Drupal\migrate\Exception\RequirementsException
   * @expectedExceptionMessage Missing migrations test_a, test_c
   */
  public function testRequirementsForMigrations() {
    $migration = new TestMigration();

    // Setup source and destination plugins without any requirements.
    $source_plugin = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $destination_plugin = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $migration->setSourcePlugin($source_plugin);
    $migration->setDestinationPlugin($destination_plugin);

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $migration->setEntityManager($entity_manager);

    // We setup the requirements that test_a doesn't exist and test_c is not
    // completed yet.
    $migration->setRequirements(['test_a', 'test_b', 'test_c', 'test_d']);

    $migration_b = $this->getMock('Drupal\migrate\Entity\MigrationInterface');
    $migration_c = $this->getMock('Drupal\migrate\Entity\MigrationInterface');
    $migration_d = $this->getMock('Drupal\migrate\Entity\MigrationInterface');

    $migration_b->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(TRUE);
    $migration_c->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(FALSE);
    $migration_d->expects($this->once())
      ->method('allRowsProcessed')
      ->willReturn(TRUE);

    $migration_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $migration_storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test_a', 'test_b', 'test_c', 'test_d'])
      ->willReturn(['test_b' => $migration_b, 'test_c' => $migration_c, 'test_d' => $migration_d]);
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('migration')
      ->willReturn($migration_storage);

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
   * Sets the entity manager service.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
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
