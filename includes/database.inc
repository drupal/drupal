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
    if (array_key_exists('default', $db_prefix)) {
      $tmp = $db_prefix;
      unset($tmp['default']);
      foreach ($tmp as $key => $val) {
        $sql = strtr($sql, array('{'. $key. '}' => $val. $key));
      }
      return strtr($sql, array('{' => $db_prefix['default'], '}' => ''));
    }
    else {
      foreach ($db_prefix as $key => $val) {
        $sql = strtr($sql, array('{'. $key. '}' => $val. $key));
      }
      return strtr($sql, array('{' => '', '}' => ''));
    }
  }
  else {
    return strtr($sql, array('{' => $db_prefix, '}' => ''));
  }
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
    $handler = "includes/database.$db_type.inc";

    if (is_file($handler)) {
      include_once($handler);
    }
    else {
      die('Unsupported database type');
    }

    $db_conns[$name] = db_connect($connect_url);

  }
  // Set the active connection.
  $active_db = $db_conns[$name];
}

/**
 * Runs a basic query in the active database.
 *
 * User-supplied arguments to the query should be passed in as separate parameters
 * so that they can be properly escaped to avoid SQL injection attacks.
 *
 * @param $query
 *   A string containing an SQL query.
 * @param ...
 *   A variable number of arguments which are substituted into the query using
 *   printf() syntax. Instead of a variable number of query arguments, you may
 *   also pass a single array containing the query arguments.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query($query) {
  $args = func_get_args();
  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    if (is_array($args[1])) {
      $args = array_merge(array($query), $args[1]);
    }
    $args = array_map('db_escape_string', $args);
    $args[0] = $query;
    $query = call_user_func_array('sprintf', $args);
  }
  return _db_query($query);
}

/**
 * Debugging version of db_query().
 *
 * Echoes the query to the browser.
 */
function db_queryd($query) {
  $args = func_get_args();
  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    if (is_array($args[1])) {
      $args = array_merge(array($query), $args[1]);
    }
    $args = array_map('db_escape_string', $args);
    $args[0] = $query;
    $query = call_user_func_array('sprintf', $args);
  }
  return _db_query($query, 1);
}

/**
 * Helper function for db_rewrite_sql.
 *
 * Collects JOIN and WHERE statements via hook_db_rewrite_sql()
 * Decides whether to select primary_key or DISTINCT(primary_key)
 *
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query. Possible values are: comments, forum, node, term_data, vocabulary.
 * @param $primary_field
 *   Name of the primary field.
 * @param $args
 *   Array of additional arguments.
 * @return
 *   An array: join statements, where statements, field or DISTINCT(field).
 */
function _db_rewrite_sql($query = '', $primary_table = 'n', $primary_field = 'nid', $args = array()) {
  $where = array();
  $join = array();
  $distinct = FALSE;
  foreach (module_implements('db_rewrite_sql') as $module) {
    $result = module_invoke($module, 'db_rewrite_sql', $query, $primary_table, $primary_field, $args);
    if (is_array($result)) {
      if (isset($result['where'])) {
        $where[] .= $result['where'];
      }
      if (isset($result['join'])) {
        $join[] .= $result['join'];
      }
      if (isset($result['distinct']) && $result['distinct']) {
        $distinct = TRUE;
      }
    }
    elseif (isset($result)) {
      $where[] .= $result;
    }
  }

  $where = empty($where) ? '' : '('. implode(') AND (', $where) .')';
  $join = empty($join) ? '' : implode(' ', $join);

  return array($join, $where, $distinct);
}

/**
 * Rewrites node queries.
 *
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query. Possible values are: comments, forum, node, term_data, vocabulary.
 * @param $primary_field
 *   Name of the primary field.
 * @param $args
 *   An array of arguments, passed to the implementations of hook_db_rewrite_sql.
 * @return
 *   The original query with JOIN and WHERE statements inserted from hook_db_rewrite_sql implementations. nid is rewritten if needed.
 */
function db_rewrite_sql($query, $primary_table = 'n', $primary_field = 'nid',  $args = array()) {
  list($join, $where, $distinct) = _db_rewrite_sql($query, $primary_table, $primary_field, $args);

  if ($distinct) {
    $field_to_select = 'DISTINCT('. $primary_table .'.'. $primary_field .')';
    // (?<!text) is a negative look-behind (no need to rewrite queries that already use DISTINCT).
    $query = preg_replace('/(SELECT.*)('. $primary_table .'\.)?(?<!DISTINCT\()(?<!DISTINCT\('. $primary_table .'\.)'. $primary_field .'(.*FROM)/AUsi', '\1'. $field_to_select .'\3', $query);
  }

  if (!empty($join)) {
    $query = preg_replace('|FROM[^[:upper:]/,]+|','\0 '. $join .' ', $query);
  }

  if (!empty($where)) {
    if (strpos($query, 'WHERE')) {
      $replace = 'WHERE';
      $add = 'AND';
    }
    elseif (strpos($query, 'GROUP')) {
      $replace = 'GROUP';
      $add = 'GROUP';
    }
    elseif (strpos($query, 'ORDER')) {
      $replace = 'ORDER';
      $add = 'ORDER';
    }
    elseif (strpos($query, 'LIMIT')) {
      $replace = 'LIMIT';
      $add = 'LIMIT';
    }
    else {
      $query .= ' WHERE '. $where;
    }
    if (isset($replace)) {
      $query = str_replace($replace, 'WHERE  '. $where .' '. $add .' ', $query);
    }
  }

  return $query;
}

/**
 * @} End of "defgroup database".
 */

// Initialize the default database.
db_set_active();

?>
