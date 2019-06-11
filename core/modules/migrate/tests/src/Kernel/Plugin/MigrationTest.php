<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;

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
   * Tests Migration::getProcessPlugins() throws an exception.
   *
   * @covers ::getProcessPlugins
   */
  public function testGetProcessPluginsException() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Invalid process configuration for foobar');
    $migration->getProcessPlugins(['foobar' => ['plugin' => 'get']]);
  }

  /**
   * Tests Migration::getMigrationDependencies()
   *
   * @covers ::getMigrationDependencies
   */
  public function testGetMigrationDependencies() {
    $plugin_manager = \Drupal::service('plugin.manager.migration');
    $plugin_definition = [
      'process' => [
        'f1' => 'bar',
        'f2' => [
          'plugin' => 'migration',
          'migration' => 'm1',
        ],
        'f3' => [
          'plugin' => 'sub_process',
          'process' => [
            'target_id' => [
              'plugin' => 'migration',
              'migration' => 'm2',
            ],
          ],
        ],
        'f4' => [
          'plugin' => 'migration_lookup',
          'migration' => 'm3',
        ],
        'f5' => [
          'plugin' => 'sub_process',
          'process' => [
            'target_id' => [
              'plugin' => 'migration_lookup',
              'migration' => 'm4',
            ],
          ],
        ],
        'f6' => [
          'plugin' => 'iterator',
          'process' => [
            'target_id' => [
              'plugin' => 'migration_lookup',
              'migration' => 'm5',
            ],
          ],
        ],
      ],
    ];
    $migration = $plugin_manager->createStubMigration($plugin_definition);
    $this->assertSame(['required' => [], 'optional' => ['m1', 'm2', 'm3', 'm4', 'm5']], $migration->getMigrationDependencies());
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

  /**
   * Tests Migration::getDestinationPlugin()
   *
   * @covers ::getDestinationPlugin
   */
  public function testGetDestinationPlugin() {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration(['destination' => ['no_stub' => TRUE]]);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("Stub requested but not made because no_stub configuration is set.");
    $migration->getDestinationPlugin(TRUE);
  }

}
