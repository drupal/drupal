#!/usr/bin/env php
<?php

/**
 * This script is designed for assisting core developers working on Migrate to
 * regenerate the Migrate dump files. Technically this script can be used to
 * dump any database into a PHP representation, but that's not its primary
 * use case.
 *
 * Dump files only need to be updated when you're adding or updating tests which
 * need new Drupal source data. Drupal 6 and 7 are supported by this script. The
 * version of Drupal will be auto-detected during dumping by scanning the system
 * table's schema_version column.
 *
 * To dump a database, you must have a connection to it defined in settings.php.
 * Then you can run this script like so:
 * migrate-db.sh --dump --database=CONNECTION_KEY
 *
 * To restore a Drupal 6 database from dump files:
 * migrate-db.sh --restore --core=6 --database=CONNECTION_KEY
 *
 * And to restore a Drupal 7 DB:
 * migrate-db.sh --restore --core=7 --database=CONNECTION_KEY
 *
 * You can also validate a set of dumps to ensure that they haven't been altered.
 * For Drupal 6 and 7, respectively:
 * migrate-db.sh --validate --core=6
 * migrate-db.sh --validate --core=7
 *
 * --dump and --restore always require the --database option. --validate and --restore
 * always require the --core option, which can accept values of 6, 6.x, 7, or 7.x.
 */

use Doctrine\Common\Inflector\Inflector;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Variable;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

$autoloader  = require __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../includes/bootstrap.inc';

$request = Request::createFromGlobals();
Settings::initialize(dirname(dirname(__DIR__)), DrupalKernel::findSitePath($request), $autoloader);

// Fully bootstrap Drupal so that things like file_scan_directory() can be used
// (for validating and restoring, and possibly other things).
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->loadLegacyIncludes();

$options = getopt('', array('database:', 'dump', 'restore', 'validate', 'core:'));

if (isset($options['dump'])) {
  if (empty($options['database'])) {
    echo "Missing required --database option.\n";
    return;
  }

  $connection = Database::getConnection('default', $options['database']);
  $connection_info = $connection->getConnectionOptions();
  $version = _get_core_version_from_database($connection);
  $output_folder = DRUPAL_ROOT . '/core/modules/migrate_drupal/src/Tests/Table/' . $version;

  $class_template = '<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\{{DRUPAL_VERSION}}\{{CLASS_NAME}}.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\{{DRUPAL_VERSION}};

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the {{TABLE}} table.
 */
class {{CLASS_NAME}} extends DrupalDumpBase {

  public function load() {
    $this->createTable("{{TABLE}}", {{TABLE_DEFINITION}});
    $this->database->insert("{{TABLE}}")->fields({{PHP_FIELDS}})
    {{PHP_VALUES}}->execute();
  }

}
';

  // Generate a list of tables.
  $tables = $connection->query('SHOW TABLES')->fetchCol();

  // Get all character sets, keyed by table name.
  $character_sets = $connection->query('SELECT T.TABLE_NAME, CCSA.CHARACTER_SET_NAME FROM information_schema.TABLES T INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA ON CCSA.COLLATION_NAME = T.TABLE_COLLATION WHERE T.TABLE_SCHEMA = \'' . $connection_info['database'] . '\'')
  ->fetchAllKeyed();

  foreach ($tables as $table) {
    // Generate the class name.
    $class = Inflector::classify($table);

    // Order by primary keys
    $order = '';
    $query = "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
    WHERE (`TABLE_SCHEMA` = '" . $connection_info['database'] . "')
    AND (`TABLE_NAME` = '{$table}') AND (`COLUMN_KEY` = 'PRI')
    ORDER BY COLUMN_NAME";
    $results = $connection->query($query);
    while(($row = $results->fetchAssoc()) !== FALSE) {
      $order .= '{' . $row['COLUMN_NAME'] . '}, ';
    }
    if (!(empty($order))) {
      $order = rtrim ($order, ", ");
      $order = ' ORDER BY ' . $order;
    }

    // Generate the field values.
    $query = $connection->query(_db_get_query($table) . $order);
    $values = '';
    // Only dump the actual table values if we're NOT looking at a cache table,
    // watchdog or sessions tables.
    if (substr($table, 0, 5) !== 'cache' && !in_array($table, array('watchdog', 'sessions'))) {
      while(($row = $query->fetchAssoc()) !== FALSE) {
        $values .= '->values(' . Variable::export($row, '    ') . ')';
      }
    }

    // Generate the field names.
    $query = $connection->query("SHOW COLUMNS FROM {$table}");
    $definition = [];
    while(($row = $query->fetchAssoc()) !== FALSE) {
      $field_name = $row['Field'];

      // Parse out the field type and meta information.
      preg_match('@([a-z]+)(?:\((\d+)(?:,(\d+))?\))?\s*(unsigned)?@', $row['Type'], $matches);
      $field_type  = _db_field_type_map($matches[1]);

      // If it's auto-increment then make it a serial instead.
      if ($row['Extra'] === 'auto_increment') {
        $field_type = 'serial';
      }

      // Add primary key entries as needed.
      if ($row['Key'] === 'PRI') {
        $definition['primary key'][] = $field_name;
      }

      // All fields have a type and not null.
      $definition['fields'][$field_name] = [
        'type' => $field_type,
        'not null' => $row['Null'] === 'NO',
      ];

      // If this is a numeric field, the meta will be precision and scale.
      if (isset($matches[2]) && $field_type === 'numeric') {
        $definition['fields'][$field_name]['precision'] = $matches[2];
        $definition['fields'][$field_name]['scale'] = $matches[3];
      }
      elseif ($field_type === 'time' || $field_type === 'datetime') {
        // We use varchar to replace the D6 datetime and time fields.
        $definition['fields'][$field_name]['type'] = 'varchar';
        $definition['fields'][$field_name]['length'] = '100';
      }
      else {
        // Try use the provided length, if it doesn't exist default to 100. It's
        // not great but good enough for our dumps at this point.
        $definition['fields'][$field_name]['length'] = isset($matches[2]) ? $matches[2] : 100;
      }

      if (isset($row['Default'])) {
        $definition['fields'][$field_name]['default'] = $row['Default'];
      }

      if (isset($matches[4])) {
        $definition['fields'][$field_name]['unsigned'] = TRUE;
      }

    }
    $fields = Variable::export(array_keys($definition['fields']), '    ');
    if ($connection->driver() == 'mysql') {
      $definition['mysql_character_set'] = $character_sets[$table];
    }
    $definition = Variable::export($definition, '    ');

    // Do our substitutions.
    $php = str_replace('{{TABLE}}', $table, $class_template);
    $php = str_replace('{{DRUPAL_VERSION}}', $version, $php);
    $php = str_replace('{{CLASS_NAME}}', $class, $php);
    $php = str_replace('{{PHP_VALUES}}', $values, $php);
    $php = str_replace('{{PHP_FIELDS}}', $fields, $php);
    $php = str_replace('{{TABLE_DEFINITION}}', $definition, $php);

    // Save the file.
    $php = implode("\n", array_map('rtrim', explode("\n", $php)));
    // Hash the dump code so that the restore script can easily determine if it
    // has been mucked with manually.
    $php .= '#' . md5($php) . "\n";
    file_put_contents("$output_folder/$class.php", $php);
  }
}
elseif (isset($options['restore'])) {
  if (!\Drupal::moduleHandler()->moduleExists('migrate_drupal')) {
    echo "The migrate_drupal module must be enabled to restore a database.\n";
    return;
  }
  elseif (empty($options['database'])) {
    echo "Missing required --database option.\n";
    return;
  }

  $connection = Database::getConnection('default', $options['database']);

  $version = _get_core_version_from_options();
  if ($version) {
    $tables_dir = DRUPAL_ROOT . '/core/modules/migrate_drupal/src/Tests/Table/' . $version;
    $tables = file_scan_directory($tables_dir, '/.php$/', array('recurse' => FALSE));
    foreach ($tables as $table) {
      if (table_is_valid($table->uri)) {
        restore_table($table->uri, $connection);
      }
      else {
        echo "Skipping invalid table {$table->uri}\n";
      }
    }
  }
  else {
    echo "Missing --core option.\n";
    return;
  }
}
elseif (isset($options['validate'])) {
  $version = _get_core_version_from_options();

  if ($version) {
    $tables_dir = DRUPAL_ROOT . '/core/modules/migrate_drupal/src/Tests/Table/' . $version;
    $tables = file_scan_directory($tables_dir, '/.php$/', array('recurse' => FALSE));
    foreach ($tables as $table) {
      echo (table_is_valid($table->uri) ? 'OK' : 'INVALID') . ": {$table->uri}\n";
    }
  }
  else {
    echo "Missing --core option.\n";
    return;
  }
}
else {
  echo "Invalid options.\n";
  return;
}

/**
 * Restores a table from a dump file.
 *
 * @param string $path
 *  The path to the dump file.
 * @param \Drupal\Core\Database\Connection $connection
 *  The target database connection.
 */
function restore_table($path, Connection $connection) {
  require_once $path;
  $version = _get_core_version_from_options();

  $class = 'Drupal\migrate_drupal\Tests\Table\\' . $version . '\\' . substr(basename($path), 0, -4);
  try {
    (new $class($connection))->load();
  }
  catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . ' [' . get_class($e) . "]\n";
  }
}

/**
 * Validates a dump file by reading in the MD5 of the file contents (last 32 bytes)
 * and comparing them with the MD5 of everything except those last 32 bytes.
 *
 * @param string $path
 *  The path to the dump file.
 *
 * @return bool
 */
function table_is_valid($path) {
  // The call to rtrim() is important, since we need to extract a specific
  // number of bytes from the end of the file.
  $contents = rtrim(file_get_contents($path));
  $dump = substr($contents, 0, -33);
  $hash = substr($contents, -32);
  return (md5($dump) === $hash);
}

/**
 * Statically maps the --core option to either 'd6' or 'd7', or NULL if the --core
 * option's value is unrecognized.
 *
 * @return string|null
 */
function _get_core_version_from_options() {
  global $options;

  if (isset($options['core'])) {
    switch ($options['core']) {
      case '6':
      case '6.x':
        return 'd6';
      case '7':
      case '7.x':
        return 'd7';
      default:
        break;
    }
  }
}

/**
 * Reads a Drupal 6 or 7 database to determine its major core version.
 *
 * @param \Drupal\Core\Database\Connection $connection
 *  The database connection.
 *
 * @return string
 *
 * @throws \UnexpectedValueException if the discovered core version is unrecognized
 * or unsupported.
 */
function _get_core_version_from_database(Connection $connection) {
  $version = $connection
    ->select('system')
    ->fields('system', array('schema_version'))
    ->condition('name', 'system')
    ->execute()
    ->fetchField();

  if ($version >= 7000) {
    return 'd7';
  }
  elseif ($version >= 6000) {
    return 'd6';
  }
  else {
    throw new \UnexpectedValueException("Unknown Drupal core version.");
  }
}

/**
 * Statically maps a SQL field type to a Schema API type. If there is no mapping, the
 * original field type is returned.
 *
 * @param string $sql_type
 *  The field type as known to the database.
 *
 * @return string
 */
function _db_field_type_map($sql_type) {
  $map = array(
    'longtext' => 'text',
    'tinytext' => 'text',
    'mediumtext' => 'text',

    'tinyint' => 'int',
    'smallint' => 'int',
    'mediumint' => 'int',
    'bigint' => 'int',
    'int' => 'int',

    'double' => 'numeric',
    'float' => 'numeric',
    'decimal' => 'numeric',

    'longblob' => 'blob',
  );

  return isset($map[$sql_type]) ? $map[$sql_type] : $sql_type;
}

/**
 * Returns the appropriate SQL query string to fetch all values from a table.
 *
 * @param string $table
 *  The table's name.
 *
 * @return string
 */
function _db_get_query($table) {
  $queries = array(
    'users' => 'SELECT * FROM {users} WHERE uid NOT IN (0,1)',
    // Volatile state variables should always be ignored. We don't want to
    // exclude all cache_* variables, since that would exclude cache_lifetime,
    // which is configuration, not state.
    'variable' => "SELECT * FROM {variable} WHERE name NOT LIKE 'cache_flush_%' AND name NOT IN ('cache', 'drupal_css_cache_files', 'javascript_parsed', 'statistics_day_timestamp', 'update_last_check')",
  );
  return isset($queries[$table]) ? $queries[$table] : 'SELECT * FROM {' . $table .'}';
}
