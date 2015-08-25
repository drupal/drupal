<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Kernel\Scripts\DbImportCommandTest.
 */

namespace Drupal\Tests\system\Kernel\Scripts;

use Drupal\Core\Command\DbImportCommand;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test that the DbToolsApplication works correctly.
 *
 * The way console application's run it is impossible to test. For now we only
 * test that we are registering the correct commands.
 */
class DbImportCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'config', 'dblog', 'menu_link_content', 'link', 'block_content', 'file', 'user'];

  /**
   * Test data to write into config.
   *
   * @var array
   */
  protected $data;

  /**
   * Flag to skip these tests, which are database-backend dependent (MySQL).
   *
   * @see \Drupal\Core\Command\DbDumpCommand
   *
   * @var bool
   */
  protected $skipTests = FALSE;

  /**
   * An array of original table schemas.
   *
   * @var array
   */
  protected $originalTableSchemas = [];

  /**
   * An array of original table indexes (including primary and unique keys).
   *
   * @var array
   */
  protected $originalTableIndexes = [];

  /**
   * Tables that should be part of the exported script.
   *
   * @var array
   */
  protected $tables = [
    'block_content',
    'block_content_field_data',
    'block_content_field_revision',
    'block_content_revision',
    'cachetags',
    'config',
    'cache_discovery',
    'cache_bootstrap',
    'file_managed',
    'key_value_expire',
    'menu_link_content',
    'menu_link_content_data',
    'semaphore',
    'sessions',
    'url_alias',
    'user__roles',
    'users',
    'users_field_data',
    'watchdog',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Determine what database backend is running, and set the skip flag.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->markTestSkipped("Skipping test since the DbDumpCommand is currently only compatible with MySql");
    }
  }

  /**
   * Test the command directly.
   */
  public function testDbImportCommand() {

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $this->container->get('database');
    // Drop tables to avoid conflicts.
    foreach ($this->tables as $table) {
      $connection->schema()->dropTable($table);
    }

    $command = new DbImportCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute(['script' => __DIR__ . '/../../../fixtures/update/drupal-8.bare.standard.php.gz']);

    // The tables should now exist.
    foreach ($this->tables as $table) {
      $this->assertTrue($connection
        ->schema()
        ->tableExists($table), strtr('Table @table created by the database script.', ['@table' => $table]));
    }
  }

  /**
   * Helper function to get a simplified schema for a given table.
   *
   * @param string $table
   *
   * @return array
   *   Array keyed by field name, with the values being the field type.
   */
  protected function getTableSchema($table) {
    // Verify the field type on the data column in the cache table.
    // @todo this is MySQL specific.
    $query = $this->container->get('database')
      ->query("SHOW COLUMNS FROM {" . $table . "}");
    $definition = [];
    while ($row = $query->fetchAssoc()) {
      $definition[$row['Field']] = $row['Type'];
    }
    return $definition;
  }

  /**
   * Returns indexes for a given table.
   *
   * @param string $table
   *   The table to find indexes for.
   *
   * @return array
   *   The 'primary key', 'unique keys', and 'indexes' portion of the Drupal
   *   table schema.
   */
  protected function getTableIndexes($table) {
    $query = $this->container->get('database')
      ->query("SHOW INDEX FROM {" . $table . "}");
    $definition = [];
    while ($row = $query->fetchAssoc()) {
      $index_name = $row['Key_name'];
      $column = $row['Column_name'];
      // Key the arrays by the index sequence for proper ordering (start at 0).
      $order = $row['Seq_in_index'] - 1;

      // If specified, add length to the index.
      if ($row['Sub_part']) {
        $column = [$column, $row['Sub_part']];
      }

      if ($index_name === 'PRIMARY') {
        $definition['primary key'][$order] = $column;
      }
      elseif ($row['Non_unique'] == 0) {
        $definition['unique keys'][$index_name][$order] = $column;
      }
      else {
        $definition['indexes'][$index_name][$order] = $column;
      }
    }
    return $definition;
  }

}
