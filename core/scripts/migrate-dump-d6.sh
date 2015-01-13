#!/usr/bin/env php
<?php

/**
 * This script is designed for assisting core developers working on Migrate to
 * regenerate the Migrate dump files. Technically this script can be used to
 * dump any database into a PHP representation however that is not its primary
 * use case.
 *
 * Dump files only need to be updated when you're adding or updating tests which
 * need new Drupal 6 data.
 *
 *  - Clone the repository from: https://www.drupal.org/sandbox/benjy/2405029
 *  - Create a database called d6_migrate and import core/migrate_drupal/src/Tests/d6.gz
 *  - Add an entry into your Drupal 8 settings file, eg: $databases['d6_migrate']['default'] = array ( // Credentials );
 *  - Use the Drupal 6 site to make data changes as needed.
 *  - Run ./core/scripts/migrate-dump-d6.sh to re-export the tables.
 */

use Doctrine\Common\Inflector\Inflector;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Variable;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

$autoloader  = require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/bootstrap.inc';

$request = Request::createFromGlobals();
Settings::initialize(dirname(dirname(__DIR__)), DrupalKernel::findSitePath($request), $autoloader);

$output_folder = DRUPAL_ROOT . '/core/modules/migrate_drupal/src/Tests/Table';
$class_template = '<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\{{CLASS_NAME}}.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the {{TABLE}} table.
 */
class {{CLASS_NAME}} extends Drupal6DumpBase {

  public function load() {
    $this->createTable("{{TABLE}}", {{TABLE_DEFINITION}});
    $this->database->insert("{{TABLE}}")->fields({{PHP_FIELDS}})
    {{PHP_VALUES}}->execute();
  }

}
';

// Generate a list of tables using the 'migrate' db connection.
$connection = Database::getConnection('default', 'd6_migrate');
$tables = $connection->query('SHOW TABLES')->fetchCol();

foreach ($tables as $table) {
  if (substr($table, 0, 5) === 'cache' || $table === 'watchdog' || $table === 'menu_router' || $table === 'sessions') {
    continue;
  }

  // Generate the class name.
  $class = Inflector::classify($table);

  // Generate the field values.
  $query = $connection->query(_db_get_query($table));
  $values = '';
  while(($row = $query->fetchAssoc()) !== FALSE) {
    $values .= '->values(' . Variable::export($row, '    ') . ')';
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
  $definition = Variable::export($definition, '    ');

  // Do our substitutions.
  $php = str_replace('{{TABLE}}', $table, $class_template);
  $php = str_replace('{{CLASS_NAME}}', $class, $php);
  $php = str_replace('{{PHP_VALUES}}', $values, $php);
  $php = str_replace('{{PHP_FIELDS}}', $fields, $php);
  $php = str_replace('{{TABLE_DEFINITION}}', $definition, $php);


  // Save the file.
  $php = implode("\n", array_map('rtrim', explode("\n", $php)));
  file_put_contents("$output_folder/$class.php", $php);
}

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

function _db_get_query($table) {
  $queries = array(
    'users' => 'SELECT * FROM {users} WHERE uid NOT IN (0,1)',
  );
  return isset($queries[$table]) ? $queries[$table] : 'SELECT * FROM {' . $table .'}';
}

$options = $connection->getConnectionOptions();
$user = $options['username'];
$pass = $options['password'];
$db = $options['database'];

@system("mysqldump -u$user -p$pass $db | gzip -c > core/modules/migrate_drupal/src/Tests/d6.gz");
