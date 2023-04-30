<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Query\PlaceholderInterface;

/**
 * Provides a base implementation for Database Schema.
 */
abstract class Schema implements PlaceholderInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The placeholder counter.
   *
   * @var int
   */
  protected $placeholder = 0;

  /**
   * Definition of prefixInfo array structure.
   *
   * Rather than redefining DatabaseSchema::getPrefixInfo() for each driver,
   * by defining the defaultSchema variable only MySQL has to re-write the
   * method.
   *
   * @see DatabaseSchema::getPrefixInfo()
   *
   * @var string
   */
  protected $defaultSchema = 'public';

  /**
   * A unique identifier for this query object.
   *
   * @var string
   */
  protected $uniqueIdentifier;

  public function __construct($connection) {
    $this->uniqueIdentifier = uniqid('', TRUE);
    $this->connection = $connection;
  }

  /**
   * Implements the magic __clone function.
   */
  public function __clone() {
    $this->uniqueIdentifier = uniqid('', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function uniqueIdentifier() {
    return $this->uniqueIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function nextPlaceholder() {
    return $this->placeholder++;
  }

  /**
   * Get information about the table name and schema from the prefix.
   *
   * @param string $table
   *   Name of table to look prefix up for. Defaults to 'default' because that's
   *   default key for prefix.
   * @param bool $add_prefix
   *   Boolean that indicates whether the given table name should be prefixed.
   *
   * @return array
   *   A keyed array with information about the schema, table name and prefix.
   */
  protected function getPrefixInfo($table = 'default', $add_prefix = TRUE) {
    $info = [
      'schema' => $this->defaultSchema,
      'prefix' => $this->connection->tablePrefix($table),
    ];
    if ($add_prefix) {
      $table = $info['prefix'] . $table;
    }
    // If the prefix contains a period in it, then that means the prefix also
    // contains a schema reference in which case we will change the schema key
    // to the value before the period in the prefix. Everything after the dot
    // will be prefixed onto the front of the table.
    if (($pos = strpos($table, '.')) !== FALSE) {
      // Grab everything before the period.
      $info['schema'] = substr($table, 0, $pos);
      // Grab everything after the dot.
      $info['table'] = substr($table, ++$pos);
    }
    else {
      $info['table'] = $table;
    }
    return $info;
  }

  /**
   * Create names for indexes, primary keys and constraints.
   *
   * This prevents using {} around non-table names like indexes and keys.
   */
  public function prefixNonTable($table) {
    $args = func_get_args();
    $info = $this->getPrefixInfo($table);
    $args[0] = $info['table'];
    return implode('_', $args);
  }

  /**
   * Build a condition to match a table name against a standard information_schema.
   *
   * The information_schema is a SQL standard that provides information about the
   * database server and the databases, schemas, tables, columns and users within
   * it. This makes information_schema a useful tool to use across the drupal
   * database drivers and is used by a few different functions. The function below
   * describes the conditions to be meet when querying information_schema.tables
   * for drupal tables or information associated with drupal tables. Even though
   * this is the standard method, not all databases follow standards and so this
   * method should be overwritten by a database driver if the database provider
   * uses alternate methods. Because information_schema.tables is used in a few
   * different functions, a database driver will only need to override this function
   * to make all the others work. For example see
   * core/includes/databases/mysql/schema.inc.
   *
   * @param $table_name
   *   The name of the table in question.
   * @param $operator
   *   The operator to apply on the 'table' part of the condition.
   * @param $add_prefix
   *   Boolean to indicate whether the table name needs to be prefixed.
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   A Condition object.
   */
  protected function buildTableNameCondition($table_name, $operator = '=', $add_prefix = TRUE) {
    $info = $this->connection->getConnectionOptions();

    // Retrieve the table name and schema
    $table_info = $this->getPrefixInfo($table_name, $add_prefix);

    $condition = $this->connection->condition('AND');
    $condition->condition('table_catalog', $info['database']);
    $condition->condition('table_schema', $table_info['schema']);
    $condition->condition('table_name', $table_info['table'], $operator);
    return $condition;
  }

  /**
   * Check if a table exists.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   *
   * @return bool
   *   TRUE if the given table exists, otherwise FALSE.
   */
  public function tableExists($table) {
    $condition = $this->buildTableNameCondition($table);
    $condition->compile($this->connection, $this);
    // Normally, we would heartily discourage the use of string
    // concatenation for conditionals like this however, we
    // couldn't use \Drupal::database()->select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.tables table.
    return (bool) $this->connection->query("SELECT 1 FROM information_schema.tables WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

  /**
   * Finds all tables that are like the specified base table name.
   *
   * @param string $table_expression
   *   A case-insensitive pattern against which table names are compared. Both
   *   '_' and '%' are treated like wildcards in MySQL 'LIKE' expressions, where
   *   '_' matches any single character and '%' matches an arbitrary number of
   *   characters (including zero characters). So 'foo%bar' matches table names
   *   like 'foobar', 'fooXBar', 'fooXBaR',  or 'fooXxBar'; whereas 'foo_bar'
   *   matches 'fooXBar' and 'fooXBaR' but not 'fooBar' or 'fooXxxBar'.
   *
   * @return array
   *   Both the keys and the values are the matching tables.
   */
  public function findTables($table_expression) {
    // Load all the tables up front in order to take into account per-table
    // prefixes. The actual matching is done at the bottom of the method.
    $condition = $this->buildTableNameCondition('%', 'LIKE');
    $condition->compile($this->connection, $this);

    $individually_prefixed_tables = $this->connection->getUnprefixedTablesMap();
    $default_prefix = $this->connection->tablePrefix();
    $default_prefix_length = strlen($default_prefix);
    $tables = [];
    // Normally, we would heartily discourage the use of string
    // concatenation for conditionals like this however, we
    // couldn't use \Drupal::database()->select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.tables table.
    $results = $this->connection->query("SELECT table_name AS table_name FROM information_schema.tables WHERE " . (string) $condition, $condition->arguments());
    foreach ($results as $table) {
      // Take into account tables that have an individual prefix.
      if (isset($individually_prefixed_tables[$table->table_name])) {
        $prefix_length = strlen($this->connection->tablePrefix($individually_prefixed_tables[$table->table_name]));
      }
      elseif ($default_prefix && substr($table->table_name, 0, $default_prefix_length) !== $default_prefix) {
        // This table name does not start the default prefix, which means that
        // it is not managed by Drupal so it should be excluded from the result.
        continue;
      }
      else {
        $prefix_length = $default_prefix_length;
      }

      // Remove the prefix from the returned tables.
      $unprefixed_table_name = substr($table->table_name, $prefix_length);

      // The pattern can match a table which is the same as the prefix. That
      // will become an empty string when we remove the prefix, which will
      // probably surprise the caller, besides not being a prefixed table. So
      // remove it.
      if (!empty($unprefixed_table_name)) {
        $tables[$unprefixed_table_name] = $unprefixed_table_name;
      }
    }

    // Convert the table expression from its SQL LIKE syntax to a regular
    // expression and escape the delimiter that will be used for matching.
    $table_expression = str_replace(['%', '_'], ['.*?', '.'], preg_quote($table_expression, '/'));
    $tables = preg_grep('/^' . $table_expression . '$/i', $tables);

    return $tables;
  }

  /**
   * Check if a column exists in the given table.
   *
   * @param string $table
   *   The name of the table in drupal (no prefixing).
   * @param string $column
   *   The name of the column.
   *
   * @return bool
   *   TRUE if the given column exists, otherwise FALSE.
   */
  public function fieldExists($table, $column) {
    $condition = $this->buildTableNameCondition($table);
    $condition->condition('column_name', $column);
    $condition->compile($this->connection, $this);
    // Normally, we would heartily discourage the use of string
    // concatenation for conditionals like this however, we
    // couldn't use \Drupal::database()->select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.columns table.
    return (bool) $this->connection->query("SELECT 1 FROM information_schema.columns WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

  /**
   * Returns a mapping of Drupal schema field names to DB-native field types.
   *
   * Because different field types do not map 1:1 between databases, Drupal has
   * its own normalized field type names. This function returns a driver-specific
   * mapping table from Drupal names to the native names for each database.
   *
   * @return array
   *   An array of Schema API field types to driver-specific field types.
   */
  abstract public function getFieldTypeMap();

  /**
   * Rename a table.
   *
   * @param $table
   *   The table to be renamed.
   * @param $new_name
   *   The new name for the table.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If a table with the specified new name already exists.
   */
  abstract public function renameTable($table, $new_name);

  /**
   * Drop a table.
   *
   * @param $table
   *   The table to be dropped.
   *
   * @return bool
   *   TRUE if the table was successfully dropped, FALSE if there was no table
   *   by that name to begin with.
   */
  abstract public function dropTable($table);

  /**
   * Add a new field to a table.
   *
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
   *   Alternatively, the 'initial_from_field' key may be used, which will
   *   auto-populate the new field with values from the specified field.
   * @param $keys_new
   *   (optional) Keys and indexes specification to be created on the
   *   table along with adding the field. The format is the same as a
   *   table specification but without the 'fields' element. If you are
   *   adding a type 'serial' field, you MUST specify at least one key
   *   or index including it in this array. See ::changeField() for more
   *   explanation why.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a field by that name.
   */
  abstract public function addField($table, $field, $spec, $keys_new = []);

  /**
   * Drop a field.
   *
   * @param $table
   *   The table to be altered.
   * @param $field
   *   The field to be dropped.
   *
   * @return bool
   *   TRUE if the field was successfully dropped, FALSE if there was no field
   *   by that name to begin with.
   */
  abstract public function dropField($table, $field);

  /**
   * Checks if an index exists in the given table.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   * @param $name
   *   The name of the index in drupal (no prefixing).
   *
   * @return bool
   *   TRUE if the given index exists, otherwise FALSE.
   */
  abstract public function indexExists($table, $name);

  /**
   * Add a primary key.
   *
   * @param $table
   *   The table to be altered.
   * @param $fields
   *   Fields for the primary key.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a primary key.
   */
  abstract public function addPrimaryKey($table, $fields);

  /**
   * Drop the primary key.
   *
   * @param $table
   *   The table to be altered.
   *
   * @return bool
   *   TRUE if the primary key was successfully dropped, FALSE if there was no
   *   primary key on this table to begin with.
   */
  abstract public function dropPrimaryKey($table);

  /**
   * Finds the primary key columns of a table, from the database.
   *
   * @param string $table
   *   The name of the table.
   *
   * @return string[]|false
   *   A simple array with the names of the columns composing the table's
   *   primary key, or FALSE if the table does not exist.
   *
   * @throws \RuntimeException
   *   If the driver does not override this method.
   */
  protected function findPrimaryKeyColumns($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    throw new \RuntimeException("The '" . $this->connection->driver() . "' database driver does not implement " . __METHOD__);
  }

  /**
   * Add a unique key.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   * @param $fields
   *   An array of field names.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has a key by that name.
   */
  abstract public function addUniqueKey($table, $name, $fields);

  /**
   * Drop a unique key.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the key.
   *
   * @return bool
   *   TRUE if the key was successfully dropped, FALSE if there was no key by
   *   that name to begin with.
   */
  abstract public function dropUniqueKey($table, $name);

  /**
   * Add an index.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   * @param $fields
   *   An array of field names or field information; if field information is
   *   passed, it's an array whose first element is the field name and whose
   *   second is the maximum length in the index. For example, the following
   *   will use the full length of the `foo` field, but limit the `bar` field to
   *   4 characters:
   *   @code
   *     $fields = ['foo', ['bar', 4]];
   *   @endcode
   * @param array $spec
   *   The table specification for the table to be altered. This is used in
   *   order to be able to ensure that the index length is not too long.
   *   This schema definition can usually be obtained through hook_schema(), or
   *   in case the table was created by the Entity API, through the schema
   *   handler listed in the entity class definition. For reference, see
   *   SqlContentEntityStorageSchema::getDedicatedTableSchema() and
   *   SqlContentEntityStorageSchema::getSharedTableFieldSchema().
   *
   *   In order to prevent human error, it is recommended to pass in the
   *   complete table specification. However, in the edge case of the complete
   *   table specification not being available, we can pass in a partial table
   *   definition containing only the fields that apply to the index:
   *   @code
   *   $spec = [
   *     // Example partial specification for a table:
   *     'fields' => [
   *       'example_field' => [
   *         'description' => 'An example field',
   *         'type' => 'varchar',
   *         'length' => 32,
   *         'not null' => TRUE,
   *         'default' => '',
   *       ],
   *     ],
   *     'indexes' => [
   *       'table_example_field' => ['example_field'],
   *     ],
   *   ];
   *   @endcode
   *   Note that the above is a partial table definition and that we would
   *   usually pass a complete table definition as obtained through
   *   hook_schema() instead.
   *
   * @see schemaapi
   * @see hook_schema()
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already has an index by that name.
   *
   * @todo remove the $spec argument whenever schema introspection is added.
   */
  abstract public function addIndex($table, $name, $fields, array $spec);

  /**
   * Drop an index.
   *
   * @param $table
   *   The table to be altered.
   * @param $name
   *   The name of the index.
   *
   * @return bool
   *   TRUE if the index was successfully dropped, FALSE if there was no index
   *   by that name to begin with.
   */
  abstract public function dropIndex($table, $name);

  /**
   * Finds the columns for the primary key, unique keys and indexes of a table.
   *
   * @param string $table
   *   The name of the table.
   *
   * @return array
   *   A schema array with the following keys: 'primary key', 'unique keys' and
   *   'indexes', and values as arrays of database columns.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table doesn't exist.
   * @throws \RuntimeException
   *   If the driver does not implement this method.
   */
  protected function introspectIndexSchema($table) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("The table $table doesn't exist.");
    }
    throw new \RuntimeException("The '{$this->connection->driver()}' database driver does not implement " . __METHOD__);
  }

  /**
   * Change a field definition.
   *
   * IMPORTANT NOTE: To maintain database portability, you have to explicitly
   * recreate all indices and primary keys that are using the changed field.
   *
   * That means that you have to drop all affected keys and indexes with
   * Schema::dropPrimaryKey(), Schema::dropUniqueKey(), or Schema::dropIndex()
   * before calling ::changeField().
   * To recreate the keys and indices, pass the key definitions as the
   * optional $keys_new argument directly to ::changeField().
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
   * primary key. The correct sequence is:
   * @code
   * $injected_database->schema()->dropPrimaryKey('foo');
   * $injected_database->schema()->changeField('foo', 'bar', 'bar',
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
   * or index as soon as they are created. You cannot use
   * Schema::addPrimaryKey, Schema::addUniqueKey(), or Schema::addIndex()
   * for this purpose because the ALTER TABLE command will fail to add
   * the column without a key or index specification.
   * The solution is to use the optional $keys_new argument to create the key
   * or index at the same time as field.
   *
   * You could use Schema::addPrimaryKey, Schema::addUniqueKey(), or
   * Schema::addIndex() in all cases unless you are converting a field to
   * be type serial. You can use the $keys_new argument in all cases.
   *
   * @param $table
   *   Name of the table.
   * @param $field
   *   Name of the field to change.
   * @param $field_new
   *   New name for the field (set to the same as $field if you don't want to change the name).
   * @param $spec
   *   The field specification for the new field.
   * @param $keys_new
   *   (optional) Keys and indexes specification to be created on the
   *   table along with changing the field. The format is the same as a
   *   table specification but without the 'fields' element.
   *
   * @throws \Drupal\Core\Database\SchemaObjectDoesNotExistException
   *   If the specified table or source field doesn't exist.
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified destination field already exists.
   */
  abstract public function changeField($table, $field, $field_new, $spec, $keys_new = []);

  /**
   * Create a new table from a Drupal table definition.
   *
   * @param $name
   *   The name of the table to create.
   * @param $table
   *   A Schema API table definition array.
   *
   * @throws \Drupal\Core\Database\SchemaObjectExistsException
   *   If the specified table already exists.
   */
  public function createTable($name, $table) {
    if ($this->tableExists($name)) {
      throw new SchemaObjectExistsException("Table '$name' already exists.");
    }
    $statements = $this->createTableSql($name, $table);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  /**
   * Return an array of field names from an array of key/index column specifiers.
   *
   * This is usually an identity function but if a key/index uses a column prefix
   * specification, this function extracts just the name.
   *
   * @param $fields
   *   An array of key/index column specifiers.
   *
   * @return array
   *   An array of field names.
   */
  public function fieldNames($fields) {
    $return = [];
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = $field[0];
      }
      else {
        $return[] = $field;
      }
    }
    return $return;
  }

  /**
   * Prepare a table or column comment for database query.
   *
   * @param $comment
   *   The comment string to prepare.
   * @param $length
   *   Optional upper limit on the returned string length.
   *
   * @return string
   *   The prepared comment.
   */
  public function prepareComment($comment, $length = NULL) {
    // Remove semicolons to avoid triggering multi-statement check.
    $comment = strtr($comment, [';' => '.']);
    return $this->connection->quote($comment);
  }

  /**
   * Escapes a value to be used as the default value on a column.
   *
   * @param mixed $value
   *   The value to be escaped (int, float, null or string).
   *
   * @return string|int|float
   *   The escaped value.
   */
  protected function escapeDefaultValue($value) {
    if (is_null($value)) {
      return 'NULL';
    }
    return is_string($value) ? $this->connection->quote($value) : $value;
  }

  /**
   * Ensures that all the primary key fields are correctly defined.
   *
   * @param array $primary_key
   *   An array containing the fields that will form the primary key of a table.
   * @param array $fields
   *   An array containing the field specifications of the table, as per the
   *   schema data structure format.
   *
   * @throws \Drupal\Core\Database\SchemaException
   *   Thrown if any primary key field specification does not exist or if they
   *   do not define 'not null' as TRUE.
   */
  protected function ensureNotNullPrimaryKey(array $primary_key, array $fields) {
    foreach (array_intersect($primary_key, array_keys($fields)) as $field_name) {
      if (!isset($fields[$field_name]['not null']) || $fields[$field_name]['not null'] !== TRUE) {
        throw new SchemaException("The '$field_name' field specification does not define 'not null' as TRUE.");
      }
    }
  }

}
