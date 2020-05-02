<?php

namespace Drupal\Tests\system\Kernel\Scripts;

use Drupal\Core\Command\DbImportCommand;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test that the DbImportCommand works correctly.
 *
 * @group console
 */
class DbImportCommandTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'config',
    'dblog',
    'menu_link_content',
    'link',
    'block_content',
    'file',
    'user',
  ];

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
    'sessions',
    'url_alias',
    'user__roles',
    'users',
    'users_field_data',
    'watchdog',
  ];

  /**
   * Test the command directly.
   *
   * @requires extension pdo_sqlite
   */
  public function testDbImportCommand() {
    $connection_info = [
      'driver' => 'sqlite',
      'database' => ':memory:',
    ];
    Database::addConnectionInfo($this->databasePrefix, 'default', $connection_info);

    $command = new DbImportCommand();
    $command_tester = new CommandTester($command);
    $command_tester->execute([
      'script' => __DIR__ . '/../../../fixtures/update/drupal-8.bare.standard.php.gz',
      '--database' => $this->databasePrefix,
    ]);

    // The tables should now exist.
    $connection = Database::getConnection('default', $this->databasePrefix);
    foreach ($this->tables as $table) {
      $this->assertTrue($connection
        ->schema()
        ->tableExists($table), strtr('Table @table created by the database script.', ['@table' => $table]));
    }
  }

}
