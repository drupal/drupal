<?php

/**
 * @file
 * Contains \Drupal\Core\Command\DbDumpCommand.
 */

namespace Drupal\Core\Command;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command to dump the current database to a script.
 *
 * This script exports all tables in the given database, and all data (except
 * for tables denoted as schema-only). The resulting script creates the tables
 * and populates them with the exported data.
 *
 * @todo This command is currently only compatible with MySQL. Making it
 *   backend-agnostic will require \Drupal\Core\Database\Schema support the
 *   ability to retrieve table schema information. Note that using a raw
 *   SQL dump file here (eg, generated from mysqldump or pg_dump) is not an
 *   option since these tend to still be database-backend specific.
 * @see https://www.drupal.org/node/301038
 *
 * @see \Drupal\Core\Command\DbDumpApplication
 */
class DbDumpCommand extends Command {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array of table patterns to exclude completely.
   *
   * This excludes any lingering simpletest tables generated during test runs.
   *
   * @var array
   */
  protected $excludeTables = ['simpletest.+'];

  /**
   * Table patterns for which to only dump the schema, no data.
   *
   * @var array
   */
  protected $schemaOnly = ['cache.*', 'sessions', 'watchdog'];

  /**
   * Construct the database dump command.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use.
   */
  function __construct(Connection $connection, ModuleHandlerInterface $module_handler) {
    // Check this is MySQL.
    if ($connection->databaseType() !== 'mysql') {
      throw new \RuntimeException('This script can only be used with MySQL database backends.');
    }

    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('dump-database-d8-mysql')
      ->setDescription('Dump the current database to a generation script');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // If not explicitly set, disable ANSI which will break generated php.
    if ($input->hasParameterOption(['--ansi']) !== TRUE) {
      $output->setDecorated(FALSE);
    }

    $output->writeln($this->generateScript(), OutputInterface::OUTPUT_RAW);
  }

  /**
   * Generates the database script.
   *
   * @return string
   *   The PHP script.
   */
  protected function generateScript() {
    $tables = '';
    foreach ($this->getTables() as $table) {
      $schema = $this->getTableSchema($table);
      $data = $this->getTableData($table);
      $tables .= $this->getTableScript($table, $schema, $data);
    }
    $script = $this->getTemplate();
    // Substitute in the tables.
    $script = str_replace('{{TABLES}}', trim($tables), $script);
    // Modules.
    $script = str_replace('{{MODULES}}', $this->getModulesScript(), $script);
    return trim($script);
  }

  /**
   * Returns a list of tables, not including those set to be excluded.
   *
   * @return array
   *   An array of table names.
   */
  protected function getTables() {
    $tables = array_values($this->connection->schema()->findTables('%'));

    foreach ($tables as $key => $table) {
      // Remove any explicitly excluded tables.
      foreach ($this->excludeTables as $pattern) {
        if (preg_match('/^' . $pattern . '$/', $table)) {
          unset($tables[$key]);
        }
      }
    }

    return $tables;
  }

  /**
   * Returns a schema array for a given table.
   *
   * @param string $table
   *   The table name.
   *
   * @return array
   *   A schema array (as defined by hook_schema()).
   *
   * @todo This implementation is hard-coded for MySQL.
   */
  protected function getTableSchema($table) {
    $query = $this->connection->query("SHOW FULL COLUMNS FROM {" . $table . "}");
    $definition = [];
    while (($row = $query->fetchAssoc()) !== FALSE) {
      $name = $row['Field'];
      // Parse out the field type and meta information.
      preg_match('@([a-z]+)(?:\((\d+)(?:,(\d+))?\))?\s*(unsigned)?@', $row['Type'], $matches);
      $type  = $this->fieldTypeMap($matches[1]);
      if ($row['Extra'] === 'auto_increment') {
        // If this is an auto increment, then the type is 'serial'.
        $type = 'serial';
      }
      $definition['fields'][$name] = [
        'type' => $type,
        'not null' => $row['Null'] === 'NO',
      ];
      if ($size = $this->fieldSizeMap($matches[1])) {
        $definition['fields'][$name]['size'] = $size;
      }
      if (isset($matches[2]) && $type === 'numeric') {
        // Add precision and scale.
        $definition['fields'][$name]['precision'] = $matches[2];
        $definition['fields'][$name]['scale'] = $matches[3];
      }
      elseif ($type === 'time' || $type === 'datetime') {
        // @todo Core doesn't support these, but copied from `migrate-db.sh` for now.
        // Convert to varchar.
        $definition['fields'][$name]['type'] = 'varchar';
        $definition['fields'][$name]['length'] = '100';
      }
      elseif (!isset($definition['fields'][$name]['size'])) {
        // Try use the provided length, if it doesn't exist default to 100. It's
        // not great but good enough for our dumps at this point.
        $definition['fields'][$name]['length'] = isset($matches[2]) ? $matches[2] : 100;
      }

      if (isset($row['Default'])) {
        $definition['fields'][$name]['default'] = $row['Default'];
      }

      if (isset($matches[4])) {
        $definition['fields'][$name]['unsigned'] = TRUE;
      }

      // Check for the 'varchar_ascii' type that should be 'binary'.
      if (isset($row['Collation']) && $row['Collation'] == 'ascii_bin') {
        $definition['fields'][$name]['type'] = 'varchar_ascii';
        $definition['fields'][$name]['binary'] = TRUE;
      }

      // Check for the non-binary 'varchar_ascii'.
      if (isset($row['Collation']) && $row['Collation'] == 'ascii_general_ci') {
        $definition['fields'][$name]['type'] = 'varchar_ascii';
      }

      // Check for the 'utf8_bin' collation.
      if (isset($row['Collation']) && $row['Collation'] == 'utf8_bin') {
        $definition['fields'][$name]['binary'] = TRUE;
      }
    }

    // Set primary key, unique keys, and indexes.
    $this->getTableIndexes($table, $definition);

    // Set table collation.
    $this->getTableCollation($table, $definition);

    return $definition;
  }

  /**
   * Adds primary key, unique keys, and index information to the schema.
   *
   * @param string $table
   *   The table to find indexes for.
   * @param array &$definition
   *   The schema definition to modify.
   */
  protected function getTableIndexes($table, &$definition) {
    // Note, this query doesn't support ordering, so that is worked around
    // below by keying the array on Seq_in_index.
    $query = $this->connection->query("SHOW INDEX FROM {" . $table . "}");
    while (($row = $query->fetchAssoc()) !== FALSE) {
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
  }

  /**
   * Set the table collation.
   *
   * @param string $table
   *   The table to find indexes for.
   * @param array &$definition
   *   The schema definition to modify.
   */
  protected function getTableCollation($table, &$definition) {
    $query = $this->connection->query("SHOW TABLE STATUS LIKE '{" . $table . "}'");
    $data = $query->fetchAssoc();

    // Set `mysql_character_set`. This will be ignored by other backends.
    $definition['mysql_character_set'] = str_replace('_general_ci', '', $data['Collation']);
  }

  /**
   * Gets all data from a given table.
   *
   * If a table is set to be schema only, and empty array is returned.
   *
   * @param string $table
   *   The table to query.
   *
   * @return array
   *   The data from the table as an array.
   */
  protected function getTableData($table) {
    // Check for schema only.
    foreach ($this->schemaOnly as $schema_only) {
      if (preg_match('/^' . $schema_only . '$/', $table)) {
        return [];
      }
    }
    $order = $this->getFieldOrder($table);
    $query = $this->connection->query("SELECT * FROM {" . $table . "} " . $order );
    $results = [];
    while (($row = $query->fetchAssoc()) !== FALSE) {
      $results[] = $row;
    }
    return $results;
  }

  /**
   * Given a database field type, return a Drupal type.
   *
   * @param string $type
   *   The MySQL field type.
   *
   * @return string
   *   The Drupal schema field type. If there is no mapping, the original field
   *   type is returned.
   */
  protected function fieldTypeMap($type) {
    // Convert everything to lowercase.
    $map = array_map('strtolower', $this->connection->schema()->getFieldTypeMap());
    $map = array_flip($map);

    // The MySql map contains type:size. Remove the size part.
    return isset($map[$type]) ? explode(':', $map[$type])[0] : $type;
  }

  /**
   * Given a database field type, return a Drupal size.
   *
   * @param string $type
   *   The MySQL field type.
   *
   * @return string
   *   The Drupal schema field size.
   */
  protected function fieldSizeMap($type) {
    // Convert everything to lowercase.
    $map = array_map('strtolower', $this->connection->schema()->getFieldTypeMap());
    $map = array_flip($map);

    $schema_type = explode(':', $map[$type])[0];
    // Only specify size on these types.
    if (in_array($schema_type, ['blob', 'float', 'int', 'text'])) {
      // The MySql map contains type:size. Remove the type part.
      return explode(':', $map[$type])[1];
    }
  }

  /**
   * Gets field ordering for a given table.
   *
   * @param string $table
   *   The table name.
   *
   * @return string
   *   The order string to append to the query.
   */
  protected function getFieldOrder($table) {
    // @todo this is MySQL only since there are no Database API functions for
    // table column data.
    // @todo this code is duplicated in `core/scripts/migrate-db.sh`.
    $connection_info = $this->connection->getConnectionOptions();
    // Order by primary keys.
    $order = '';
    $query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE (`TABLE_SCHEMA` = '" . $connection_info['database'] . "')
    AND (`TABLE_NAME` = '{" . $table . "}') AND (`COLUMN_KEY` = 'PRI')
    ORDER BY COLUMN_NAME";
    $results = $this->connection->query($query);
    while (($row = $results->fetchAssoc()) !== FALSE) {
      $order .= $row['COLUMN_NAME'] . ', ';
    }
    if (!empty($order)) {
      $order = ' ORDER BY ' . rtrim($order, ', ');
    }
    return $order;
  }

  /**
   * The script template.
   *
   * @return string
   *   The template for the generated PHP script.
   */
  protected function getTemplate() {
    $script = <<<'ENDOFSCRIPT'
<?php
/**
 * @file
 * Filled installation of Drupal 8.0, for test purposes.
 *
 * This file was generated by the dump-database-d8.php script, from an
 * installation of Drupal 8. It has the following modules installed:
 *
{{MODULES}}
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

{{TABLES}}

ENDOFSCRIPT;
    return $script;
  }

  /**
   * The part of the script for each table.
   *
   * @param string $table
   *   Table name.
   * @param array $schema
   *   Drupal schema definition.
   * @param array $data
   *   Data for the table.
   *
   * @return string
   *   The table create statement, and if there is data, the insert command.
   */
  protected function getTableScript($table, array $schema, array $data) {
    $output = '';
    $output .= "\$connection->schema()->createTable('" . $table . "', " . Variable::export($schema) . ");\n\n";
    if (!empty($data)) {
      $insert = '';
      foreach ($data as $record) {
        $insert .= "->values(" . Variable::export($record) . ")\n";
      }
      $output .= "\$connection->insert('" . $table . "')\n"
        . "->fields(" . Variable::export(array_keys($schema['fields'])) . ")\n"
        . $insert
        . "->execute();\n\n";
    }
    return $output;
  }

  /**
   * List of modules enabled for insertion into the script docblock.
   *
   * @return string
   *   The formatted list of enabled modules.
   */
  protected function getModulesScript() {
    $output = '';
    $modules = $this->moduleHandler->getModuleList();
    ksort($modules);
    foreach ($modules as $module => $filename) {
      $output .= " *  - $module\n";
    }
    return rtrim($output, "\n");
  }

}
