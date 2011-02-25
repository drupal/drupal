<?php

/**
 * @file
 * Wrapper for database interface code.
 */

/**
 * @defgroup database Database abstraction layer
 * @{
 * Allow the use of different database servers using the same code base.
 *
 * Drupal provides a slim database abstraction layer to provide developers with
 * the ability to support multiple database servers easily. The intent of this
 * layer is to preserve the syntax and power of SQL as much as possible, while
 * letting Drupal control the pieces of queries that need to be written
 * differently for different servers and provide basic security checks.
 *
 * Most Drupal database queries are performed by a call to db_query() or
 * db_query_range(). Module authors should also consider using pager_query() for
 * queries that return results that need to be presented on multiple pages, and
 * tablesort_sql() for generating appropriate queries for sortable tables.
 *
 * For example, one might wish to return a list of the most recent 10 nodes
 * authored by a given user. Instead of directly issuing the SQL query
 * @code
 *   SELECT n.title, n.body, n.created FROM node n WHERE n.uid = $uid LIMIT 0, 10;
 * @endcode
 * one would instead call the Drupal functions:
 * @code
 *   $result = db_query_range('SELECT n.title, n.body, n.created
 *     FROM {node} n WHERE n.uid = %d', $uid, 0, 10);
 *   while ($node = db_fetch_object($result)) {
 *     // Perform operations on $node->body, etc. here.
 *   }
 * @endcode
 * Curly braces are used around "node" to provide table prefixing via
 * db_prefix_tables(). The explicit use of a user ID is pulled out into an
 * argument passed to db_query() so that SQL injection attacks from user input
 * can be caught and nullified. The LIMIT syntax varies between database servers,
 * so that is abstracted into db_query_range() arguments. Finally, note the
 * common pattern of iterating over the result set using db_fetch_object().
 */

/**
 * Append a database prefix to all tables in a query.
 *
 * Queries sent to Drupal should wrap all table names in curly brackets. This
 * function searches for this syntax and adds Drupal's table prefix to all
 * tables, allowing Drupal to coexist with other systems in the same database if
 * necessary.
 *
 * @param $sql
 *   A string containing a partial or entire SQL query.
 * @return
 *   The properly-prefixed string.
 */
function db_prefix_tables($sql) {
  global $db_prefix;

  if (is_array($db_prefix)) {
    $prefix = $db_prefix['default'];
    foreach ($db_prefix as $key => $val) {
      if ($key !== 'default') {
        $sql = strtr($sql, array('{'. $key. '}' => $val. $key));
      }
    }
  }
  else {
    $prefix = $db_prefix;
  }
  return strtr($sql, array('{' => $prefix, '}' => ''));
}

/**
 * Activate a database for future queries.
 *
 * If it is necessary to use external databases in a project, this function can
 * be used to change where database queries are sent. If the database has not
 * yet been used, it is initialized using the URL specified for that name in
 * Drupal's configuration file. If this name is not defined, a duplicate of the
 * default connection is made instead.
 *
 * Be sure to change the connection back to the default when done with custom
 * code.
 *
 * @param $name
 *   The name assigned to the newly active database connection. If omitted, the
 *   default connection will be made active.
 */
function db_set_active($name = 'default') {
  global $db_url, $db_type, $active_db;
  static $db_conns;

  if (!isset($db_conns[$name])) {
    // Initiate a new connection, using the named DB URL specified.
    if (is_array($db_url)) {
      $connect_url = array_key_exists($name, $db_url) ? $db_url[$name] : $db_url['default'];
    }
    else {
      $connect_url = $db_url;
    }

    $db_type = substr($connect_url, 0, strpos($connect_url, '://'));

    // TODO: Allow more than one database API to be present.
    if ($db_type == 'mysql') {
      include_once 'includes/database.mysql.inc';
    }
    else {
      include_once 'includes/database.pgsql.inc';
    }

    $db_conns[$name] = db_connect($connect_url);

  }
  // Set the active connection.
  $active_db = $db_conns[$name];
}

/**
 * @} End of "defgroup database".
 */

// Initialize the default database.
db_set_active();

?>
