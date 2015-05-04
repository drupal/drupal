<?php

/**
 * @file
 * Hooks related to the Database system and the Schema API.
 */

/**
 * @defgroup database Database abstraction layer
 * @{
 * Allow the use of different database servers using the same code base.
 *
 * @section sec_intro Overview
 * Drupal's database abstraction layer provides a unified database query API
 * that can query different underlying databases. It is built upon PHP's
 * PDO (PHP Data Objects) database API, and inherits much of its syntax and
 * semantics. Besides providing a unified API for database queries, the
 * database abstraction layer also provides a structured way to construct
 * complex queries, and it protects the database by using good security
 * practices.
 *
 * For more detailed information on the database abstraction layer, see
 * https://drupal.org/developing/api/database
 *
 * @section sec_entity Querying entities
 * Any query on Drupal entities or fields should use the Entity Query API. See
 * the @link entity_api entity API topic @endlink for more information.
 *
 * @section sec_simple Simple SELECT database queries
 * For simple SELECT queries that do not involve entities, the Drupal database
 * abstraction layer provides the functions db_query() and db_query_range(),
 * which execute SELECT queries (optionally with range limits) and return result
 * sets that you can iterate over using foreach loops. (The result sets are
 * objects implementing the \Drupal\Core\Database\StatementInterface interface.)
 * You can use the simple query functions for query strings that are not
 * dynamic (except for placeholders, see below), and that you are certain will
 * work in any database engine. See @ref sec_dynamic below if you have a more
 * complex query, or a query whose syntax would be different in some databases.
 *
 * As a note, db_query() and similar functions are wrappers on connection object
 * methods. In most classes, you should use dependency injection and the
 * database connection object instead of these wrappers; See @ref sec_connection
 * below for details.
 *
 * To use the simple database query functions, you will need to make a couple of
 * modifications to your bare SQL query:
 * - Enclose your table name in {}. Drupal allows site builders to use
 *   database table name prefixes, so you cannot be sure what the actual
 *   name of the table will be. So, use the name that is in the hook_schema(),
 *   enclosed in {}, and Drupal will calculate the right name.
 * - Instead of putting values for conditions into the query, use placeholders.
 *   The placeholders are named and start with :, and they take the place of
 *   putting variables directly into the query, to protect against SQL
 *   injection attacks.
 * - LIMIT syntax differs between databases, so if you have a ranged query,
 *   use db_query_range() instead of db_query().
 *
 * For example, if the query you want to run is:
 * @code
 * SELECT e.id, e.title, e.created FROM example e WHERE e.uid = $uid
 *   ORDER BY e.created DESC LIMIT 0, 10;
 * @endcode
 * you would do it like this:
 * @code
 * $result = db_query_range('SELECT e.id, e.title, e.created
 *   FROM {example} e
 *   WHERE e.uid = :uid
 *   ORDER BY e.created DESC',
 *   0, 10, array(':uid' => $uid));
 * foreach ($result as $record) {
 *   // Perform operations on $record->title, etc. here.
 * }
 * @endcode
 *
 * Note that if your query has a string condition, like:
 * @code
 * WHERE e.my_field = 'foo'
 * @endcode
 * when you convert it to placeholders, omit the quotes:
 * @code
 * WHERE e.my_field = :my_field
 * ... array(':my_field' => 'foo') ...
 * @endcode
 *
 * @section sec_dynamic Dynamic SELECT queries
 * For SELECT queries where the simple query API described in @ref sec_simple
 * will not work well, you need to use the dynamic query API. However, you
 * should still use the Entity Query API if your query involves entities or
 * fields (see the @link entity_api Entity API topic @endlink for more on
 * entity queries).
 *
 * As a note, db_select() and similar functions are wrappers on connection
 * object methods. In most classes, you should use dependency injection and the
 * database connection object instead of these wrappers; See @ref sec_connection
 * below for details.
 *
 * The dynamic query API lets you build up a query dynamically using method
 * calls. As an illustration, the query example from @ref sec_simple above
 * would be:
 * @code
 * $result = db_select('example', 'e')
 *   ->fields('e', array('id', 'title', 'created'))
 *   ->condition('e.uid', $uid)
 *   ->orderBy('e.created', 'DESC')
 *   ->range(0, 10)
 *   ->execute();
 * @endcode
 *
 * There are also methods to join to other tables, add fields with aliases,
 * isNull() to have a @code WHERE e.foo IS NULL @endcode condition, etc. See
 * https://drupal.org/developing/api/database for many more details.
 *
 * One note on chaining: It is common in the dynamic database API to chain
 * method calls (as illustrated here), because most of the query methods modify
 * the query object and then return the modified query as their return
 * value. However, there are some important exceptions; these methods (and some
 * others) do not support chaining:
 * - join(), innerJoin(), etc.: These methods return the joined table alias.
 * - addField(): This method returns the field alias.
 * Check the documentation for the query method you are using to see if it
 * returns the query or something else, and only chain methods that return the
 * query.
 *
 * @section_insert INSERT, UPDATE, and DELETE queries
 * INSERT, UPDATE, and DELETE queries need special care in order to behave
 * consistently across databases; you should never use db_query() to run
 * an INSERT, UPDATE, or DELETE query. Instead, use functions db_insert(),
 * db_update(), and db_delete() to obtain a base query on your table, and then
 * add dynamic conditions (as illustrated in @ref sec_dynamic above).
 *
 * As a note, db_insert() and similar functions are wrappers on connection
 * object methods. In most classes, you should use dependency injection and the
 * database connection object instead of these wrappers; See @ref sec_connection
 * below for details.
 *
 * For example, if your query is:
 * @code
 * INSERT INTO example (id, uid, path, name) VALUES (1, 2, 'path', 'Name');
 * @endcode
 * You can execute it via:
 * @code
 * $fields = array('id' => 1, 'uid' => 2, 'path' => 'path', 'name' => 'Name');
 * db_insert('example')
 *   ->fields($fields)
 *   ->execute();
 * @endcode
 *
 * @section sec_transaction Transactions
 * Drupal supports transactions, including a transparent fallback for
 * databases that do not support transactions. To start a new transaction,
 * call @code $txn = db_transaction(); @endcode The transaction will
 * remain open for as long as the variable $txn remains in scope; when $txn is
 * destroyed, the transaction will be committed. If your transaction is nested
 * inside of another then Drupal will track each transaction and only commit
 * the outer-most transaction when the last transaction object goes out out of
 * scope (when all relevant queries have completed successfully).
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
 * @section sec_connection Database connection objects
 * The examples here all use functions like db_select() and db_query(), which
 * can be called from any Drupal method or function code. In some classes, you
 * may already have a database connection object in a member variable, or it may
 * be passed into a class constructor via dependency injection. If that is the
 * case, you can look at the code for db_select() and the other functions to see
 * how to get a query object from your connection variable. For example:
 * @code
 * $query = $connection->select('example', 'e');
 * @endcode
 * would be the equivalent of
 * @code
 * $query = db_select('example', 'e');
 * @endcode
 * if you had a connection object variable $connection available to use. See
 * also the @link container Services and Dependency Injection topic. @endlink
 *
 * @see http://drupal.org/developing/api/database
 * @see entity_api
 * @see schemaapi
 *
 * @}
 */

/**
 * @defgroup schemaapi Schema API
 * @{
 * API to handle database schemas.
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
 *   - 'description': A string in non-markup plain text describing this table
 *     and its purpose. References to other tables should be enclosed in
 *     curly-brackets. For example, the node_field_revision table
 *     description field might contain "Stores per-revision title and
 *     body data for each {node}."
 *   - 'fields': An associative array ('fieldname' => specification)
 *     that describes the table's database columns. The specification
 *     is also an array. The following specification parameters are defined:
 *     - 'description': A string in non-markup plain text describing this field
 *       and its purpose. References to other tables should be enclosed in
 *       curly-brackets. For example, the node table vid field
 *       description might contain "Always holds the largest (most
 *       recent) {node_field_revision}.vid value for this nid."
 *     - 'type': The generic datatype: 'char', 'varchar', 'text', 'blob', 'int',
 *       'float', 'numeric', or 'serial'. Most types just map to the according
 *       database engine specific datatypes. Use 'serial' for auto incrementing
 *       fields. This will expand to 'INT auto_increment' on MySQL.
 *       A special 'varchar_ascii' type is also available for limiting machine
 *       name field to US ASCII characters.
 *     - 'mysql_type', 'pgsql_type', 'sqlite_type', etc.: If you need to
 *       use a record type not included in the officially supported list
 *       of types above, you can specify a type for each database
 *       backend. In this case, you can leave out the type parameter,
 *       but be advised that your schema will fail to load on backends that
 *       do not have a type specified. A possible solution can be to
 *       use the "text" type as a fallback.
 *     - 'serialize': A boolean indicating whether the field will be stored as
 *       a serialized string.
 *     - 'size': The data size: 'tiny', 'small', 'medium', 'normal',
 *       'big'. This is a hint about the largest value the field will
 *       store and determines which of the database engine specific
 *       datatypes will be used (e.g. on MySQL, TINYINT vs. INT vs. BIGINT).
 *       'normal', the default, selects the base type (e.g. on MySQL,
 *       INT, VARCHAR, BLOB, etc.).
 *       Not all sizes are available for all data types. See
 *       DatabaseSchema::getFieldTypeMap() for possible combinations.
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
 *     - 'binary': A boolean indicating that MySQL should force 'char',
 *       'varchar' or 'text' fields to use case-sensitive binary collation.
 *       This has no effect on other database types for which case sensitivity
 *       is already the default behavior.
 *     All parameters apart from 'type' are optional except that type
 *     'numeric' columns must specify 'precision' and 'scale', and type
 *     'varchar' must specify the 'length' parameter.
 *  - 'primary key': An array of one or more key column specifiers (see below)
 *    that form the primary key.
 *  - 'unique keys': An associative array of unique keys ('keyname' =>
 *    specification). Each specification is an array of one or more
 *    key column specifiers (see below) that form a unique key on the table.
 *  - 'foreign keys': An associative array of relations ('my_relation' =>
 *    specification). Each specification is an array containing the name of
 *    the referenced table ('table'), and an array of column mappings
 *    ('columns'). Column mappings are defined by key pairs ('source_column' =>
 *    'referenced_column').
 *  - 'indexes':  An associative array of indexes ('indexname' =>
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
 *     'translate'           => array('translate'),
 *   ),
 *   'unique keys' => array(
 *     'vid' => array('vid'),
 *   ),
 *   'foreign keys' => array(
 *     'node_revision' => array(
 *       'table' => 'node_field_revision',
 *       'columns' => array('vid' => 'vid'),
 *      ),
 *     'node_author' => array(
 *       'table' => 'users',
 *       'columns' => array('uid' => 'uid'),
 *      ),
 *    ),
 *   'primary key' => array('nid'),
 * );
 * @endcode
 *
 * @see drupal_install_schema()
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations to a structured query.
 *
 * Structured (aka dynamic) queries that have tags associated may be altered by any module
 * before the query is executed.
 *
 * @param $query
 *   A Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_TAG_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 *
 * @ingroup database
 */
function hook_query_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  if ($query->hasTag('micro_limit')) {
    $query->range(0, 2);
  }
}

/**
 * Perform alterations to a structured query for a given tag.
 *
 * @param $query
 *   An Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 *
 * @ingroup database
 */
function hook_query_TAG_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  // Skip the extra expensive alterations if site has no node access control modules.
  if (!node_access_view_all_nodes()) {
    // Prevent duplicates records.
    $query->distinct();
    // The recognized operations are 'view', 'update', 'delete'.
    if (!$op = $query->getMetaData('op')) {
      $op = 'view';
    }
    // Skip the extra joins and conditions for node admins.
    if (!\Drupal::currentUser()->hasPermission('bypass node access')) {
      // The node_access table has the access grants for any given node.
      $access_alias = $query->join('node_access', 'na', '%alias.nid = n.nid');
      $or = db_or();
      // If any grant exists for the specified user, then user has access to the node for the specified operation.
      foreach (node_access_grants($op, $query->getMetaData('account')) as $realm => $gids) {
        foreach ($gids as $gid) {
          $or->condition(db_and()
            ->condition($access_alias . '.gid', $gid)
            ->condition($access_alias . '.realm', $realm)
          );
        }
      }

      if (count($or->conditions())) {
        $query->condition($or);
      }

      $query->condition($access_alias . 'grant_' . $op, 1, '>=');
    }
  }
}

/**
 * Define the current version of the database schema.
 *
 * A Drupal schema definition is an array structure representing one or more
 * tables and their related keys and indexes. A schema is defined by
 * hook_schema() which must live in your module's .install file.
 *
 * The tables declared by this hook will be automatically created when the
 * module is installed, and removed when the module is uninstalled. This happens
 * before hook_install() is invoked, and after hook_uninstall() is invoked,
 * respectively.
 *
 * By declaring the tables used by your module via an implementation of
 * hook_schema(), these tables will be available on all supported database
 * engines. You don't have to deal with the different SQL dialects for table
 * creation and alteration of the supported database engines.
 *
 * See the Schema API Handbook at http://drupal.org/node/146843 for details on
 * schema definition structures.
 *
 * @return array
 *   A schema definition structure array. For each element of the
 *   array, the key is a table name and the value is a table structure
 *   definition.
 *
 * @see hook_schema_alter()
 *
 * @ingroup schemaapi
 */
function hook_schema() {
  $schema['node'] = array(
    // Example (partial) specification for table "node".
    'description' => 'The base table for nodes.',
    'fields' => array(
      'nid' => array(
        'description' => 'The primary identifier for a node.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ),
      'vid' => array(
        'description' => 'The current {node_field_revision}.vid version identifier.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ),
      'type' => array(
        'description' => 'The type of this node.',
        'type' => 'varchar',
        'length' => 32,
        'not null' => TRUE,
        'default' => '',
      ),
      'title' => array(
        'description' => 'The node title.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ),
    ),
    'indexes' => array(
      'node_changed'        => array('changed'),
      'node_created'        => array('created'),
    ),
    'unique keys' => array(
      'nid_vid' => array('nid', 'vid'),
      'vid'     => array('vid'),
    ),
    'foreign keys' => array(
      'node_revision' => array(
        'table' => 'node_field_revision',
        'columns' => array('vid' => 'vid'),
      ),
      'node_author' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    ),
    'primary key' => array('nid'),
  );
  return $schema;
}

/**
 * Perform alterations to existing database schemas.
 *
 * When a module modifies the database structure of another module (by
 * changing, adding or removing fields, keys or indexes), it should
 * implement hook_schema_alter() to update the default $schema to take its
 * changes into account.
 *
 * See hook_schema() for details on the schema definition structure.
 *
 * @param $schema
 *   Nested array describing the schemas for all modules.
 *
 * @ingroup schemaapi
 */
function hook_schema_alter(&$schema) {
  // Add field to existing schema.
  $schema['users']['fields']['timezone_id'] = array(
    'type' => 'int',
    'not null' => TRUE,
    'default' => 0,
    'description' => 'Per-user timezone configuration.',
  );
}

/**
 * @} End of "addtogroup hooks".
 */
