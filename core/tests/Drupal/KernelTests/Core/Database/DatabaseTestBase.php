<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for databases database tests.
 *
 * Because all database tests share the same test data, we can centralize that
 * here.
 */
abstract class DatabaseTestBase extends KernelTestBase {

  use DatabaseTestSchemaDataTrait;
  use DatabaseTestSchemaInstallTrait;

  protected static $modules = ['database_test'];

  /**
   * The database connection for testing.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = Database::getConnection();
    $this->installSampleSchema();
    $this->addSampleData();
  }

  /**
   * Sets up tables for NULL handling.
   */
  public function ensureSampleDataNull() {
    $this->connection->insert('test_null')
      ->fields(['name', 'age'])
      ->values([
        'name' => 'Kermit',
        'age' => 25,
      ])
      ->values([
        'name' => 'Fozzie',
        'age' => NULL,
      ])
      ->values([
        'name' => 'Gonzo',
        'age' => 27,
      ])
      ->execute();
  }

}
