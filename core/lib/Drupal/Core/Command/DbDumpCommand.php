<?php

namespace Drupal\Core\Command;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Database\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
class DbDumpCommand extends DbCommandBase {

  /**
   * An array of table patterns to exclude completely.
   *
   * This excludes any lingering simpletest tables generated during test runs.
   *
   * @var array
   */
  protected $excludeTables = ['test[0-9]+'];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('dump-database-d8-mysql')
      ->setDescription('Dump the current database to a generation script')
      ->addOption('schema-only', NULL, InputOption::VALUE_OPTIONAL, 'A comma separated list of tables to only export the schema without data.', 'cache.*,sessions,watchdog');
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $connection = $this->getDatabaseConnection($input);

    // If not explicitly set, disable ANSI which will break generated php.
    if ($input->hasParameterOption(['--ansi']) !== TRUE) {
      $output->setDecorated(FALSE);
    }

    $schema_tables = $input->getOption('schema-only');
    $schema_tables = explode(',', $schema_tables);

    $output->writeln($this->generateScript($connection, $schema_tables), OutputInterface::OUTPUT_RAW);
    return 0;
  }

  /**
   * Generates the database script.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param array $schema_only
   *   Table patterns for which to only dump the schema, no data.
   *
   * @return string
   *   The PHP script.
   */
  protected function generateScript(Connection $connection, array $schema_only = []) {
    $tables = '';

    $schema_only_patterns = [];
    foreach ($schema_only as $match) {
      $schema_only_patterns[] = '/^' . $match . '$/';
    }

    foreach ($this->getTables($connection) as $table) {
      $schema = $this->getTableSchema($connection, $table);
      // Check for schema only.
      if (empty($schema_only_patterns) || preg_replace($schema_only_patterns, '', $table)) {
        $data = $this->getTableData($connection, $table);
      }
      else {
        $data = [];
      }
      $tables .= $this->getTableScript($table, $schema, $data);
    }
    $script = $this->getTemplate();
    // Substitute in the version.
    $script = str_replace('{{VERSION}}', \Drupal::VERSION, $script);
    // Substitute in the tables.
    $script = str_replace('{{TABLES}}', trim($tables), $script);
    return trim($script);
  }

  /**
   * Returns a list of tables, not including those set to be excluded.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   *
   * @return array
   *   An array of table names.
   */
  protected function getTables(Connection $connection) {
    $tables = array_values($connection->schema()->findTables('%'));

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The table name.
   *
   * @return array
   *   A schema array (as defined by hook_schema()).
   *
   * @todo This implementation is hard-coded for MySQL.
   */
  protected function getTableSchema(Connection $connection, $table) {
    // Check this is MySQL.
    if ($connection->databaseType() !== 'mysql') {
      throw new \RuntimeException('This script can only be used with MySQL database backends.');
    }

    $query = $connection->query("SHOW FULL COLUMNS FROM {" . $table . "}");
    $definition = [];
    while (($row = $query->fetchAssoc()) !== FALSE) {
      $name = $row['Field'];
      // Parse out the field type and meta information.
      preg_match('@([a-z]+)(?:\((\d+)(?:,(\d+))?\))?\s*(unsigned)?@', $row['Type'], $matches);
      $type = $this->fieldTypeMap($connection, $matches[1]);
      if ($row['Extra'] === 'auto_increment') {
        // If this is an auto increment, then the type is 'serial'.
        $type = 'serial';
      }
      $definition['fields'][$name] = [
        'type' => $type,
        'not null' => $row['Null'] === 'NO',
      ];
      if ($size = $this->fieldSizeMap($connection, $matches[1])) {
        $definition['fields'][$name]['size'] = $size;
      }
      if (isset($matches[2]) && $type === 'numeric') {
        // Add precision and scale.
        $definition['fields'][$name]['precision'] = $matches[2];
        $definition['fields'][$name]['scale'] = $matches[3];
      }
      elseif ($type === 'time') {
        // @todo Core doesn't support these, but copied from `migrate-db.sh` for now.
        // Convert to varchar.
        $definition['fields'][$name]['type'] = 'varchar';
        $definition['fields'][$name]['length'] = '100';
      }
      elseif ($type === 'datetime') {
        // Adjust for other database types.
        $definition['fields'][$name]['mysql_type'] = 'datetime';
        $definition['fields'][$name]['pgsql_type'] = 'timestamp without time zone';
        $definition['fields'][$name]['sqlite_type'] = 'varchar';
        $definition['fields'][$name]['sqlsrv_type'] = 'smalldatetime';
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
    $this->getTableIndexes($connection, $table, $definition);

    // Set table collation.
    $this->getTableCollation($connection, $table, $definition);

    return $definition;
  }

  /**
   * Adds primary key, unique keys, and index information to the schema.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The table to find indexes for.
   * @param array &$definition
   *   The schema definition to modify.
   */
  protected function getTableIndexes(Connection $connection, $table, &$definition) {
    // Note, this query doesn't support ordering, so that is worked around
    // below by keying the array on Seq_in_index.
    $query = $connection->query("SHOW INDEX FROM {" . $table . "}");
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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The table to find indexes for.
   * @param array &$definition
   *   The schema definition to modify.
   */
  protected function getTableCollation(Connection $connection, $table, &$definition) {
    // Remove identifier quotes from the table name. See
    // \Drupal\Core\Database\Driver\mysql\Connection::$identifierQuotes.
    $table = trim($connection->prefixTables('{' . $table . '}'), '"');
    $query = $connection->query("SHOW TABLE STATUS WHERE NAME = :table_name", [':table_name' => $table]);
    $data = $query->fetchAssoc();

    // Map the collation to a character set. For example, 'utf8mb4_general_ci'
    // (MySQL 5) or 'utf8mb4_0900_ai_ci' (MySQL 8) will be mapped to 'utf8mb4'.
    list($charset,) = explode('_', $data['Collation'], 2);

    // Set `mysql_character_set`. This will be ignored by other backends.
    $definition['mysql_character_set'] = $charset;
  }

  /**
   * Gets all data from a given table.
   *
   * If a table is set to be schema only, and empty array is returned.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The table to query.
   *
   * @return array
   *   The data from the table as an array.
   */
  protected function getTableData(Connection $connection, $table) {
    $order = $this->getFieldOrder($connection, $table);
    $query = $connection->query("SELECT * FROM {" . $table . "} " . $order);
    $results = [];
    while (($row = $query->fetchAssoc()) !== FALSE) {
      $results[] = $row;
    }
    return $results;
  }

  /**
   * Given a database field type, return a Drupal type.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $type
   *   The MySQL field type.
   *
   * @return string
   *   The Drupal schema field type. If there is no mapping, the original field
   *   type is returned.
   */
  protected function fieldTypeMap(Connection $connection, $type) {
    // Convert everything to lowercase.
    $map = array_map('strtolower', $connection->schema()->getFieldTypeMap());
    $map = array_flip($map);

    // The MySql map contains type:size. Remove the size part.
    return isset($map[$type]) ? explode(':', $map[$type])[0] : $type;
  }

  /**
   * Given a database field type, return a Drupal size.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $type
   *   The MySQL field type.
   *
   * @return string|null
   *   The Drupal schema field size.
   */
  protected function fieldSizeMap(Connection $connection, $type) {
    // Convert everything to lowercase.
    $map = array_map('strtolower', $connection->schema()->getFieldTypeMap());
    $map = array_flip($map);

    // Do nothing if the field type is not defined.
    if (!isset($map[$type])) {
      return NULL;
    }

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The table name.
   *
   * @return string
   *   The order string to append to the query.
   */
  protected function getFieldOrder(Connection $connection, $table) {
    // @todo this is MySQL only since there are no Database API functions for
    // table column data.
    // @todo this code is duplicated in `core/scripts/migrate-db.sh`.
    $connection_info = $connection->getConnectionOptions();
    // Order by primary keys.
    $order = '';
    $query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE (`TABLE_SCHEMA` = '" . $connection_info['database'] . "')
    AND (`TABLE_NAME` = '{" . $table . "}') AND (`COLUMN_KEY` = 'PRI')
    ORDER BY COLUMN_NAME";
    $results = $connection->query($query);
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
    // The template contains an instruction for the file to be ignored by PHPCS.
    // This is because the files can be huge and coding standards are
    // irrelevant.
    $script = <<<'ENDOFSCRIPT'
<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal {{VERSION}} db-tools.php script.
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
      $fields = Variable::export(array_keys($schema['fields']));
      $output .= <<<EOT
\$connection->insert('$table')
->fields($fields)
{$insert}->execute();

EOT;
    }
    return $output;
  }

}
