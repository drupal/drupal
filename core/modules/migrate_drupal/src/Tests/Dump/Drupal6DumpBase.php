<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Base class for the dump classes.
 */
class Drupal6DumpBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An array of tables that have already been created.
   *
   * @var array
   */
  protected $migrateTables;

  /**
   * Sample database schema and values.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Create a new table from a Drupal table definition if it doesn't exist.
   *
   * @param string $name
   *   The name of the table to create.
   * @param array $table
   *   A Schema API table definition array.
   */
  protected function createTable($name, array $table) {
    // This must be on the database connection to be shared among classes.
    if (empty($this->database->migrateTables[$name])) {
      $this->database->migrateTables[$name] = TRUE;
      $this->database->schema()->createTable($name, $table);
    }
  }

}
