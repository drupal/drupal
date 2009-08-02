<?php
// $Id$

/**
 * @file
 * Generic Database schema code.
 */

/**
 * @defgroup schemaapi Schema API
 * @{
 *
 * A Drupal schema definition is an array structure representing one or
 * more tables and their related keys and indexes. A schema is defined by
 * hook_schema(), which usually lives in a modulename.install file.
 *
 * By implementing hook_schema() and specifying the tables your module
 * declares, you can easily create and drop these tables on all
 * supported database engines. You don't have to deal with the
 * different SQL dialects for table creation and alteration of the
 * supported database engines.
 *
 * hook_schema() should return an array with a key for each table that
 * the module defines.
 *
 * The following keys are defined:
 *
 *   - 'description': A string in non-markup plain text describing this table
 *     and its purpose. References to other tables should be enclosed in
 *     curly-brackets. For example, the node_revisions table
 *     description field might contain "Stores per-revision title and
 *     body data for each {node}."
 *   - 'fields': An associative array ('fieldname' => specification)
 *     that describes the table's database columns. The specification
 *     is also an array. The following specification parameters are defined:
 *
 *     - 'description': A string in non-markup plain text describing this field
 *       and its purpose. References to other tables should be enclosed in
 *       curly-brackets. For example, the node table vid field
 *       description might contain "Always holds the largest (most
 *       recent) {node_revision}.vid value for this nid."
 *     - 'type': The generic datatype: 'varchar', 'int', 'serial'
 *       'float', 'numeric', 'text', 'blob' or 'datetime'. Most types
 *       just map to the according database engine specific
 *       datatypes. Use 'serial' for auto incrementing fields. This
 *       will expand to 'int auto_increment' on mysql.
 *     - 'serialize': A boolean indicating whether the field will be stored as
 *       a serialized string.
 *     - 'size': The data size: 'tiny', 'small', 'medium', 'normal',
 *       'big'. This is a hint about the largest value the field will
 *       store and determines which of the database engine specific
 *       datatypes will be used (e.g. on MySQL, TINYINT vs. INT vs. BIGINT).
 *       'normal', the default, selects the base type (e.g. on MySQL,
 *       INT, VARCHAR, BLOB, etc.).
 *
 *       Not all sizes are available for all data types. See
 *       db_type_map() for possible combinations.
 *     - 'not null': If true, no NULL values will be allowed in this
 *       database column. Defaults to false.
 *     - 'default': The field's default value. The PHP type of the
 *       value matters: '', '0', and 0 are all different. If you
 *       specify '0' as the default value for a type 'int' field it
 *       will not work because '0' is a string containing the
 *       character "zero", not an integer.
 *     - 'length': The maximal length of a type 'char', 'varchar' or 'text'
 *       field. Ignored for other field types.
 *     - 'unsigned': A boolean indicating whether a type 'int', 'float'
 *       and 'numeric' only is signed or unsigned. Defaults to
 *       FALSE. Ignored for other field types.
 *     - 'precision', 'scale': For type 'numeric' fields, indicates
 *       the precision (total number of significant digits) and scale
 *       (decimal digits right of the decimal point). Both values are
 *       mandatory. Ignored for other field types.
 *
 *     All parameters apart from 'type' are optional except that type
 *     'numeric' columns must specify 'precision' and 'scale'.
 *
 *  - 'primary key': An array of one or more key column specifiers (see below)
 *    that form the primary key.
 *  - 'unique keys': An associative array of unique keys ('keyname' =>
 *    specification). Each specification is an array of one or more
 *    key column specifiers (see below) that form a unique key on the table.
 *  - 'foreign keys': An associative array, each key references a column
 *    of the local table, each value is an array with a single key pair as
 *    'tablename' => 'column' where 'column' is the foreign column to
 *    reference.
 *  - 'indexes':  An associative array of indexes ('indexame' =>
 *    specification). Each specification is an array of one or more
 *    key column specifiers (see below) that form an index on the
 *    table.
 *
 * A key column specifier is either a string naming a column or an
 * array of two elements, column name and length, specifying a prefix
 * of the named column.
 *
 * As an example, here is a SUBSET of the schema definition for
 * Drupal's 'node' table. It show four fields (nid, vid, type, and
 * title), the primary key on field 'nid', a unique key named 'vid' on
 * field 'vid', and two indexes, one named 'nid' on field 'nid' and
 * one named 'node_title_type' on the field 'title' and the first four
 * bytes of the field 'type':
 *
 * @code
 * $schema['node'] = array(
 *   'description' => 'The base table for nodes.',
 *   'fields' => array(
 *     'nid'       => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE),
 *     'vid'       => array('type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE,'default' => 0),
 *     'type'      => array('type' => 'varchar','length' => 32,'not null' => TRUE, 'default' => ''),
 *     'language'  => array('type' => 'varchar','length' => 12,'not null' => TRUE,'default' => ''),
 *     'title'     => array('type' => 'varchar','length' => 255,'not null' => TRUE, 'default' => ''),
 *     'uid'       => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'status'    => array('type' => 'int', 'not null' => TRUE, 'default' => 1),
 *     'created'   => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'changed'   => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'comment'   => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'promote'   => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'moderate'  => array('type' => 'int', 'not null' => TRUE,'default' => 0),
 *     'sticky'    => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *     'tnid'      => array('type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0),
 *     'translate' => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
 *   ),
 *   'indexes' => array(
 *     'node_changed'        => array('changed'),
 *     'node_created'        => array('created'),
 *     'node_moderate'       => array('moderate'),
 *     'node_frontpage'      => array('promote', 'status', 'sticky', 'created'),
 *     'node_status_type'    => array('status', 'type', 'nid'),
 *     'node_title_type'     => array('title', array('type', 4)),
 *     'node_type'           => array(array('type', 4)),
 *     'uid'                 => array('uid'),
 *     'tnid'                => array('tnid'),
 *     'translate'           => array('translate'),
 *   ),
 *   'unique keys' => array(
 *     'vid' => array('vid'),
 *   ),
 *   'foreign keys' => array(
 *      'vid' => array('node_revision' => 'vid'),
 *      'uid' => array('users' => 'uid'),
 *    ),
 *   'primary key' => array('nid'),
 * );
 * @endcode
 *
 * @see drupal_install_schema()
 */

abstract class DatabaseSchema {

  protected $connection;

  public function __construct($connection) {
    $this->connection = $connection;
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
   * to make all the others work. For example see includes/databases/mysql/schema.inc.
   *
   * @param $table_name
   *   The name of the table to explode.
   * @param $operator
   *   The operator to apply on the 'table' part of the condition.
   * @return
   *   A DatabaseCondition object.
   */
  protected function buildTableNameCondition($table_name, $operator = '=') {
    $info = Database::getConnectionInfo();

    // The table name may describe the schema eg. schema.table.
    if (strpos($table_name, '.')) {
      list($schema, $table_name) = explode('.', $table_name);
    }
    else {
      $schema = 'public';
    }

    $condition = new DatabaseCondition('AND');
    $condition->condition('table_catalog', $info['default']['database']);
    $condition->condition('table_schema', $schema);
    $condition->condition('table_name', $table_name, $operator);
    return $condition;
  }

  /**
   * Check if a table exists.
   *
   * @param $table
   *   The name of the table in drupal (no prefixing).
   * @return
   *   false is no table exists otherwise the actual table name.
   */
  public function tableExists($table) {
    $condition = $this->buildTableNameCondition($this->connection->prefixTables('{' . $table . '}'));
    $condition->compile($this->connection);
    // Normally, we would heartily discourage the use of string
    // concatination for conditionals like this however, we
    // couldn't use db_select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.tables table.
    return db_query("SELECT table_name FROM information_schema.tables WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

  /**
   * Find all tables that are like the specified base table name.
   *
   * @param $table_expression
   *   An SQL expression, for example "simpletest%" (without the quotes).
   *   BEWARE: this is not prefixed, the caller should take care of that.
   * @return
   *   Array, both the keys and the values are the matching tables.
   */
  public function findTables($table_expression) {
    $condition = $this->buildTableNameCondition($table_expression, 'LIKE');
    $condition->compile($this->connection);
    // Normally, we would heartily discourage the use of string
    // concatination for conditionals like this however, we
    // couldn't use db_select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.tables table.
    return db_query("SELECT table_name FROM information_schema.tables WHERE " . (string) $condition, $condition->arguments())->fetchAllKeyed(0, 0);
  }

  /**
   * Check if a column exists in the given table.
   */
  public function columnExists($table, $column) {
    $condition = $this->buildTableNameCondition($this->connection->prefixTables('{' . $table . '}'));
    $condition->condition('column_name', $column);
    $condition->compile($this->connection);
    // Normally, we would heartily discourage the use of string
    // concatination for conditionals like this however, we
    // couldn't use db_select() here because it would prefix
    // information_schema.tables and the query would fail.
    // Don't use {} around information_schema.columns table.
    return db_query("SELECT column_name FROM information_schema.columns WHERE " . (string) $condition, $condition->arguments())->fetchAllKeyed(0, 0);
  }

  /**
   * This maps a generic data type in combination with its data size
   * to the engine-specific data type.
   */
  abstract public function getFieldTypeMap();

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
  abstract public function renameTable(&$ret, $table, $new_name);

  /**
   * Drop a table.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be dropped.
   */
  abstract public function dropTable(&$ret, $table);

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
   * @param $keys_new
   *   Optional keys and indexes specification to be created on the
   *   table along with adding the field. The format is the same as a
   *   table specification but without the 'fields' element. If you are
   *   adding a type 'serial' field, you MUST specify at least one key
   *   or index including it in this array. @see db_change_field for more
   *   explanation why.
   */
  abstract public function addField(&$ret, $table, $field, $spec, $keys_new = array());

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
  abstract public function dropField(&$ret, $table, $field);

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
  abstract public function fieldSetDefault(&$ret, $table, $field, $default);

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
  abstract public function fieldSetNoDefault(&$ret, $table, $field);

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
  abstract public function addPrimaryKey(&$ret, $table, $fields);

  /**
   * Drop the primary key.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $table
   *   The table to be altered.
   */
  abstract public function dropPrimaryKey(&$ret, $table);

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
  abstract public function addUniqueKey(&$ret, $table, $name, $fields);

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
  abstract public function dropUniqueKey(&$ret, $table, $name);

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
  abstract public function addIndex(&$ret, $table, $name, $fields);

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
  abstract public function dropIndex(&$ret, $table, $name);


  /**
   * Change a field definition.
   *
   * IMPORTANT NOTE: To maintain database portability, you have to explicitly
   * recreate all indices and primary keys that are using the changed field.
   *
   * That means that you have to drop all affected keys and indexes with
   * db_drop_{primary_key,unique_key,index}() before calling db_change_field().
   * To recreate the keys and indices, pass the key definitions as the
   * optional $keys_new argument directly to db_change_field().
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
   * or index as soon as they are created. You cannot use
   * db_add_{primary_key,unique_key,index}() for this purpose because
   * the ALTER TABLE command will fail to add the column without a key
   * or index specification. The solution is to use the optional
   * $keys_new argument to create the key or index at the same time as
   * field.
   *
   * You could use db_add_{primary_key,unique_key,index}() in all cases
   * unless you are converting a field to be type serial. You can use
   * the $keys_new argument in all cases.
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
   * @param $keys_new
   *   Optional keys and indexes specification to be created on the
   *   table along with changing the field. The format is the same as a
   *   table specification but without the 'fields' element.
   */
  abstract public function changeField(&$ret, $table, $field, $field_new, $spec, $keys_new = array());

  /**
   * Create a new table from a Drupal table definition.
   *
   * @param $ret
   *   Array to which query results will be added.
   * @param $name
   *   The name of the table to create.
   * @param $table
   *   A Schema API table definition array.
   */
  public function createTable(&$ret, $name, $table) {
    $statements = $this->createTableSql($name, $table);
    foreach ($statements as $statement) {
      $ret[] = update_sql($statement);
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
   * @return
   *   An array of field names.
   */
  public function fieldNames($fields) {
    $ret = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $ret[] = $field[0];
      }
      else {
        $ret[] = $field;
      }
    }
    return $ret;
  }

  /**
   * Prepare a table or column comment for database query.
   *
   * @param $comment
   *   The comment string to prepare.
   * @param $length
   *   Optional upper limit on the returned string length.
   * @return
   *   The prepared comment.
   */
  public function prepareComment($comment, $length = NULL) {
    return $this->connection->quote($comment);
  }
}

/**
 * @} End of "defgroup schemaapi".
 */

