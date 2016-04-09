<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Tests row skips triggered during hook_migrate_prepare_row().
 *
 * @group migrate
 */
class MigrateSkipRowTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['migrate', 'migrate_prepare_row_test'];

  /**
   * Tests migration interruptions.
   */
  public function testPrepareRowSkip() {
    // Run a simple little migration with two data rows which should be skipped
    // in different ways.
    $definition = [
      'migration_tags' => ['prepare_row test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['id' => '1', 'data' => 'skip_and_record'],
          ['id' => '2', 'data' => 'skip_and_dont_record'],
        ],
        'ids' => [
          'id' => ['type' => 'string'],
        ],
      ],
      'process' => ['value' => 'data'],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'migrate_test.settings',
      ],
      'load' => ['plugin' => 'null'],
    ];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $result = $executable->import();
    $this->assertEqual($result, MigrationInterface::RESULT_COMPLETED);

    $id_map_plugin = $migration->getIdMap();
    // The first row is recorded in the map as ignored.
    $map_row = $id_map_plugin->getRowBySource(['id' => 1]);
    $this->assertEqual(MigrateIdMapInterface::STATUS_IGNORED, $map_row['source_row_status']);
    // The second row is not recorded in the map.
    $map_row = $id_map_plugin->getRowBySource(['id' => 2]);
    $this->assertFalse($map_row);

  }

}
