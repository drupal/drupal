<?php

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * @file
 * Core systems for the database layer.
 *
 * Classes required for basic functioning of the database system should be
 * placed in this file.  All utility functions should also be placed in this
 * file only, as they cannot auto-load the way classes can.
 */

/**
 * @defgroup database Database abstraction layer
 * @{
 * Allow the use of different database servers using the same code base.
 *
 * Drupal provides a database abstraction layer to provide developers with
 * the ability to support multiple database servers easily. The intent of
 * this layer is to preserve the syntax and power of SQL as much as possible,
 * but also allow developers a way to leverage more complex functionality in
 * a unified way. It also provides a structured interface for dynamically
 * constructing queries when appropriate, and enforcing security checks and
 * similar good practices.
 *
 * The system is built atop PHP's PDO (PHP Data Objects) database API and
 * inherits much of its syntax and semantics.
 *
 * Most Drupal database SELECT queries are performed by a call to db_query() or
 * db_query_range(). Module authors should also consider using the
 * Drupal\Core\Database\Query\PagerSelectExtender for queries that return
 * results that need to be presented on multiple pages
 * (see https://drupal.org/node/508796), and the
 * Drupal\Core\Database\Query\TableSortExtender for generating appropriate
 * queries for sortable tables (see https://drupal.org/node/1848372).
 *
 * For example, one might wish to return a list of the most recent 10 rows
 * authored by a given user. Instead of directly issuing the SQL query
 * @code
 * SELECT e.id, e.title, e.created FROM example e WHERE e.uid = $uid
 *   ORDER BY e.created DESC LIMIT 0, 10;
 * @endcode
 * one would instead call the Drupal functions:
 * @code
 * $result = db_query_range('SELECT e.id, e.title, e.created
 *   FROM {example} e WHERE e.uid = :uid
 *   ORDER BY e.created DESC', 0, 10, array(':uid' => $uid));
 * foreach ($result as $record) {
 *   // Perform operations on $record->title, etc. here.
 * }
 * @endcode
 * Curly braces are used around "example" to provide table prefixing via
 * DatabaseConnection::prefixTables(). The explicit use of a user ID is pulled
 * out into an argument passed to db_query() so that SQL injection attacks
 * from user input can be caught and nullified. The LIMIT syntax varies between
 * database servers, so that is abstracted into db_query_range() arguments.
 * Finally, note the PDO-based ability to iterate over the result set using
 * foreach ().
 *
 * All queries are passed as a prepared statement string. A
 * prepared statement is a "template" of a query that omits literal or variable
 * values in favor of placeholders. The values to place into those
 * placeholders are passed separately, and the database driver handles
 * inserting the values into the query in a secure fashion. That means you
 * should never quote or string-escape a value to be inserted into the query.
 *
 * There are two formats for placeholders: named and unnamed. Named placeholders
 * are strongly preferred in all cases as they are more flexible and
 * self-documenting. Named placeholders should start with a colon ":" and can be
 * followed by one or more letters, numbers or underscores.
 *
 * Named placeholders begin with a colon followed by a unique string. Example:
 * @code
 * SELECT id, title FROM {example} WHERE uid=:uid;
 * @endcode
 *
 * ":uid" is a placeholder that will be replaced with a literal value when
 * the query is executed. A given placeholder label cannot be repeated in a
 * given query, even if the value should be the same. When using named
 * placeholders, the array of arguments to the query must be an associative
 * array where keys are a placeholder label (e.g., :uid) and the value is the
 * corresponding value to use. The array may be in any order.
 *
 * Unnamed placeholders are simply a question mark. Example:
 * @code
 * SELECT id, title FROM {example} WHERE uid=?;
 * @endcode
 *
 * In this case, the array of arguments must be an indexed array of values to
 * use in the exact same order as the placeholders in the query.
 *
 * Note that placeholders should be a "complete" value. For example, when
 * running a LIKE query the SQL wildcard character, %, should be part of the
 * value, not the query itself. Thus, the following is incorrect:
 * @code
 * SELECT id, title FROM {example} WHERE title LIKE :title%;
 * @endcode
 * It should instead read:
 * @code
 * SELECT id, title FROM {example} WHERE title LIKE :title;
 * @endcode
 * and the value for :title should include a % as appropriate. Again, note the
 * lack of quotation marks around :title. Because the value is not inserted
 * into the query as one big string but as an explicitly separate value, the
 * database server knows where the query ends and a value begins. That is
 * considerably more secure against SQL injection than trying to remember
 * which values need quotation marks and string escaping and which don't.
 *
 * INSERT, UPDATE, and DELETE queries need special care in order to behave
 * consistently across all different databases. Therefore, they use a special
 * object-oriented API for defining a query structurally. For example, rather
 * than:
 * @code
 * INSERT INTO {example} (id, uid, path, name) VALUES (1, 2, 'path', 'Name');
 * @endcode
 * one would instead write:
 * @code
 * $fields = array('id' => 1, 'uid' => 2, 'path' => 'path', 'name' => 'Name');
 * db_insert('example')
 *   ->fields($fields)
 *   ->execute();
 * @endcode
 * This method allows databases that need special data type handling to do so,
 * while also allowing optimizations such as multi-insert queries. UPDATE and
 * DELETE queries have a similar pattern.
 *
 * Drupal also supports transactions, including a transparent fallback for
 * databases that do not support transactions. To start a new transaction,
 * simply call $txn = db_transaction(); in your own code. The transaction will
 * remain open for as long as the variable $txn remains in scope.  When $txn is
 * destroyed, the transaction will be committed.  If your transaction is nested
 * inside of another then Drupal will track each transaction and only commit
 * the outer-most transaction when the last transaction object goes out out of
 * scope, that is, all relevant queries completed successfully.
 *
 * Example:
 * @code
 * function my_transaction_function() {
 *   // The transaction opens here.
 *   $txn = db_transaction();
 *
 *   try {
 *     $id = db_insert('example')
 *       ->fields(array(
 *         'field1' => 'mystring',
 *         'field2' => 5,
 *       ))
 *       ->execute();
 *
 *     my_other_function($id);
 *
 *     return $id;
 *   }
 *   catch (Exception $e) {
 *     // Something went wrong somewhere, so roll back now.
 *     $txn->rollback();
 *     // Log the exception to watchdog.
 *     watchdog_exception('type', $e);
 *   }
 *
 *   // $txn goes out of scope here.  Unless the transaction was rolled back, it
 *   // gets automatically committed here.
 * }
 *
 * function my_other_function($id) {
 *   // The transaction is still open here.
 *
 *   if ($id % 2 == 0) {
 *     db_update('example')
 *       ->condition('id', $id)
 *       ->fields(array('field2' => 10))
 *       ->execute();
 *   }
 * }
 * @endcode
 *
 * @see http://drupal.org/developing/api/database
 */


/**
 * The following utility functions are simply convenience wrappers.
 *
 * They should never, ever have any database-specific code in them.
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
 *   more self-documenting.
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
 * Executes a query string and saves the result set to a temporary table.
 *
 * The execution of the query string happens against the active database.
 *
 * @param $query
 *   The prepared statement query to run. Although it will accept both named and
 *   unnamed placeholders, named placeholders are strongly preferred as they are
 *   more self-documenting.
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
  if (empty($options['target']) || $options['target'] == 'slave') {
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
  if (empty($options['target']) || $options['target'] == 'slave') {
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
  if (empty($options['target']) || $options['target'] == 'slave') {
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
  if (empty($options['target']) || $options['target'] == 'slave') {
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
  if (empty($options['target']) || $options['target'] == 'slave') {
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
 *   The alias for the base table of this query.
 * @param $options
 *   An array of options to control how the query operates.
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
 * @} End of "defgroup database".
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
 * Copies the structure of a table.
 *
 * @param string $source
 *   The name of the table to be copied.
 * @param string $destination
 *   The name for the new table.
 *
 * @return \Drupal\Core\Database\StatementInterface
 *   The result of the executed query.
 *
 * @see \Drupal\Core\Database\Schema::copyTable()
 */
function db_copy_table_schema($source, $destination) {
  return Database::getConnection()->schema()->copyTable($source, $destination);
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
 *   Optional keys and indexes specification to be created on the table along
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
 *   Optional keys and indexes specification to be created on the table along
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
 * Sets a session variable specifying the lag time for ignoring a slave server.
 */
function db_ignore_slave() {
  $connection_info = Database::getConnectionInfo();
  // Only set ignore_slave_server if there are slave servers being used, which
  // is assumed if there are more than one.
  if (count($connection_info) > 1) {
    // Five minutes is long enough to allow the slave to break and resume
    // interrupted replication without causing problems on the Drupal site from
    // the old data.
    $duration = settings()->get('maximum_replication_lag', 300);
    // Set session variable with amount of time to delay before using slave.
    $_SESSION['ignore_slave_server'] = REQUEST_TIME + $duration;
  }
}
