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
 *
 * @return the name of the previously active database or FALSE if non was found.
 */
function db_set_active($name = 'default') {
  global $db_url, $db_type, $active_db;
  static $db_conns, $active_name = FALSE;

  if (!isset($db_conns[$name])) {
    // Initiate a new connection, using the named DB URL specified.
    if (is_array($db_url)) {
      $connect_url = array_key_exists($name, $db_url) ? $db_url[$name] : $db_url['default'];
    }
    else {
      $connect_url = $db_url;
    }

    $db_type = substr($connect_url, 0, strpos($connect_url, '://'));
    $handler = "./includes/database.$db_type.inc";

    if (is_file($handler)) {
      include_once $handler;
    }
    else {
      drupal_maintenance_theme();
      drupal_set_title('Unsupported database type');
      print theme('maintenance_page', '<p>The database type '. theme('placeholder', $db_type) .' is unsupported. Please use either <var>mysql</var> for MySQL 3.x &amp; 4.0.x databases, <var>mysqli</var> for MySQL 4.1.x+ databases, or <var>pgsql</var> for PostgreSQL databases. The database information is in your <code>settings.php</code> file.</p>
<p>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>');
      exit;
    }

    $db_conns[$name] = db_connect($connect_url);
  }

  $previous_name = $active_name;
  // Set the active connection.
  $active_name = $name;
  $active_db = $db_conns[$name];

  return $previous_name;
}

/**
 * Helper function for db_query().
 */
function _db_query_callback($match, $init = FALSE) {
  static $args = NULL;
  if ($init) {
    $args = $match;
    return;
  }

  switch ($match[1]) {
    case '%d': // We must use type casting to int to convert false/null/(true?)
      return (int) array_shift($args); // We don't need db_escape_string as numbers are db-safe
    case '%s':
      return db_escape_string(array_shift($args));
    case '%%':
      return '%';
    case '%f':
      return (float) array_shift($args);
    case '%b': // binary data
      return db_encode_blob(array_shift($args));
  }
}

define('DB_QUERY_REGEXP', '/(%d|%s|%%|%f|%b)/');

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
 * Debugging version of db_query().
 *
 * Echoes the query to the browser.
 */
function db_queryd($query) {
  $args = func_get_args();
  array_shift($args);
  $query = db_prefix_tables($query);
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
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
 *   Name or alias of the table which has the primary key field for this query. Possible values are: comments, forum, node, menu, term_data, vocabulary.
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
    if (isset($result) && is_array($result)) {
      if (isset($result['where'])) {
        $where[] = $result['where'];
      }
      if (isset($result['join'])) {
        $join[] = $result['join'];
      }
      if (isset($result['distinct']) && $result['distinct']) {
        $distinct = TRUE;
      }
    }
    elseif (isset($result)) {
      $where[] = $result;
    }
  }

  $where = empty($where) ? '' : '('. implode(') AND (', $where) .')';
  $join = empty($join) ? '' : implode(' ', $join);

  return array($join, $where, $distinct);
}

/**
 * Rewrites node, taxonomy and comment queries. Use it for listing queries. Do not
 * use FROM table1, table2 syntax, use JOIN instead.
 *
 * @param $query
 *   Query to be rewritten.
 * @param $primary_table
 *   Name or alias of the table which has the primary key field for this query. Possible values are: comments, forum, node, menu, term_data, vocabulary.
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
    $query = db_distinct_field($primary_table, $primary_field, $query);
  }

  if (!empty($where) || !empty($join)) {
    if (!empty($where)) {
      $new = "WHERE $where ";
    }
    $new = " $join $new";
    if (strpos($query, 'WHERE')) {
      $query = str_replace('WHERE', $new .'AND (', $query);
      $insert = ') ';
    }
    else {
      $insert = $new;
    }
    if (strpos($query, 'GROUP')) {
      $replace = 'GROUP';
    }
    elseif (strpos($query, 'ORDER')) {
      $replace = 'ORDER';
    }
    elseif (strpos($query, 'LIMIT')) {
      $replace = 'LIMIT';
    }
    else {
      $query .= $insert;
    }
    if (isset($replace)) {
      $query = str_replace($replace, $insert . $replace, $query);
    }
  }

  return $query;
}

/**
 * Restrict a dynamic tablename to safe characters.
 *
 * Only keeps alphanumeric and underscores.
 */
function db_escape_table($string) {
  return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
}

/**
 * @} End of "defgroup database".
 */


