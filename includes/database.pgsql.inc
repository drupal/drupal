<?php

/**
 * @file
 * Database interface code for PostgreSQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * Report database status.
 */
function db_status_report() {
  $t = get_t();

  $version = db_version();

  $form['pgsql'] = array(
    'title' => $t('PostgreSQL database'),
    'value' => $version,
  );

  if (version_compare($version, DRUPAL_MINIMUM_PGSQL) < 0) {
    $form['pgsql']['severity'] = REQUIREMENT_ERROR;
    $form['pgsql']['description'] = $t('Your PostgreSQL Server is too old. Drupal requires at least PostgreSQL %version.', array('%version' => DRUPAL_MINIMUM_PGSQL));
  }

  return $form;
}

/**
 * Returns the version of the database server currently in use.
 *
 * @return Database server version
 */
function db_version() {
  return db_result(db_query("SHOW SERVER_VERSION"));
}

/**
 * Initialize a database connection.
 */
function db_connect($url) {
  // Check if PostgreSQL support is present in PHP
  if (!function_exists('pg_connect')) {
    _db_error_page('Unable to use the PostgreSQL database because the PostgreSQL extension for PHP is not installed. Check your <code>php.ini</code> to see how you can enable it.');
  }

  $url = parse_url($url);
  $conn_string = '';

  // Decode url-encoded information in the db connection string
  if (isset($url['user'])) {
    $conn_string .= ' user='. urldecode($url['user']);
  }
  if (isset($url['pass'])) {
    $conn_string .= ' password='. urldecode($url['pass']);
  }
  if (isset($url['host'])) {
    $conn_string .= ' host='. urldecode($url['host']);
  }
  if (isset($url['path'])) {
    $conn_string .= ' dbname='. substr(urldecode($url['path']), 1);
  }
  if (isset($url['port'])) {
    $conn_string .= ' port='. urldecode($url['port']);
  }

  // pg_last_error() does not return a useful error message for database
  // connection errors. We must turn on error tracking to get at a good error
  // message, which will be stored in $php_errormsg.
  $track_errors_previous = ini_get('track_errors');
  ini_set('track_errors', 1);

  $connection = @pg_connect($conn_string);
  if (!$connection) {
    require_once './includes/unicode.inc';
    _db_error_page(decode_entities($php_errormsg));
  }

  // Restore error tracking setting
  ini_set('track_errors', $track_errors_previous);

  pg_query($connection, "set client_encoding=\"UTF8\"");
  return $connection;
}

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
 * Helper function for db_query().
 */
function _db_query($query, $debug = 0) {
  global $active_db, $last_result, $queries;

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $last_result = pg_query($active_db, $query);

  if (variable_get('dev_query', 0)) {
    $bt = debug_backtrace();
    $query = $bt[2]['function'] ."\n". $query;
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print '<p>query: '. $query .'<br />error:'. pg_last_error($active_db) .'</p>';
  }

  if ($last_result !== FALSE) {
    return $last_result;
  }
  else {
    // Indicate to drupal_error_handler that this is a database error.
    ${DB_ERROR} = TRUE;
    trigger_error(check_plain(pg_last_error($active_db) ."\nquery: ". $query), E_USER_WARNING);
    return FALSE;
  }
}

/**
 * Fetch one result row from the previous query as an object.
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   An object representing the next row of the result, or FALSE. The attributes
 *   of this object are the table fields selected by the query.
 */
function db_fetch_object($result) {
  if ($result) {
    return pg_fetch_object($result);
  }
}

/**
 * Fetch one result row from the previous query as an array.
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   An associative array representing the next row of the result, or FALSE.
 *   The keys of this object are the names of the table fields selected by the
 *   query, and the values are the field values for this result row.
 */
function db_fetch_array($result) {
  if ($result) {
    return pg_fetch_assoc($result);
  }
}

/**
 * Return an individual result field from the previous query.
 *
 * Only use this function if exactly one field is being selected; otherwise,
 * use db_fetch_object() or db_fetch_array().
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   The resulting field or FALSE.
 */
function db_result($result) {
  if ($result && pg_num_rows($result) > 0) {
    $array = pg_fetch_row($result);
    return $array[0];
  }
  return FALSE;
}

/**
 * Determine whether the previous query caused an error.
 */
function db_error() {
  global $active_db;
  return pg_last_error($active_db);
}

/**
 * Returns the last insert id. This function is thread safe.
 *
 * @param $table
 *   The name of the table you inserted into.
 * @param $field
 *   The name of the autoincrement field.
 */
function db_last_insert_id($table, $field) {
  return db_result(db_query("SELECT CURRVAL('{". db_escape_table($table) ."}_". db_escape_table($field) ."_seq')"));
}

/**
 * Determine the number of rows changed by the preceding query.
 */
function db_affected_rows() {
  global $last_result;
  return empty($last_result) ? 0 : pg_affected_rows($last_result);
}

/**
 * Runs a limited-range query in the active database.
 *
 * Use this as a substitute for db_query() when a subset of the query
 * is to be returned.
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
 *   Valid %-modifiers are: %s, %d, %f, %b (binary data, do not enclose
 *   in '') and %%.
 *
 *   NOTE: using this syntax will cast NULL and FALSE values to decimal 0,
 *   and TRUE values to decimal 1.
 *
 * @param $from
 *   The first result row to return.
 * @param $count
 *   The maximum number of result rows to return.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query_range($query) {
  $args = func_get_args();
  $count = array_pop($args);
  $from = array_pop($args);
  array_shift($args);

  $query = db_prefix_tables($query);
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  $query .= ' LIMIT '. (int)$count .' OFFSET '. (int)$from;
  return _db_query($query);
}

/**
 * Runs a SELECT query and stores its results in a temporary table.
 *
 * Use this as a substitute for db_query() when the results need to stored
 * in a temporary table. Temporary tables exist for the duration of the page
 * request.
 * User-supplied arguments to the query should be passed in as separate parameters
 * so that they can be properly escaped to avoid SQL injection attacks.
 *
 * Note that if you need to know how many results were returned, you should do
 * a SELECT COUNT(*) on the temporary table afterwards. db_affected_rows() does
 * not give consistent result across different database types in this case.
 *
 * @param $query
 *   A string containing a normal SELECT SQL query.
 * @param ...
 *   A variable number of arguments which are substituted into the query
 *   using printf() syntax. The query arguments can be enclosed in one
 *   array instead.
 *   Valid %-modifiers are: %s, %d, %f, %b (binary data, do not enclose
 *   in '') and %%.
 *
 *   NOTE: using this syntax will cast NULL and FALSE values to decimal 0,
 *   and TRUE values to decimal 1.
 *
 * @param $table
 *   The name of the temporary table to select into. This name will not be
 *   prefixed as there is no risk of collision.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query_temporary($query) {
  $args = func_get_args();
  $tablename = array_pop($args);
  array_shift($args);

  $query = preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE '. $tablename .' AS SELECT', db_prefix_tables($query));
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  return _db_query($query);
}

/**
 * Returns a properly formatted Binary Large OBject value.
 * In case of PostgreSQL encodes data for insert into bytea field.
 *
 * @param $data
 *   Data to encode.
 * @return
 *  Encoded data.
 */
function db_encode_blob($data) {
  return "'". pg_escape_bytea($data) ."'";
}

/**
 * Returns text from a Binary Large OBject value.
 * In case of PostgreSQL decodes data after select from bytea field.
 *
 * @param $data
 *   Data to decode.
 * @return
 *  Decoded data.
 */
function db_decode_blob($data) {
  return pg_unescape_bytea($data);
}

/**
 * Prepare user input for use in a database query, preventing SQL injection attacks.
 * Note: This function requires PostgreSQL 7.2 or later.
 */
function db_escape_string($text) {
  return pg_escape_string($text);
}

/**
 * Lock a table.
 * This function automatically starts a transaction.
 */
function db_lock_table($table) {
  db_query('BEGIN; LOCK TABLE {'. db_escape_table($table) .'} IN EXCLUSIVE MODE');
}

/**
 * Unlock all locked tables.
 * This function automatically commits a transaction.
 */
function db_unlock_tables() {
  db_query('COMMIT');
}

/**
 * Check if a table exists.
 *
 * @param $table
 *   The name of the table.
 *
 * @return
 *   TRUE if the table exists, and FALSE if the table does not exist.
 */
function db_table_exists($table) {
  return (bool) db_result(db_query("SELECT COUNT(*) FROM pg_class WHERE relname = '{". db_escape_table($table) ."}'"));
}

/**
 * Check if a column exists in the given table.
 *
 * @param $table
 *   The name of the table.
 * @param $column
 *   The name of the column.
 *
 * @return
 *   TRUE if the column exists, and FALSE if the column does not exist.
 */
function db_column_exists($table, $column) {
  return (bool) db_result(db_query("SELECT COUNT(pg_attribute.attname) FROM pg_class, pg_attribute WHERE pg_attribute.attrelid = pg_class.oid AND pg_class.relname = '{". db_escape_table($table) ."}' AND attname = '". db_escape_table($column) ."'"));
}

/**
 * Verify if the database is set up correctly.
 */
function db_check_setup() {
  $t = get_t();

  $encoding = db_result(db_query('SHOW server_encoding'));
  if (!in_array(strtolower($encoding), array('unicode', 'utf8'))) {
    drupal_set_message($t('Your PostgreSQL database is set up with the wrong character encoding (%encoding). It is possible it will not work as expected. It is advised to recreate it with UTF-8/Unicode encoding. More information can be found in the <a href="@url">PostgreSQL documentation</a>.', array('%encoding' => $encoding, '@url' => 'http://www.postgresql.org/docs/7.4/interactive/multibyte.html')), 'status');
  }
}

/**
 * @} End of "ingroup database".
 */

/**
 * @ingroup schemaapi
 * @{
 */

/**
 * This maps a generic data type in combination with its data size
 * to the engine-specific data type.
 */
function db_type_map() {
  // Put :normal last so it gets preserved by array_flip.  This makes
  // it much easier for modules (such as schema.module) to map
  // database types back into schema types.
  $map = array(
    'varchar:normal' => 'varchar',
    'char:normal' => 'character',

    'text:tiny' => 'text',
    'text:small' => 'text',
    'text:medium' => 'text',
    'text:big' => 'text',
    'text:normal' => 'text',

    'int:tiny' => 'smallint',
    'int:small' => 'smallint',
    'int:medium' => 'int',
    'int:big' => 'bigint',
    'int:normal' => 'int',

    'float:tiny' => 'real',
    'float:small' => 'real',
    'float:medium' => 'real',
    'float:big' => 'double precision',
    'float:normal' => 'real',

    'numeric:normal' => 'numeric',

    'blob:big' => 'bytea',
    'blob:normal' => 'bytea',

    'datetime:normal' => 'timestamp without time zone',

    'serial:tiny' => 'serial',
    'serial:small' => 'serial',
    'serial:medium' => 'serial',
    'serial:big' => 'bigserial',
    'serial:normal' => 'serial',
  );
  return $map;
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
  $sql_fields = array();
  foreach ($table['fields'] as $field_name => $field) {
    $sql_fields[] = _db_create_field_sql($field_name, _db_process_field($field));
  }

  $sql_keys = array();
  if (isset($table['primary key']) && is_array($table['primary key'])) {
    $sql_keys[] = 'PRIMARY KEY ('. implode(', ', $table['primary key']) .')';
  }
  if (isset($table['unique keys']) && is_array($table['unique keys'])) {
    foreach ($table['unique keys'] as $key_name => $key) {
      $sql_keys[] = 'CONSTRAINT {'. $name .'}_'. $key_name .'_key UNIQUE ('. implode(', ', $key) .')';
    }
  }

  $sql = "CREATE TABLE {". $name ."} (\n\t";
  $sql .= implode(",\n\t", $sql_fields);
  if (count($sql_keys) > 0) {
    $sql .= ",\n\t";
  }
  $sql .= implode(",\n\t", $sql_keys);
  $sql .= "\n)";
  $statements[] = $sql;

  if (isset($table['indexes']) && is_array($table['indexes'])) {
    foreach ($table['indexes'] as $key_name => $key) {
      $statements[] = _db_create_index_sql($name, $key_name, $key);
    }
  }

  return $statements;
}

function _db_create_index_sql($table, $name, $fields) {
  $query = 'CREATE INDEX {'. $table .'}_'. $name .'_idx ON {'. $table .'} (';
  $query .= _db_create_key_sql($fields) .')';
  return $query;
}

function _db_create_key_sql($fields) {
  $ret = array();
  foreach ($fields as $field) {
    if (is_array($field)) {
      $ret[] = 'substr('. $field[0] .', 1, '. $field[1] .')';
    }
    else {
      $ret[] = $field;
    }
  }
  return implode(', ', $ret);
}

function _db_create_keys(&$ret, $table, $new_keys) {
  if (isset($new_keys['primary key'])) {
    db_add_primary_key($ret, $table, $new_keys['primary key']);
  }
  if (isset($new_keys['unique keys'])) {
    foreach ($new_keys['unique keys'] as $name => $fields) {
      db_add_unique_key($ret, $table, $name, $fields);
    }
  }
  if (isset($new_keys['indexes'])) {
    foreach ($new_keys['indexes'] as $name => $fields) {
      db_add_index($ret, $table, $name, $fields);
    }
  }
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
  if (!isset($field['pgsql_type'])) {
    $map = db_type_map();
    $field['pgsql_type'] = $map[$field['type'] .':'. $field['size']];
  }
  if ($field['type'] == 'serial') {
    unset($field['not null']);
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
  $sql = $name .' '. $spec['pgsql_type'];

  if ($spec['type'] == 'serial') {
    unset($spec['not null']);
  }

  if (in_array($spec['type'], array('varchar', 'char', 'text')) && isset($spec['length'])) {
    $sql .= '('. $spec['length'] .')';
  }
  elseif (isset($spec['precision']) && isset($spec['scale'])) {
    $sql .= '('. $spec['precision'] .', '. $spec['scale'] .')';
  }

  if (!empty($spec['unsigned'])) {
    $sql .= " CHECK ($name >= 0)";
  }

  if (isset($spec['not null']) && $spec['not null']) {
    $sql .= ' NOT NULL';
  }
  if (isset($spec['default'])) {
    $default = is_string($spec['default']) ? "'". $spec['default'] ."'" : $spec['default'];
    $sql .= " default $default";
  }

  return $sql;
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
 * @param $new_keys
 *   Optional keys and indexes specification to be created on the
 *   table along with adding the field. The format is the same as a
 *   table specification but without the 'fields' element.  If you are
 *   adding a type 'serial' field, you MUST specify at least one key
 *   or index including it in this array. See db_change_field() for more
 *   explanation why.
 */
function db_add_field(&$ret, $table, $field, $spec, $new_keys = array()) {
  $fixnull = FALSE;
  if (!empty($spec['not null']) && !isset($spec['default'])) {
    $fixnull = TRUE;
    $spec['not null'] = FALSE;
  }
  $query = 'ALTER TABLE {'. $table .'} ADD COLUMN ';
  $query .= _db_create_field_sql($field, _db_process_field($spec));
  $ret[] = update_sql($query);
  if (isset($spec['initial'])) {
    // All this because update_sql does not support %-placeholders.
    $sql = 'UPDATE {'. $table .'} SET '. $field .' = '. db_type_placeholder($spec['type']);
    $result = db_query($sql, $spec['initial']);
    $ret[] = array('success' => $result !== FALSE, 'query' => check_plain($sql .' ('. $spec['initial'] .')'));
  }
  if ($fixnull) {
    $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $field SET NOT NULL");
  }
  if (isset($new_keys)) {
    _db_create_keys($ret, $table, $new_keys);
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
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP COLUMN '. $field);
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
    implode(',', $fields) .')');
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
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP CONSTRAINT {'. $table .'}_pkey');
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
  $name = '{'. $table .'}_'. $name .'_key';
  $ret[] = update_sql('ALTER TABLE {'. $table .'} ADD CONSTRAINT '.
    $name .' UNIQUE ('. implode(',', $fields) .')');
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
  $name = '{'. $table .'}_'. $name .'_key';
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DROP CONSTRAINT '. $name);
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
  $ret[] = update_sql(_db_create_index_sql($table, $name, $fields));
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
  $name = '{'. $table .'}_'. $name .'_idx';
  $ret[] = update_sql('DROP INDEX '. $name);
}

/**
 * Change a field definition.
 *
 * IMPORTANT NOTE: To maintain database portability, you have to explicitly
 * recreate all indices and primary keys that are using the changed field.
 *
 * That means that you have to drop all affected keys and indexes with
 * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
 * To recreate the keys and indices, pass the key definitions as the
 * optional $new_keys argument directly to db_change_field().
 *
 * For example, suppose you have:
 * @code
 * $schema['foo'] = array(
 *   'fields' => array(
 *     'bar' => array('type' => 'int', 'not null' => TRUE)
 *   ),
 *   'primary key' => array('bar')
 * );
 * @endcode
 * and you want to change foo.bar to be type serial, leaving it as the
 * primary key.  The correct sequence is:
 * @code
 * db_drop_primary_key($ret, 'foo');
 * db_change_field($ret, 'foo', 'bar', 'bar',
 *   array('type' => 'serial', 'not null' => TRUE),
 *   array('primary key' => array('bar')));
 * @endcode
 *
 * The reasons for this are due to the different database engines:
 *
 * On PostgreSQL, changing a field definition involves adding a new field
 * and dropping an old one which* causes any indices, primary keys and
 * sequences (from serial-type fields) that use the changed field to be dropped.
 *
 * On MySQL, all type 'serial' fields must be part of at least one key
 * or index as soon as they are created.  You cannot use
 * db_add_{primary_key,unique_key,index}() for this purpose because
 * the ALTER TABLE command will fail to add the column without a key
 * or index specification.  The solution is to use the optional
 * $new_keys argument to create the key or index at the same time as
 * field.
 *
 * You could use db_add_{primary_key,unique_key,index}() in all cases
 * unless you are converting a field to be type serial. You can use
 * the $new_keys argument in all cases.
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
 * @param $new_keys
 *   Optional keys and indexes specification to be created on the
 *   table along with changing the field. The format is the same as a
 *   table specification but without the 'fields' element.
 */
function db_change_field(&$ret, $table, $field, $field_new, $spec, $new_keys = array()) {
  $ret[] = update_sql('ALTER TABLE {'. $table .'} RENAME "'. $field .'" TO "'. $field .'_old"');
  $not_null = isset($spec['not null']) ? $spec['not null'] : FALSE;
  unset($spec['not null']);

  if (!array_key_exists('size', $spec)) {
    $spec['size'] = 'normal';
  }
  db_add_field($ret, $table, "$field_new", $spec);

  // We need to type cast the new column to best transfer the data
  // db_type_map will return possiblities that are not 'cast-able'
  // such as serial - they must be made 'int' instead.
  $map =  db_type_map();
  $typecast = $map[$spec['type'] .':'. $spec['size']];
  if (in_array($typecast, array('serial', 'bigserial', 'numeric'))) {
    $typecast = 'int';
  }
  $ret[] = update_sql('UPDATE {'. $table .'} SET '. $field_new .' = CAST('. $field .'_old AS '. $typecast .')');

  if ($not_null) {
    $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $field_new SET NOT NULL");
  }

  db_drop_field($ret, $table, $field .'_old');

  if (isset($new_keys)) {
    _db_create_keys($ret, $table, $new_keys);
  }
}

/**
 * @} End of "ingroup schemaapi".
 */

