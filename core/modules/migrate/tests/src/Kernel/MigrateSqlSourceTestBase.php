<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\Core\Database\Driver\sqlite\Connection;

/**
 * Base class for tests of Migrate source plugins that use a database.
 */
abstract class MigrateSqlSourceTestBase extends MigrateSourceTestBase {

  /**
   * Builds an in-memory SQLite database from a set of source data.
   *
   * @param array $source_data
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   *
   * @return \Drupal\Core\Database\Driver\sqlite\Connection
   *   The SQLite database connection.
   */
  protected function getDatabase(array $source_data) {
    // Create an in-memory SQLite database. Plugins can interact with it like
    // any other database, and it will cease to exist when the connection is
    // closed.
    $connection_options = ['database' => ':memory:'];
    $pdo = Connection::open($connection_options);
    $connection = new Connection($pdo, $connection_options);

    // Create the tables and fill them with data.
    foreach ($source_data as $table => $rows) {
      // Use the biggest row to build the table schema.
      $counts = array_map('count', $rows);
      asort($counts);
      end($counts);
      $pilot = $rows[key($counts)];

      $connection->schema()
        ->createTable($table, [
          // SQLite uses loose affinity typing, so it's OK for every field to
          // be a text field.
          'fields' => array_map(function() { return ['type' => 'text']; }, $pilot),
        ]);

      $fields = array_keys($pilot);
      $insert = $connection->insert($table)->fields($fields);
      array_walk($rows, [$insert, 'values']);
      $insert->execute();
    }

    return $connection;
  }

  /**
   * Tests the source plugin against a particular data set.
   *
   * @param array $source_data
   *   The source data that the plugin will read. See getDatabase() for the
   *   expected format.
   * @param array $expected_data
   *   The result rows the plugin is expected to return.
   * @param int $expected_count
   *   (optional) How many rows the source plugin is expected to return.
   * @param array $configuration
   *   (optional) Configuration for the source plugin.
   * @param mixed $high_water
   *   (optional) The value of the high water field.
   *
   * @dataProvider providerSource
   *
   * @requires extension pdo_sqlite
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL) {
    $plugin = $this->getPlugin($configuration);

    // Since we don't yet inject the database connection, we need to use a
    // reflection hack to set it in the plugin instance.
    $reflector = new \ReflectionObject($plugin);
    $property = $reflector->getProperty('database');
    $property->setAccessible(TRUE);
    $property->setValue($plugin, $this->getDatabase($source_data));

    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water);
  }

}
