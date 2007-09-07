<?php
// $Id: database.mysql-common.inc,v 1.11 2007/09/07 10:42:56 goba Exp $

/**
 * @file
 * Functions shared between mysql and mysqli database engines.
 */

/**
 * @ingroup schemaapi
 * @{
 */

/**
 * Runs a basic query in the active database.
 *
 * User-supplied arguments to the query should be passed in as separate
 * parameters so that they can be properly escaped to avoid SQL injection
 * attacks.
 *
 * @param $query
 *   A string containing an SQL query.
 * @param ...
 *   A variable number of arguments which are substituted into the query
 *   using printf() syntax. Instead of a variable number of query arguments,
 *   you may also pass a single array containing the query arguments.
 *
 *   Valid %-modifiers are: %s, %d, %f, %b (binary data, do not enclose
 *   in '') and %%.
 *
 *   NOTE: using this syntax will cast NULL and FALSE values to decimal 0,
 *   and TRUE values to decimal 1.
 *
 * @return
 *   A database query result resource, or FALSE if the query was not
 *   executed correctly.
 */
function db_query($query) {
  $args = func_get_args();
  array_shift($args);
  $query = db_prefix_tables($query);
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  return _db_query($query);
}

/**
 * Generate SQL to create a new table from a Drupal schema definition.
 *
 * @param $name
 *   The name of the table to create.
 * @param $table
 *   A Schema API table definition array.
 * @return
 *   An array of SQL statements to create the table.
 */
function db_create_table_sql($name, $table) {

  if (empty($table['mysql_suffix'])) {
    $table['mysql_suffix'] = "/*!40100 DEFAULT CHARACTER SET UTF8 */";
  }

  $sql = "CREATE TABLE {". $name ."} (\n";

  // Add the SQL statement for each field.
  foreach ($table['fields'] as $field_name => $field) {
    $sql .= _db_create_field_sql($field_name, _db_process_field($field)) .", \n";
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
 *   The field specification array, as taken from a schema definition.
 *   The specification may also contain the key 'initial', the newly
 *   created field will be set to the value of the key in all rows.
 *   This is most useful for creating NOT NULL columns with no default
 *   value in existing tables.
 */
function db_add_field(&$ret, $table, $field, $spec) {
  $fixnull = FALSE;
  if (!empty($spec['not null']) && !isset($spec['default'])) {
    $fixnull = TRUE;
    $spec['not null'] = FALSE;
  }
  $query = 'ALTER TABLE {'. $table .'} ADD ';
  $query .= _db_create_field_sql($field, _db_process_field($spec));
  $ret[] = update_sql($query);
  if (isset($spec['initial'])) {
    // All this because update_sql does not support %-placeholders.
    $sql = 'UPDATE {'. $table .'} SET '. $field .' = '. _db_type_placeholder($spec['type']);
    $result = db_query($sql, $spec['initial']);
    $ret[] = array('success' => $result !== FALSE, 'query' => check_plain($sql .' ('. $spec['initial'] .')'));
  }
  if ($fixnull) {
    $spec['not null'] = TRUE;
    db_change_field($ret, $table, $field, $field, $spec);
  }
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
  $query = 'ALTER TABLE {'. $table .'} ADD INDEX '. $name .' ('. _db_create_key_sql($fields) .')';
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
 * IMPORTANT NOTE: On some database systems (notably PostgreSQL),
 * changing a field definition involves adding a new field and
 * dropping an old one. This means that any indices, primary keys and
 * sequences (from serial-type fields) that use the field to be
 * changed get dropped.  For database portability, you MUST drop them
 * explicitly before calling db_change_field() and then re-create them
 * afterwards.  Use db_{add,drop}_{primary_key,unique_key,index} for
 * this purpose.
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
 * Returns the last insert id.
 *
 * @param $table
 *   The name of the table you inserted into.
 * @param $field
 *   The name of the autoincrement field.
 */
function db_last_insert_id($table, $field) {
  return db_result(db_query('SELECT LAST_INSERT_ID()'));
}

/**
 * @} End of "ingroup schemaapi".
 */
