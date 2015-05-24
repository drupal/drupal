<?php

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Site\Settings;

/**
 * @file
 * Core systems for the database layer.
 *
 * Classes required for basic functioning of the database system should be
 * placed in this file.  All utility functions should also be placed in this
 * file only, as they cannot auto-load the way classes can.
 */

/**
 * @addtogroup database
 * @{
 */

/**
 * Executes an arbitrary query string against the active database.
 *
 * Use this function for SELECT queries if it is just a simple query string.
 * If the caller or other modules need to change the query, use db_select()
 * instead.
 *
 * Do not use this function for INSERT, UPDATE, or DELETE queries. Those should
 * be handled via db_insert(), db_update() and db_delete() respectively.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting. If the argument corresponding to a placeholder is
 *   an array of values to be expanded, e.g. for an IN query, the placeholder
 *   should be named with a trailing bracket like :example[]
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\StatementInterface
 *   A prepared statement object, already executed.
 *
 * @see \Drupal\Core\Database\Connection::defaultOptions()
 */
function db_query($query, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->query($query, $args, $options);
}

/**
 * Executes a query against the active database, restricted to a range.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting.
 * @param $from
 *   The first record from the result set to return.
 * @param $count
 *   The number of records to return from the result set.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\StatementInterface
 *   A prepared statement object, already executed.
 *
 * @see \Drupal\Core\Database\Connection::defaultOptions()
 */
function db_query_range($query, $from, $count, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->queryRange($query, $from, $count, $args, $options);
}

/**
 * Executes a SELECT query string and saves the result set to a temporary table.
 *
 * The execution of the query string happens against the active database.
 *
 * @param $query
 *   The prepared SELECT statement query to run. Although it will accept both
 *   named and unnamed placeholders, named placeholders are strongly preferred
 *   as they are more self-documenting.
 * @param $args
 *   An array of values to substitute into the query. If the query uses named
 *   placeholders, this is an associative array in any order. If the query uses
 *   unnamed placeholders (?), this is an indexed array and the order must match
 *   the order of placeholders in the query string.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return
 *   The name of the temporary table.
 *
 * @see \Drupal\Core\Database\Connection::defaultOptions()
 */
function db_query_temporary($query, array $args = array(), array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }

  return Database::getConnection($options['target'])->queryTemporary($query, $args, $options);
}

/**
 * Returns a new InsertQuery object for the active database.
 *
 * @param $table
 *   The table into which to insert.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Insert
 *   A new Insert object for this connection.
 */
function db_insert($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'replica') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->insert($table, $options);
}

/**
 * Returns a new MergeQuery object for the active database.
 *
 * @param $table
 *   The table into which to merge.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Merge
 *   A new Merge object for this connection.
 */
function db_merge($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'replica') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->merge($table, $options);
}

/**
 * Returns a new UpdateQuery object for the active database.
 *
 * @param $table
 *   The table to update.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Update
 *   A new Update object for this connection.
 */
function db_update($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'replica') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->update($table, $options);
}

/**
 * Returns a new DeleteQuery object for the active database.
 *
 * @param $table
 *   The table from which to delete.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Delete
 *   A new Delete object for this connection.
 */
function db_delete($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'replica') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->delete($table, $options);
}

/**
 * Returns a new TruncateQuery object for the active database.
 *
 * @param $table
 *   The table from which to delete.
 * @param $options
 *   An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Truncate
 *   A new Truncate object for this connection.
 */
function db_truncate($table, array $options = array()) {
  if (empty($options['target']) || $options['target'] == 'replica') {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->truncate($table, $options);
}

/**
 * Returns a new SelectQuery object for the active database.
 *
 * @param $table
 *   The base table for this query. May be a string or another SelectQuery
 *   object. If a query object is passed, it will be used as a subselect.
 * @param $alias
 *   (optional) The alias for the base table of this query.
 * @param $options
 *   (optional) An array of options to control how the query operates.
 *
 * @return \Drupal\Core\Database\Query\Select
 *   A new Select object for this connection.
 */
function db_select($table, $alias = NULL, array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->select($table, $alias, $options);
}

/**
 * Returns a new transaction object for the active database.
 *
 * @param string $name
 *   Optional name of the transaction.
 * @param array $options
 *   An array of options to control how the transaction operates:
 *   - target: The database target name.
 *
 * @return \Drupal\Core\Database\Transaction
 *   A new Transaction object for this connection.
 */
function db_transaction($name = NULL, array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = 'default';
  }
  return Database::getConnection($options['target'])->startTransaction($name);
}

/**
 * Sets a new active database.
 *
 * @param $key
 *   The key in the $databases array to set as the default database.
 *
 * @return
 *   The key of the formerly active database.
 */
function db_set_active($key = 'default') {
  return Database::setActiveConnection($key);
}

/**
 * Restricts a dynamic table name to safe characters.
 *
 * Only keeps alphanumeric and underscores.
 *
 * @param $table
 *   The table name to escape.
 *
 * @return
 *   The escaped table name as a string.
 */
function db_escape_table($table) {
  return Database::getConnection()->escapeTable($table);
}

/**
 * Restricts a dynamic column or constraint name to safe characters.
 *
 * Only keeps alphanumeric and underscores.
 *
 * @param $field
 *   The field name to escape.
 *
 * @return
 *   The escaped field name as a string.
 */
function db_escape_field($field) {
  return Database::getConnection()->escapeField($field);
}

/**
 * Escapes characters that work as wildcard characters in a LIKE pattern.
 *
 * The wildcard characters "%" and "_" as well as backslash are prefixed with
 * a backslash. Use this to do a search for a verbatim string without any
 * wildcard behavior.
 *
 * You must use a query builder like db_select() in order to use db_like() on
 * all supported database systems. Using db_like() with db_query() or
 * db_query_range() is not supported.
 *
 * For example, the following does a case-insensitive query for all rows whose
 * name starts with $prefix:
 * @code
 * $result = db_select('person', 'p')
 *   ->fields('p')
 *   ->condition('name', db_like($prefix) . '%', 'LIKE')
 *   ->execute()
 *   ->fetchAll();
 * @endcode
 *
 * Backslash is defined as escape character for LIKE patterns in
 * DatabaseCondition::mapConditionOperator().
 *
 * @param $string
 *   The string to escape.
 *
 * @return
 *   The escaped string.
 */
function db_like($string) {
  return Database::getConnection()->escapeLike($string);
}

/**
 * Retrieves the name of the currently active database driver.
 *
 * @return
 *   The name of the currently active database driver.
 */
function db_driver() {
  return Database::getConnection()->driver();
}

/**
 * Closes the active database connection.
 *
 * @param $options
 *   An array of options to control which connection is closed. Only the target
 *   key has any meaning in this case.
 */
function db_close(array $options = array()) {
  if (empty($options['target'])) {
    $options['target'] = NULL;
  }
  Database::closeConnection($options['target']);
}

/**
 * Retrieves a unique id.
 *
 * Use this function if for some reason you can't use a serial field. Using a
 * serial field is preferred, and InsertQuery::execute() returns the value of
 * the last ID inserted.
 *
 * @param $existing_id
 *   After a database import, it might be that the sequences table is behind, so
 *   by passing in a minimum ID, it can be assured that we never issue the same
 *   ID.
 *
 * @return
 *   An integer number larger than any number returned before for this sequence.
 */
function db_next_id($existing_id = 0) {
  return Database::getConnection()->nextId($existing_id);
}

/**
 * Returns a new DatabaseCondition, set to "OR" all conditions together.
 *
 * @return \Drupal\Core\Database\Query\Condition
 *   A new Condition object, set to "OR" all conditions together.
 */
function db_or() {
  return new Condition('OR');
}

/**
 * Returns a new DatabaseCondition, set to "AND" all conditions together.
 *
 * @return \Drupal\Core\Database\Query\Condition
 *   A new Condition object, set to "AND" all conditions together.
 */
function db_and() {
  return new Condition('AND');
}

/**
 * Returns a new DatabaseCondition, set to "XOR" all conditions together.
 *
 * @return \Drupal\Core\Database\Query\Condition
 *   A new Condition object, set to "XOR" all conditions together.
 */
function db_xor() {
  return new Condition('XOR');
}

/**
 * Returns a new DatabaseCondition, set to the specified conjunction.
 *
 * Internal API function call.  The db_and(), db_or(), and db_xor()
 * functions are preferred.
 *
 * @param $conjunction
 *   The conjunction to use for query conditions (AND, OR or XOR).
 *
 * @return \Drupal\Core\Database\Query\Condition
 *   A new Condition object, set to the specified conjunction.
 */
function db_condition($conjunction) {
  return new Condition($conjunction);
}

/**
 * @} End of "addtogroup database".
 */


/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * Creates a new table from a Drupal table definition.
 *
 * @param $name
 *   The name of the table to create.
 * @param $table
 *   A Schema API table definition array.
 */
function db_create_table($name, $table) {
  return Database::getConnection()->schema()->createTable($name, $table);
}

/**
 * Returns an array of field names from an array of key/index column specifiers.
 *
 * This is usually an identity function but if a key/index uses a column prefix
 * specification, this function extracts just the name.
 *
 * @param $fields
 *   An array of key/index column specifiers.
 *
 * @return
 *   An array of field names.
 */
function db_field_names($fields) {
  return Database::getConnection()->schema()->fieldNames($fields);
}

/**
 * Checks if an index exists in the given table.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 * @param $name
 *   The name of the index in drupal (no prefixing).
 *
 * @return
 *   TRUE if the given index exists, otherwise FALSE.
 */
function db_index_exists($table, $name) {
  return Database::getConnection()->schema()->indexExists($table, $name);
}

/**
 * Checks if a table exists.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 *
 * @return
 *   TRUE if the given table exists, otherwise FALSE.
 */
function db_table_exists($table) {
  return Database::getConnection()->schema()->tableExists($table);
}

/**
 * Checks if a column exists in the given table.
 *
 * @param $table
 *   The name of the table in drupal (no prefixing).
 * @param $field
 *   The name of the field.
 *
 * @return
 *   TRUE if the given column exists, otherwise FALSE.
 */
function db_field_exists($table, $field) {
  return Database::getConnection()->schema()->fieldExists($table, $field);
}

/**
 * Finds all tables that are like the specified base table name.
 *
 * @param $table_expression
 *   An SQL expression, for example "simpletest%" (without the quotes).
 *   BEWARE: this is not prefixed, the caller should take care of that.
 *
 * @return
 *   Array, both the keys and the values are the matching tables.
 */
function db_find_tables($table_expression) {
  return Database::getConnection()->schema()->findTables($table_expression);
}

function _db_create_keys_sql($spec) {
  return Database::getConnection()->schema()->createKeysSql($spec);
}

/**
 * Renames a table.
 *
 * @param $table
 *   The current name of the table to be renamed.
 * @param $new_name
 *   The new name for the table.
 */
function db_rename_table($table, $new_name) {
  return Database::getConnection()->schema()->renameTable($table, $new_name);
}

/**
 * Drops a table.
 *
 * @param $table
 *   The table to be dropped.
 */
function db_drop_table($table) {
  return Database::getConnection()->schema()->dropTable($table);
}

/**
 * Adds a new field to a table.
 *
 * @param $table
 *   Name of the table to be altered.
 * @param $field
 *   Name of the field to be added.
 * @param $spec
 *   The field specification array, as taken from a schema definition. The
 *   specification may also contain the key 'initial'; the newly-created field
 *   will be set to the value of the key in all rows. This is most useful for
 *   creating NOT NULL columns with no default value in existing tables.
 * @param $keys_new
 *   (optional) Keys and indexes specification to be created on the table along
 *   with adding the field. The format is the same as a table specification, but
 *   without the 'fields' element. If you are adding a type 'serial' field, you
 *   MUST specify at least one key or index including it in this array. See
 *   db_change_field() for more explanation why.
 *
 * @see db_change_field()
 */
function db_add_field($table, $field, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->addField($table, $field, $spec, $keys_new);
}

/**
 * Drops a field.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be dropped.
 */
function db_drop_field($table, $field) {
  return Database::getConnection()->schema()->dropField($table, $field);
}

/**
 * Sets the default value for a field.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 * @param $default
 *   Default value to be set. NULL for 'default NULL'.
 */
function db_field_set_default($table, $field, $default) {
  return Database::getConnection()->schema()->fieldSetDefault($table, $field, $default);
}

/**
 * Sets a field to have no default value.
 *
 * @param $table
 *   The table to be altered.
 * @param $field
 *   The field to be altered.
 */
function db_field_set_no_default($table, $field) {
  return Database::getConnection()->schema()->fieldSetNoDefault($table, $field);
}

/**
 * Adds a primary key to a database table.
 *
 * @param $table
 *   Name of the table to be altered.
 * @param $fields
 *   Array of fields for the primary key.
 */
function db_add_primary_key($table, $fields) {
  return Database::getConnection()->schema()->addPrimaryKey($table, $fields);
}

/**
 * Drops the primary key of a database table.
 *
 * @param $table
 *   Name of the table to be altered.
 */
function db_drop_primary_key($table) {
  return Database::getConnection()->schema()->dropPrimaryKey($table);
}

/**
 * Adds a unique key.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 * @param $fields
 *   An array of field names.
 */
function db_add_unique_key($table, $name, $fields) {
  return Database::getConnection()->schema()->addUniqueKey($table, $name, $fields);
}

/**
 * Drops a unique key.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the key.
 */
function db_drop_unique_key($table, $name) {
  return Database::getConnection()->schema()->dropUniqueKey($table, $name);
}

/**
 * Adds an index.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 * @param $fields
 *   An array of field names.
 */
function db_add_index($table, $name, $fields) {
  return Database::getConnection()->schema()->addIndex($table, $name, $fields);
}

/**
 * Drops an index.
 *
 * @param $table
 *   The table to be altered.
 * @param $name
 *   The name of the index.
 */
function db_drop_index($table, $name) {
  return Database::getConnection()->schema()->dropIndex($table, $name);
}

/**
 * Changes a field definition.
 *
 * IMPORTANT NOTE: To maintain database portability, you have to explicitly
 * recreate all indices and primary keys that are using the changed field.
 *
 * That means that you have to drop all affected keys and indexes with
 * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
 * To recreate the keys and indices, pass the key definitions as the optional
 * $keys_new argument directly to db_change_field().
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
 * and you want to change foo.bar to be type serial, leaving it as the primary
 * key. The correct sequence is:
 * @code
 * db_drop_primary_key('foo');
 * db_change_field('foo', 'bar', 'bar',
 *   array('type' => 'serial', 'not null' => TRUE),
 *   array('primary key' => array('bar')));
 * @endcode
 *
 * The reasons for this are due to the different database engines:
 *
 * On PostgreSQL, changing a field definition involves adding a new field and
 * dropping an old one which causes any indices, primary keys and sequences
 * (from serial-type fields) that use the changed field to be dropped.
 *
 * On MySQL, all type 'serial' fields must be part of at least one key or index
 * as soon as they are created. You cannot use
 * db_add_{primary_key,unique_key,index}() for this purpose because the ALTER
 * TABLE command will fail to add the column without a key or index
 * specification. The solution is to use the optional $keys_new argument to
 * create the key or index at the same time as field.
 *
 * You could use db_add_{primary_key,unique_key,index}() in all cases unless you
 * are converting a field to be type serial. You can use the $keys_new argument
 * in all cases.
 *
 * @param $table
 *   Name of the table.
 * @param $field
 *   Name of the field to change.
 * @param $field_new
 *   New name for the field (set to the same as $field if you don't want to
 *   change the name).
 * @param $spec
 *   The field specification for the new field.
 * @param $keys_new
 *   (optional) Keys and indexes specification to be created on the table along
 *   with changing the field. The format is the same as a table specification
 *   but without the 'fields' element.
 */
function db_change_field($table, $field, $field_new, $spec, $keys_new = array()) {
  return Database::getConnection()->schema()->changeField($table, $field, $field_new, $spec, $keys_new);
}

/**
 * @} End of "addtogroup schemaapi".
 */

/**
 * Sets a session variable specifying the lag time for ignoring a replica
 * server (A replica server is traditionally referred to as
 * a "slave" in database server documentation).
 * @see https://www.drupal.org/node/2275877
 */
function db_ignore_replica() {
  $connection_info = Database::getConnectionInfo();
  // Only set ignore_replica_server if there are replica servers being used,
  // which is assumed if there are more than one.
  if (count($connection_info) > 1) {
    // Five minutes is long enough to allow the replica to break and resume
    // interrupted replication without causing problems on the Drupal site from
    // the old data.
    $duration = Settings::get('maximum_replication_lag', 300);
    // Set session variable with amount of time to delay before using replica.
    $_SESSION['ignore_replica_server'] = REQUEST_TIME + $duration;
  }
}
