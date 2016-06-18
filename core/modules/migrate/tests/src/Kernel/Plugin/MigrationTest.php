<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the migration plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\Migration
 * @group migrate
 */
class MigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * Tests Migration::getProcessPlugins()
   *
   * @covers ::getProcessPlugins
   */
  public function testGetProcessPlugins() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);
    $this->assertEquals([], $migration->getProcessPlugins([]));
  }

  /**
   * Tests Migration::getMigrationDependencies()
   *
   * @covers ::getMigrationDependencies
   */
  public function testGetMigrationDependencies() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([
        'migration_dependencies' => NULL
    ]);
    $this->assertNotEmpty($migration->getMigrationDependencies(), 'Migration dependencies is not empty');
  }

  /**
   * Tests Migration::getDestinationIds()
   *
   * @covers ::getDestinationIds
   */
  public function testGetDestinationIds() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration(['destinationIds' => ['foo' => 'bar']]);
    $destination_ids = $migration->getDestinationIds();
    $this->assertNotEmpty($destination_ids, 'Destination ids are not empty');
    $this->assertEquals(['foo' => 'bar'], $destination_ids, 'Destination ids match the expected values.');
  }

  /**
   * Tests Migration::getTrackLastImported()
   *
   * @covers ::getTrackLastImported
   * @covers ::isTrackLastImported
   */
  public function testGetTrackLastImported() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);
    $migration->setTrackLastImported(TRUE);
    $this->assertEquals(TRUE, $migration->getTrackLastImported());
    $this->assertEquals(TRUE, $migration->isTrackLastImported());
  }

}
