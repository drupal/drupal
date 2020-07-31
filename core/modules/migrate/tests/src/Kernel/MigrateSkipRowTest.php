<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
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
          ['id' => '2', 'data' => 'skip_and_do_not_record'],
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

    $executable = new MigrateExecutable($migration);
    $result = $executable->import();
    $this->assertEqual($result, MigrationInterface::RESULT_COMPLETED);

    /** @var \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map_plugin */
    $id_map_plugin = $migration->getIdMap();
    // The first row is recorded in the map as ignored.
    $map_row = $id_map_plugin->getRowBySource(['id' => 1]);
    $this->assertEqual(MigrateIdMapInterface::STATUS_IGNORED, $map_row['source_row_status']);
    // Check that no message has been logged for the first exception.
    $messages = $id_map_plugin->getMessages(['id' => 1])->fetchAll();
    $this->assertEmpty($messages);

    // The second row is not recorded in the map.
    $map_row = $id_map_plugin->getRowBySource(['id' => 2]);
    $this->assertFalse($map_row);
    // Check that the correct message has been logged for the second exception.
    $messages = $id_map_plugin->getMessages(['id' => 2])->fetchAll();
    $this->assertCount(1, $messages);
    $message = reset($messages);
    $this->assertEquals('skip_and_do_not_record message', $message->message);
    $this->assertEquals(MigrationInterface::MESSAGE_INFORMATIONAL, $message->level);

    // Insert a custom processor in the process flow.
    $definition['process']['value'] = [
      'source' => 'data',
      'plugin' => 'test_skip_row_process',
    ];
    // Change data to avoid triggering again hook_migrate_prepare_row().
    $definition['source']['data_rows'] = [
      ['id' => '1', 'data' => 'skip_and_record (use plugin)'],
      ['id' => '2', 'data' => 'skip_and_do_not_record (use plugin)'],
    ];
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration);
    $result = $executable->import();
    $this->assertEquals($result, MigrationInterface::RESULT_COMPLETED);

    $id_map_plugin = $migration->getIdMap();

    // The first row is recorded in the map as ignored.
    $map_row = $id_map_plugin->getRowBySource(['id' => 1]);
    $this->assertEquals(MigrateIdMapInterface::STATUS_IGNORED, $map_row['source_row_status']);
    // The second row is not recorded in the map.
    $map_row = $id_map_plugin->getRowBySource(['id' => 2]);
    $this->assertFalse($map_row);
  }

}
