<?php

namespace Drupal\Tests\migrate\Functional\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore destid

/**
 * Tests the 'download' process plugin.
 *
 * @group migrate
 */
class DownloadFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that an exception is thrown bu migration continues with the next row.
   */
  public function testExceptionThrow() {
    $invalid_url = "{$this->baseUrl}/not-existent-404";
    $valid_url = "{$this->baseUrl}/core/misc/favicon.ico";

    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['url' => $invalid_url, 'uri' => 'public://first.txt'],
          ['url' => $valid_url, 'uri' => 'public://second.ico'],
        ],
        'ids' => [
          'url' => ['type' => 'string'],
        ],
      ],
      'process' => [
        'uri' => [
          'plugin' => 'download',
          'source' => ['url', 'uri'],
        ],
      ],
      'destination' => [
        'plugin' => 'entity:file',
      ],
    ];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration);
    $result = $executable->import();

    // Check that the migration has completed.
    $this->assertEquals(MigrationInterface::RESULT_COMPLETED, $result);

    /** @var \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map_plugin */
    $id_map_plugin = $migration->getIdMap();

    // Check that the first row was marked as failed in the id map table.
    $map_row = $id_map_plugin->getRowBySource(['url' => $invalid_url]);
    $this->assertEquals(MigrateIdMapInterface::STATUS_FAILED, $map_row['source_row_status']);
    $this->assertNull($map_row['destid1']);

    // Check that a message with the thrown exception has been logged.
    $messages = $id_map_plugin->getMessages(['url' => $invalid_url])->fetchAll();
    $this->assertCount(1, $messages);
    $message = reset($messages);

    // Assert critical parts of the error message, but not the exact message,
    // since it depends on Guzzle's internal implementation of PSR-7.
    $id = $migration->getPluginId();
    $this->assertStringContainsString("$id:uri:download:", $message->message);
    $this->assertStringContainsString($invalid_url, $message->message);
    $this->assertEquals(MigrationInterface::MESSAGE_ERROR, $message->level);

    // Check that the second row was migrated successfully.
    $map_row = $id_map_plugin->getRowBySource(['url' => $valid_url]);
    $this->assertEquals(MigrateIdMapInterface::STATUS_IMPORTED, $map_row['source_row_status']);
    $this->assertEquals(1, $map_row['destid1']);
  }

}
