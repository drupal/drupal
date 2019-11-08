<?php

namespace Drupal\KernelTests\Core\Command;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Command\DbDumpApplication;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Drupal\user\Entity\User;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests for the database dump commands.
 *
 * @group Update
 */
class DbDumpTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'config', 'dblog', 'menu_link_content', 'link', 'block_content', 'file', 'path_alias', 'user'];

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
  protected $tables;

  /**
   * {@inheritdoc}
   *
   * Register a database cache backend rather than memory-based.
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('cache_factory', 'Drupal\Core\Cache\DatabaseBackendFactory')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('cache_tags.invalidator.checksum'))
      ->addArgument(new Reference('settings'));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Determine what database backend is running, and set the skip flag.
    $this->skipTests = Database::getConnection()->databaseType() !== 'mysql';

    // Create some schemas so our export contains tables.
    $this->installSchema('system', [
      'key_value_expire',
      'sessions',
    ]);
    $this->installSchema('dblog', ['watchdog']);
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('path_alias');
    $this->installSchema('system', 'sequences');

    // Place some sample config to test for in the export.
    $this->data = [
      'foo' => $this->randomMachineName(),
      'bar' => $this->randomMachineName(),
    ];
    $storage = new DatabaseStorage(Database::getConnection(), 'config');
    $storage->write('test_config', $this->data);

    // Create user account with some potential syntax issues.
    $account = User::create(['mail' => 'q\'uote$dollar@example.com', 'name' => '$dollar']);
    $account->save();

    // Create a path alias.
    $this->createPathAlias('/user/' . $account->id(), '/user/example');

    // Create a cache table (this will create 'cache_discovery').
    \Drupal::cache('discovery')->set('test', $this->data);

    // These are all the tables that should now be in place.
    $this->tables = [
      'block_content',
      'block_content_field_data',
      'block_content_field_revision',
      'block_content_revision',
      'cachetags',
      'config',
      'cache_bootstrap',
      'cache_config',
      'cache_data',
      'cache_discovery',
      'cache_entity',
      'file_managed',
      'key_value_expire',
      'menu_link_content',
      'menu_link_content_data',
      'menu_link_content_revision',
      'menu_link_content_field_revision',
      'sequences',
      'sessions',
      'path_alias',
      'path_alias_revision',
      'user__roles',
      'users',
      'users_field_data',
      'watchdog',
    ];
  }

  /**
   * Test the command directly.
   */
  public function testDbDumpCommand() {
    if ($this->skipTests) {
      $this->pass("Skipping test since the DbDumpCommand is currently only compatible with MySql");
      return;
    }

    $application = new DbDumpApplication();
    $command = $application->find('dump-database-d8-mysql');
    $command_tester = new CommandTester($command);
    $command_tester->execute([]);

    // Tables that are schema-only should not have data exported.
    $pattern = preg_quote("\$connection->insert('sessions')");
    $this->assertNotRegExp('/' . $pattern . '/', $command_tester->getDisplay(), 'Tables defined as schema-only do not have data exported to the script.');

    // Table data is exported.
    $pattern = preg_quote("\$connection->insert('config')");
    $this->assertRegExp('/' . $pattern . '/', $command_tester->getDisplay(), 'Table data is properly exported to the script.');

    // The test data are in the dump (serialized).
    $pattern = preg_quote(serialize($this->data));
    $this->assertRegExp('/' . $pattern . '/', $command_tester->getDisplay(), 'Generated data is found in the exported script.');

    // Check that the user account name and email address was properly escaped.
    $pattern = preg_quote('"q\'uote\$dollar@example.com"');
    $this->assertRegExp('/' . $pattern . '/', $command_tester->getDisplay(), 'The user account email address was properly escaped in the exported script.');
    $pattern = preg_quote('\'$dollar\'');
    $this->assertRegExp('/' . $pattern . '/', $command_tester->getDisplay(), 'The user account name was properly escaped in the exported script.');
  }

  /**
   * Test loading the script back into the database.
   */
  public function testScriptLoad() {
    if ($this->skipTests) {
      $this->pass("Skipping test since the DbDumpCommand is currently only compatible with MySql");
      return;
    }

    // Generate the script.
    $application = new DbDumpApplication();
    $command = $application->find('dump-database-d8-mysql');
    $command_tester = new CommandTester($command);
    $command_tester->execute([]);
    $script = $command_tester->getDisplay();

    // Store original schemas and drop tables to avoid errors.
    $connection = Database::getConnection();
    $schema = $connection->schema();
    foreach ($this->tables as $table) {
      $this->originalTableSchemas[$table] = $this->getTableSchema($table);
      $this->originalTableIndexes[$table] = $this->getTableIndexes($table);
      $schema->dropTable($table);
    }

    // This will load the data.
    $file = sys_get_temp_dir() . '/' . $this->randomMachineName();
    file_put_contents($file, $script);
    require_once $file;

    // The tables should now exist and the schemas should match the originals.
    foreach ($this->tables as $table) {
      $this->assertTrue($schema
        ->tableExists($table), new FormattableMarkup('Table @table created by the database script.', ['@table' => $table]));
      $this->assertSame($this->originalTableSchemas[$table], $this->getTableSchema($table), new FormattableMarkup('The schema for @table was properly restored.', ['@table' => $table]));
      $this->assertSame($this->originalTableIndexes[$table], $this->getTableIndexes($table), new FormattableMarkup('The indexes for @table were properly restored.', ['@table' => $table]));
    }

    // Ensure the test config has been replaced.
    $config = unserialize($connection->query("SELECT data FROM {config} WHERE name = 'test_config'")->fetchField());
    $this->assertIdentical($config, $this->data, 'Script has properly restored the config table data.');

    // Ensure the cache data was not exported.
    $this->assertFalse(\Drupal::cache('discovery')
      ->get('test'), 'Cache data was not exported to the script.');
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
    $query = Database::getConnection()->query("SHOW COLUMNS FROM {" . $table . "}");
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
    $query = Database::getConnection()->query("SHOW INDEX FROM {" . $table . "}");
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
