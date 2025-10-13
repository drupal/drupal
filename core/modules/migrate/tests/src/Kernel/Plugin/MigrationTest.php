<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\Migration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration plugin.
 */
#[CoversClass(Migration::class)]
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class MigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_expected_migrations_test'];

  /**
   * Tests Migration::getProcessPlugins()
   *
   * @legacy-covers ::getProcessPlugins
   */
  public function testGetProcessPlugins(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);
    $this->assertEquals([], $migration->getProcessPlugins([]));
  }

  /**
   * Tests Migration::getProcessPlugins() throws an exception.
   *
   * @legacy-covers ::getProcessPlugins
   */
  public function testGetProcessPluginsException(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Invalid process configuration for foobar');
    $migration->getProcessPlugins(['foobar' => ['plugin' => 'get']]);
  }

  /**
   * Tests Migration::getProcessPlugins()
   *
   * @param array $process
   *   The migration process pipeline.
   *
   * @legacy-covers ::getProcessPlugins
   */
  #[DataProvider('getProcessPluginsExceptionMessageProvider')]
  public function testGetProcessPluginsExceptionMessage(array $process): void {
    // Test with an invalid process pipeline.
    $plugin_definition = [
      'id' => 'foo',
      'process' => $process,
    ];
    $destination = array_key_first(($process));

    $migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($plugin_definition);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Invalid process for destination '$destination' in migration 'foo'");
    $migration->getProcessPlugins();
  }

  /**
   * Provides data for testing invalid process pipeline.
   */
  public static function getProcessPluginsExceptionMessageProvider(): \Generator {
    yield 'null' => ['process' => ['dest' => NULL]];
    yield 'boolean' => ['process' => ['dest' => TRUE]];
    yield 'integer' => ['process' => ['dest' => 2370]];
    yield 'float' => ['process' => ['dest' => 1.61]];
  }

  /**
   * Tests Migration::getMigrationDependencies()
   *
   * @legacy-covers ::getMigrationDependencies
   */
  public function testGetMigrationDependencies(): void {
    $plugin_manager = \Drupal::service('plugin.manager.migration');
    $plugin_definition = [
      'id' => 'foo',
      'deriver' => 'fooDeriver',
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
        'f7' => [
          'plugin' => 'migration_lookup',
          'migration' => 'foo',
        ],
      ],
    ];
    $migration = $plugin_manager->createStubMigration($plugin_definition);
    $this->assertSame(['required' => [], 'optional' => ['m1', 'm2', 'm3', 'm4', 'm5']], $migration->getMigrationDependencies());
  }

  /**
   * Tests Migration::getDestinationIds()
   *
   * @legacy-covers ::getDestinationIds
   */
  public function testGetDestinationIds(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration(['destinationIds' => ['foo' => 'bar']]);
    $destination_ids = $migration->getDestinationIds();
    $this->assertNotEmpty($destination_ids, 'Destination ids are not empty');
    $this->assertEquals(['foo' => 'bar'], $destination_ids, 'Destination ids match the expected values.');
  }

  /**
   * Tests Migration::getDestinationPlugin()
   *
   * @legacy-covers ::getDestinationPlugin
   */
  public function testGetDestinationPlugin(): void {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration(['destination' => ['no_stub' => TRUE]]);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("Stub requested but not made because no_stub configuration is set.");
    $migration->getDestinationPlugin(TRUE);
  }

}
