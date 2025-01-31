<?php

/**
 * @file
 * Hooks related to the Database system and the Schema API.
 */

use Drupal\Core\Database\Query\SelectInterface;

/**
 * @defgroup database Database abstraction layer
 * @{
 * Allow the use of different database servers using the same code base.
 *
 * @section sec_intro Overview
 * Drupal's database abstraction layer provides a unified database query API
 * that can query different underlying databases. It is generally built upon
 * PHP's PDO (PHP Data Objects) database API, and inherits much of its syntax
 * and semantics. Besides providing a unified API for database queries, the
 * database abstraction layer also provides a structured way to construct
 * complex queries, and it protects the database by using good security
 * practices.
 *
 * Drupal provides 'database drivers', in the form of Drupal modules, for the
 * concrete implementation of its API towards a specific database engine.
 * MySql, PostgreSQL and SQLite are core implementations, built on PDO. Other
 * modules can provide implementations for additional database engines, like
 * MSSql or Oracle; or alternative low-level database connection clients like
 * mysqli or oci8.
 *
 * For more detailed information on the database abstraction layer, see
 * https://www.drupal.org/docs/drupal-apis/database-api/database-api-overview.
 *
 * @section sec_entity Querying entities
 * Any query on Drupal entities or fields should use the Entity Query API. See
 * the @link entity_api entity API topic @endlink for more information.
 *
 * @section sec_simple Simple SELECT database queries
 * For simple SELECT queries that do not involve entities, the Drupal database
 * abstraction layer provides the functions \Drupal::database()->query() and
 * \Drupal::database()->queryRange(), which execute SELECT queries (optionally
 * with range limits) and return result sets that you can iterate over using
 * foreach loops. (The result sets are objects implementing the
 * \Drupal\Core\Database\StatementInterface interface.)
 * You can use the simple query functions for query strings that are not
 * dynamic (except for placeholders, see below), and that you are certain will
 * work in any database engine. See @ref sec_dynamic below if you have a more
 * complex query, or a query whose syntax would be different in some databases.
 *
 * Note: \Drupal::database() is used here as a shorthand way to get a reference
 * to the database connection object. In most classes, you should use dependency
 * injection and inject the 'database' service to perform queries. See
 * @ref sec_connection below for details.
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
 *   use \Drupal::database()->queryRange() instead of
 *   \Drupal::database()->query().
 *
 * For example, if the query you want to run is:
 * @code
 * SELECT e.id, e.title, e.created FROM example e WHERE e.uid = $uid
 *   ORDER BY e.created DESC LIMIT 0, 10;
 * @endcode
 * you would do it like this:
 * @code
 * $result = \Drupal::database()->queryRange('SELECT e.id, e.title, e.created
 *   FROM {example} e
 *   WHERE e.uid = :uid
 *   ORDER BY e.created DESC',
 *   0, 10, [':uid' => $uid)];
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
 * ... [':my_field' => 'foo'] ...
 * @endcode
 *
 * @section sec_dynamic Dynamic SELECT queries
 * For SELECT queries where the simple query API described in @ref sec_simple
 * will not work well, you need to use the dynamic query API. However, you
 * should still use the Entity Query API if your query involves entities or
 * fields (see the @link entity_api Entity API topic @endlink for more on
 * entity queries).
 *
 * Note: \Drupal::database() is used here as a shorthand way to get a reference
 * to the database connection object. In most classes, you should use dependency
 * injection and inject the 'database' service to perform queries. See
 * @ref sec_connection below for details.
 *
 * The dynamic query API lets you build up a query dynamically using method
 * calls. As an illustration, the query example from @ref sec_simple above
 * would be:
 * @code
 * $result = \Drupal::database()->select('example', 'e')
 *   ->fields('e', ['id', 'title', 'created'])
 *   ->condition('e.uid', $uid)
 *   ->orderBy('e.created', 'DESC')
 *   ->range(0, 10)
 *   ->execute();
 * @endcode
 *
 * There are also methods to join to other tables, add fields with aliases,
 * isNull() to query for NULL values, etc. See
 * https://www.drupal.org/docs/drupal-apis/database-api for many more details.
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
 * @section sec_insert INSERT, UPDATE, and DELETE queries
 * INSERT, UPDATE, and DELETE queries need special care in order to behave
 * consistently across databases; you should never use
 * \Drupal::database()->query() to run an INSERT, UPDATE, or DELETE query.
 * Instead, use functions \Drupal::database()->insert(),
 * \Drupal::database()->update(), and \Drupal::database()->delete() to obtain
 * a base query on your table, and then add dynamic conditions (as illustrated
 * in @ref sec_dynamic above).
 *
 * Note: \Drupal::database() is used here as a shorthand way to get a reference
 * to the database connection object. In most classes, you should use dependency
 * injection and inject the 'database' service to perform queries. See
 * @ref sec_connection below for details.
 *
 * For example, if your query is:
 * @code
 * INSERT INTO example (id, uid, path, name) VALUES (1, 2, 'path', 'Name');
 * @endcode
 * You can execute it via:
 * @code
 * $fields = ['id' => 1, 'uid' => 2, 'path' => 'path', 'name' => 'Name'];
 * \Drupal::database()->insert('example')
 *   ->fields($fields)
 *   ->execute();
 * @endcode
 *
 * @section sec_transaction Transactions
 * Drupal supports transactions, including a transparent fallback for
 * databases that do not support transactions. To start a new transaction,
 * call startTransaction(), like this:
 * @code
 * $transaction = \Drupal::database()->startTransaction();
 * @endcode
 * The transaction will remain open for as long as the variable $transaction
 * remains in scope; when $transaction is destroyed, the transaction will be
 * committed. If your transaction is nested inside of another then Drupal will
 * track each transaction and only commit the outer-most transaction when the
 * last transaction object goes out of scope (when all relevant queries have
 * completed successfully).
 *
 * Example:
 * @code
 * function my_transaction_function() {
 *   $connection = \Drupal::database();
 *
 *   try {
 *     // The transaction opens here.
 *     $transaction = $connection->startTransaction();
 *
 *     $id = $connection->insert('example')
 *       ->fields([
 *         'field1' => 'string',
 *         'field2' => 5,
 *       ])
 *       ->execute();
 *
 *     my_other_function($id);
 *
 *     return $id;
 *   }
 *   catch (Exception $e) {
 *     // Something went wrong somewhere. If the exception was thrown during
 *     // startTransaction(), then $transaction is NULL and there's nothing to
 *     // roll back. If the exception was thrown after a transaction was
 *     // successfully started, then it must be rolled back.
 *     if (isset($transaction)) {
 *       $transaction->rollBack();
 *     }
 *
 *     // Log the exception.
 *     Error::logException(\Drupal::logger('type'), $e);
 *   }
 *
 *   // $transaction goes out of scope here. Unless the transaction was rolled
 *   // back, it gets automatically committed here.
 * }
 *
 * function my_other_function($id) {
 *   $connection = \Drupal::database();
 *   // The transaction is still open here.
 *
 *   if ($id % 2 == 0) {
 *     $connection->update('example')
 *       ->condition('id', $id)
 *       ->fields(['field2' => 10])
 *       ->execute();
 *   }
 * }
 * @endcode
 *
 * @section sec_connection Database connection objects
 * The examples here all use functions like \Drupal::database()->select() and
 * \Drupal::database()->query(), which can be called from any Drupal method or
 * function code. In some classes, you may already have a database connection
 * object in a member variable, or it may be passed into a class constructor
 * via dependency injection. If that is the case, you can look at the code for
 * \Drupal::database()->select() and the other functions to see how to get a
 * query object from your connection variable. For example:
 * @code
 * $query = $connection->select('example', 'e');
 * @endcode
 * would be the equivalent of
 * @code
 * $query = \Drupal::database()->select('example', 'e');
 * @endcode
 * if you had a connection object variable $connection available to use. See
 * also the @link container Services and Dependency Injection topic. @endlink
 * In Object Oriented code:
 * - If possible, use dependency injection to use the "database" service.
 *   @code
 *   use Drupal\Core\Database\Connection;
 *
 *   class myClass {
 *
 *   public function __construct(protected Connection $database) {
 *     // ...
 *   }
 *   @endcode
 * - If it is not possible to use dependency injection, for example in a static
 *   method, use \Drupal::database().
 *   @code
 *   $connection = \Drupal::database();
 *   $query = $connection->query('...');
 *   @endcode
 * - If services are not yet available, use
 *   \Drupal\Core\Database\Database::getConnection() to get a database
 *   connection;
 *   @code
 *   use Drupal\Core\Database\Database;
 *
 *   // ...
 *
 *   $connection = Database::getConnection();
 *   $query = $connection->query('...');
 *   @endcode
 * - In unit tests, we do not have a booted kernel or a built container. Unit
 *   tests that need a database service should be converted to a kernel test.
 * - In kernel and functional test classes, use
 *   \Drupal\Core\Database\Database::getConnection() to get a database
 *   connection.
 *   @code
 *   use Drupal\Core\Database\Database;
 *
 *   // ...
 *
 *   $connection = Database::getConnection();
 *   $query = $connection->query('...');
 *   @endcode
 * In procedural code, such as *.module, *.inc or script files:
 * - Use \Drupal::database(); to get database connection.
 *   @code
 *   $connection = \Drupal::database();
 *   $query = $connection->query('...');
 *   @endcode
 *
 * @see https://www.drupal.org/docs/drupal-apis/database-api
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
 *     and its purpose. References to other tables should be enclosed in curly
 *     brackets.
 *   - 'fields': An associative array ('fieldname' => specification)
 *     that describes the table's database columns. The specification
 *     is also an array. The following specification parameters are defined:
 *     - 'description': A string in non-markup plain text describing this field
 *       and its purpose. References to other tables should be enclosed in curly
 *       brackets. For example, the users_data table 'uid' field description
 *       might contain "The {users}.uid this record affects."
 *     - 'type': The generic datatype: 'char', 'varchar', 'text', 'blob', 'int',
 *       'float', 'numeric', or 'serial'. Most types just map to the according
 *       database engine specific data types. Use 'serial' for auto incrementing
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
 *       data types will be used (e.g. on MySQL, TINYINT vs. INT vs. BIGINT).
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
 *  - 'unique keys': An associative array of unique keys ('key_name' =>
 *    specification). Each specification is an array of one or more
 *    key column specifiers (see below) that form a unique key on the table.
 *  - 'foreign keys': An associative array of relations ('my_relation' =>
 *    specification). Each specification is an array containing the name of
 *    the referenced table ('table'), and an array of column mappings
 *    ('columns'). Column mappings are defined by key pairs ('source_column' =>
 *    'referenced_column'). This key is for documentation purposes only; foreign
 *    keys are not created in the database, nor are they enforced by Drupal.
 *  - 'indexes':  An associative array of indexes ('indexname' =>
 *    specification). Each specification is an array of one or more
 *    key column specifiers (see below) that form an index on the
 *    table.
 *
 * A key column specifier is either a string naming a column or an array of two
 * elements, column name and length, specifying a prefix of the named column.
 * Note that some DBMS drivers may opt to ignore the prefix length configuration
 * and still use the whole field value for the key. Code should therefore not
 * rely on this functionality.
 *
 * As an example, this is the schema definition for the 'users_data' table. It
 * shows five fields ('uid', 'module', 'name', 'value', and 'serialized'), the
 * primary key (on the 'uid', 'module', and 'name' fields), and two indexes (the
 * 'module' index on the 'module' field and the 'name' index on the 'name'
 * field).
 *
 * @code
 * $schema['users_data'] = [
 *   'description' => 'Stores module data as key/value pairs per user.',
 *   'fields' => [
 *     'uid' => [
 *       'description' => 'The {users}.uid this record affects.',
 *       'type' => 'int',
 *       'unsigned' => TRUE,
 *       'not null' => TRUE,
 *       'default' => 0,
 *     ],
 *     'module' => [
 *       'description' => 'The name of the module declaring the variable.',
 *       'type' => 'varchar_ascii',
 *       'length' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
 *       'not null' => TRUE,
 *       'default' => '',
 *     ],
 *     'name' => [
 *       'description' => 'The identifier of the data.',
 *       'type' => 'varchar_ascii',
 *       'length' => 128,
 *       'not null' => TRUE,
 *       'default' => '',
 *     ],
 *     'value' => [
 *       'description' => 'The value.',
 *       'type' => 'blob',
 *       'not null' => FALSE,
 *       'size' => 'big',
 *     ],
 *     'serialized' => [
 *       'description' => 'Whether value is serialized.',
 *       'type' => 'int',
 *       'size' => 'tiny',
 *       'unsigned' => TRUE,
 *       'default' => 0,
 *     ],
 *   ],
 *   'primary key' => ['uid', 'module', 'name'],
 *   'indexes' => [
 *     'module' => ['module'],
 *     'name' => ['name'],
 *   ],
 *   // For documentation purposes only; foreign keys are not created in the
 *   // database.
 *   'foreign keys' => [
 *     'data_user' => [
 *       'table' => 'users',
 *       'columns' => [
 *         'uid' => 'uid',
 *       ],
 *     ],
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Extension\ModuleInstaller::installSchema()
 * @see \Drupal\Core\Extension\ModuleInstaller::uninstallSchema()
 * @see \Drupal\TestTools\Extension\SchemaInspector::getTablesSpecification()
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
 * Structured (aka dynamic) queries that have tags associated may be altered by
 * any module before the query is executed.
 *
 * @param Drupal\Core\Database\Query\AlterableInterface $query
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
 * Some common tags include:
 * - 'entity_reference': For queries that return entities that may be referenced
 *   by an entity reference field.
 * - ENTITY_TYPE . '_access': For queries of entities that will be displayed in
 *   a listing (e.g., from Views) and therefore require access control.
 *
 * @param Drupal\Core\Database\Query\AlterableInterface $query
 *   A Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 *
 * @ingroup database
 */
function hook_query_TAG_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  // This is an example of a possible hook_query_media_access_alter()
  // implementation. In other words, alter queries of media entities that
  // require access control (have the 'media_access' query tag).

  // Determine which media entities we want to remove from the query. In this
  // example, we hard-code some media IDs.
  $media_entities_to_hide = [1, 3];

  // In this example, we're only interested in applying our media access
  // restrictions to SELECT queries. hook_media_access() can be used to apply
  // access control to 'update' and 'delete' operations.
  if (!($query instanceof SelectInterface)) {
    return;
  }

  // The tables in the query. This can include media entity tables and other
  // tables. Tables might be joined more than once, with aliases.
  $query_tables = $query->getTables();

  // The tables belonging to media entity storage.
  $table_mapping = \Drupal::entityTypeManager()->getStorage('media')->getTableMapping();
  $media_tables = $table_mapping->getTableNames();

  // For each table in the query, if it's a media entity storage table, add a
  // condition to filter out records belonging to a media entity that we wish
  // to hide.
  foreach ($query_tables as $alias => $info) {
    // Skip over subqueries.
    if ($info['table'] instanceof SelectInterface) {
      continue;
    }
    $real_table_name = $info['table'];
    if (in_array($real_table_name, $media_tables)) {
      $query->condition("$alias.mid", $media_entities_to_hide, 'NOT IN');
    }
  }
}

/**
 * Define the current version of the database schema.
 *
 * Only procedural implementations are supported for this hook.
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
 * See the Schema API Handbook at https://www.drupal.org/node/146843 for details
 * on schema definition structures. Note that foreign key definitions are for
 * documentation purposes only; foreign keys are not created in the database,
 * nor are they enforced by Drupal.
 *
 * @return array
 *   A schema definition structure array. For each element of the
 *   array, the key is a table name and the value is a table structure
 *   definition.
 *
 * @ingroup schemaapi
 */
function hook_schema(): array {
  $schema['users_data'] = [
    'description' => 'Stores module data as key/value pairs per user.',
    'fields' => [
      'uid' => [
        'description' => 'The {users}.uid this record affects.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ],
      'module' => [
        'description' => 'The name of the module declaring the variable.',
        'type' => 'varchar_ascii',
        'length' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
        'not null' => TRUE,
        'default' => '',
      ],
      'name' => [
        'description' => 'The identifier of the data.',
        'type' => 'varchar_ascii',
        'length' => 128,
        'not null' => TRUE,
        'default' => '',
      ],
      'value' => [
        'description' => 'The value.',
        'type' => 'blob',
        'not null' => FALSE,
        'size' => 'big',
      ],
      'serialized' => [
        'description' => 'Whether value is serialized.',
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'default' => 0,
      ],
    ],
    'primary key' => ['uid', 'module', 'name'],
    'indexes' => [
      'module' => ['module'],
      'name' => ['name'],
    ],
    // For documentation purposes only; foreign keys are not created in the
    // database.
    'foreign keys' => [
      'data_user' => [
        'table' => 'users',
        'columns' => [
          'uid' => 'uid',
        ],
      ],
    ],
  ];

  return $schema;
}

/**
 * @} End of "addtogroup hooks".
 */
