<?php

// $Id: database.mysql-common.inc,v 1.3 2007/05/29 11:36:33 dries Exp $

/**
 * @file
 * Functions shared between mysql and mysqli database engines.
 */

/**
 * @ingroup schemaapi
 * @{
 */

/**
 *  Generate SQL to create a new table from a Drupal schema definition.
 *
 * @param $table
 *   A valid Drupal table definition array.
 * @return
 *   An array of SQL statements to create the table.
 */
function db_create_table_sql($table) {

  if (empty($table['mysql_suffix'])) {
    $table['mysql_suffix'] = "/*!40100 DEFAULT CHARACTER SET UTF8 */";
  }

  $sql = "CREATE TABLE {". $table['name'] ."} (\n";

  // Add the SQL statement for each field.
  foreach ($table['fields'] as $name => $field) {
    $sql .= _db_create_field_sql($name, _db_process_field($field)) .", \n";
  }

  // Process keys & indexes.
  if (!empty($table['primary key'])) {
    $sql .= " PRIMARY KEY (". _db_create_key_sql($table['primary key']) ."), \n";
  }
  if (!empty($table['unique keys'])) {
    foreach ($table['unique keys'] as $key => $fields)
      $sql .= " UNIQUE KEY $key (". _db_create_key_sql($fields) ."), \n";
  }
  if (!empty($table['indexes'])) {
    foreach ($table['indexes'] as $index => $fields)
      $sql .= " INDEX $index (". _db_create_key_sql($fields) ."), \n";
  }

  // Remove the last comma and space.
  $sql = substr($sql, 0, -3) ."\n) ";

  $sql .= $table['mysql_suffix'];

  return array($sql);
}

function _db_create_key_sql($fields) {
  $ret = array();
  foreach ($fields as $field) {
    if (is_array($field)) {
      $ret[] = $field[0] .'('. $field[1] .')';
    }
    else {
      $ret[] = $field;
    }
  }
  return implode(', ', $ret);
}

/**
 * Set database-engine specific properties for a field.
 *
 * @param $field
 *   A field description array, as specified in the schema documentation.
 */
function _db_process_field($field) {

  if (!isset($field['size'])) {
    $field['size'] = 'normal';
  }

  // Set the correct database-engine specific datatype.
  if (!isset($field['mysql_type'])) {
    $map = db_type_map();
    $field['mysql_type'] = $map[$field['type'] .':'. $field['size']];
  }

  if ($field['type'] == 'serial') {
    $field['auto_increment'] = TRUE;
  }

  return $field;
}

/**
 * Create an SQL string for a field to be used in table creation or alteration.
 *
 * Before passing a field out of a schema definition into this function it has
 * to be processed by _db_process_field().
 *
 * @param $name
 *    Name of the field.
 * @param $spec
 *    The field specification, as per the schema data structure format.
 */
function _db_create_field_sql($name, $spec) {
  $sql = "`". $name ."` ". $spec['mysql_type'];

  if (isset($spec['length'])) {
    $sql .= '('. $spec['length'] .')';
  }
  elseif (isset($spec['precision']) && isset($spec['scale'])) {
    $sql .= '('. $spec['scale'] .', '. $spec['precision'] .')';
  }

  if (!empty($spec['unsigned'])) {
    $sql .= ' unsigned';
  }

  if (!empty($spec['not null'])) {
    $sql .= ' NOT NULL';
  }

  if (!empty($spec['auto_increment'])) {
    $sql .= ' auto_increment';
  }

  if (isset($spec['default'])) {
    if (is_string($spec['default'])) {
      $spec['default'] = "'". $spec['default'] ."'";
    }
    $sql .= ' DEFAULT '. $spec['default'];
  }

  if (empty($spec['not null']) && !isset($spec['default'])) {
    $sql .= ' DEFAULT NULL';
  }

  return $sql;
}

/**
 * This maps a generic data type in combination with its data size
 * to the engine-specific data type.
 */
function db_type_map() {
  // Put :normal last so it gets preserved by array_flip.  This makes
  // it much easier for modules (such as schema.module) to map
  // database types back into schema types.
  $map = array(
    'varchar:normal'  => 'VARCHAR',

    'text:tiny'       => 'SMALLTEXT',
    'text:small'      => 'SMALLTEXT',
    'text:medium'     => 'MEDIUMTEXT',
    'text:big'        => 'LONGTEXT',
    'text:normal'     => 'TEXT',

    'serial:tiny'     => 'TINYINT',
    'serial:small'    => 'SMALLINT',
    'serial:medium'   => 'MEDIUMINT',
    'serial:big'      => 'BIGINT',
    'serial:normal'   => 'INT',

    'int:tiny'        => 'TINYINT',
    'int:small'       => 'SMALLINT',
    'int:medium'      => 'MEDIUMINT',
    'int:big'         => 'BIGINT',
    'int:normal'      => 'INT',

    'float:tiny'      => 'FLOAT',
    'float:small'     => 'FLOAT',
    'float:medium'    => 'FLOAT',
    'float:big'       => 'DOUBLE',
    'float:normal'    => 'FLOAT',

    'numeric:normal'  => 'NUMERIC',

    'blob:big'        => 'LONGBLOB',
    'blob:normal'     => 'BLOB',

    'datetime:normal' => 'DATETIME',
  );
  return $map;
}

/**
 * Rename a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be renamed.
 * @param $new_name
 *   The new name for the table.
 */
function db_rename_table(&$ret, $table, $new_name) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} RENAME TO {'. $new_name .'}');
}

/**
 * Drop a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be dropped.
 */
function db_drop_table(&$ret, $table) {
  $ret[] = update_sql('DROP TABLE {'. $table .'}');
}

/**
 * Add a new field to a table.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   Name of the table to be altered.
 * @param $field
 *   Name of the field to be added.
 * @param $spec
 *   The field specification array, as taken from a schema definition
 */
function db_add_field(&$ret, $table, $field, $spec) {
  $query = 'ALTER TABLE {'. $table .'} ADD ';
  $query .= _db_create_field_sql($field, _db_process_field($spec));
  $ret[] = update_sql($query);
}

/**
 * Drop a field.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be dropped.
 */
function db_drop_field(&$ret, $table, $field) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP '. $field);
}

/**
 * Set the default value for a field.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 * @param $default
 *   Default value to be set. NULL for 'default NULL'.
 */
function db_field_set_default(&$ret, $table, $field, $default) {
  if ($default == NULL) {
    $default = 'NULL';
  }
  else {
    $default = is_string($default) ? "'$default'" : $default;
  }

  $ret[] = update_sql('ALTER TABLE {'. $table .'} ALTER COLUMN '. $field .' SET DEFAULT '. $default);
}

/**
 * Set a field to have no default value.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 */
function db_field_set_no_default(&$ret, $table, $field) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} ALTER COLUMN '. $field .' DROP DEFAULT');
}

/**
 * Add a primary key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $fields
 *   Fields for the primary key.
 */
function db_add_primary_key(&$ret, $table, $fields) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} ADD PRIMARY KEY ('.
    _db_create_key_sql($fields) .')');
}

/**
 * Drop the primary key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 */
function db_drop_primary_key(&$ret, $table) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP PRIMARY KEY');
}

/**
 * Add a unique key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 * @param $fields
 *   An array of field names.
 */
function db_add_unique_key(&$ret, $table, $name, $fields) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} ADD UNIQUE KEY '.
    $name .' ('. _db_create_key_sql($fields) .')');
}

/**
 * Drop a unique key.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 */
function db_drop_unique_key(&$ret, $table, $name) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP KEY '. $name);
}

/**
 * Add an index.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 * @param $fields
 *   An array of field names.
 */
function db_add_index(&$ret, $table, $name, $fields) {
  $query = 'ALTER TABLE {'. $table .'} ADD INDEX '. $name .' (';

  foreach ($fields as $current) {
    $query .= $current .', ';
  }

  // Remove the last comma, add a closing bracket.
  $query = substr($query, 0, -2) .')';

  $ret[] = update_sql($query);
}

/**
 * Drop an index.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 */
function db_drop_index(&$ret, $table, $name) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP INDEX '. $name);
}

/**
 * Change a field definition.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   Name of the table.
 * @param $field
 *   Name of the field to change.
 * @param $field_new
 *   New name for the field (set to the same as $field if you don't want to change the name).
 * @param $spec
 *   The field specification for the new field.
 */
function db_change_field(&$ret, $table, $field, $field_new, $spec) {
  $ret[] = update_sql("ALTER TABLE {". $table ."} CHANGE $field ".
    _db_create_field_sql($field_new, _db_process_field($spec)));
}

/**
 * Update a field definition to match its schema. If the field is
 * involved in any keys or indexes, recreate them.
 *
 * @param $ret
 *   Array to which query results will be added.
 * @param $table
 *   Name of the table.
 * @param $field
 *   Name of the field to update.
 */
function db_update_field(&$ret, $table, $field) {
  $spec = drupal_get_schema($table);
  db_change_field($ret, $table, $field, $field, $spec['fields'][$field]);
}

/**
 * @} End of "ingroup schemaapi".
 */

?>