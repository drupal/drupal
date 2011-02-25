<?php

/**
 * @file
 * Database interface code for database servers using PEAR, including PostgreSQL.
 */

require_once 'DB.php';

/**
 * Initialize a database connection.
 */
function db_connect($url) {
  $db_handle = DB::connect($url);

  if (DB::isError($db_handle)) {
    die('Database problem: '. $db_handle->getMessage());
  }

  $db_handle->setFetchMode(DB_FETCHMODE_ASSOC);

  return $db_handle;
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
 *   printf() syntax.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query($query) {

  $args = func_get_args();

  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    if(is_array($args[1])){
      $args1 = array_map('check_query', $args[1]);
      $nargs = array_merge(array($query), $args1);
    }
    else {
      $nargs = array_map('check_query', $args);
      $nargs[0] = $query;
    }
    return _db_query(call_user_func_array('sprintf', $nargs));
  }
  else {
    return _db_query($query);
  }
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
    if(is_array($args[1])){
      $args1 = array_map('check_query', $args[1]);
      $nargs = array_merge(array($query), $args1);
    }
    else {
      $nargs = array_map('check_query', $args);
      $nargs[0] = $query;
    }
    return _db_query(call_user_func_array('sprintf', $nargs), 1);
  }
  else {
    return _db_query($query, 1);
  }
}

/**
 * Helper function for db_query().
 */
function _db_query($query, $debug = 0) {
  global $active_db, $queries;

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $result = $active_db->query($query);

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print '<p>query: '. $query .'</p>';
  }

  if (DB::isError($result)) {
    trigger_error($result->getMessage() ."\nquery: ". htmlspecialchars($query), E_USER_ERROR);
  }
  else {
    return $result;
  }
}

/**
 * Fetch one result row from the previous query as an object.
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   An object representing the next row of the result. The attributes of this
 *   object are the table fields selected by the query.
 */
function db_fetch_object($result) {
  if ($result) {
    return $result->fetchRow(DB_FETCHMODE_OBJECT);
  }
}

/**
 * Fetch one result row from the previous query as an array.
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   An associative array representing the next row of the result. The keys of
 *   this object are the names of the table fields selected by the query, and
 *   the values are the field values for this result row.
 */
function db_fetch_array($result) {
  if ($result) {
    return $result->fetchRow(DB_FETCHMODE_ASSOC);
  }
}

/**
 * Determine how many result rows were found by the preceding query.
 *
 * @param $result
 *   A database query result resource, as returned from db_query().
 * @return
 *   The number of result rows.
 */
function db_num_rows($result) {
  if ($result) {
    return $result->numRows($result);
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
 * @param $row
 *   The index of the row whose result is needed.
 * @return
 *   The resulting field.
 */
function db_result($result, $row = 0) {
  if ($result && $result->numRows($result) > $row) {
    $tmp = $result->fetchRow(DB_FETCHMODE_ORDERED);
    return $tmp[$row];
  }
}

/**
 * Determine whether the previous query caused an error.
 */
function db_error() {
  global $active_db;

  return DB::isError($active_db);
}

/**
 * Return a new unique ID in the given sequence.
 *
 * For compatibility reasons, Drupal does not use auto-numbered fields in its
 * database tables. Instead, this function is used to return a new unique ID
 * of the type requested. If necessary, a new sequence with the given name
 * will be created.
 */
function db_next_id($name) {
  global $active_db;

  $name = db_prefix_tables($name);
  $result = $active_db->nextID($name);
  if (DB::isError($result)) {
    watchdog('error', t('Database: %db sequence table: %name.', array('%db' => '<em>'. $result->getMessage() .'</em>', '%name' => "<em>$name</em>")));
  }
  else {
    return $result;
  }
}

/**
 * Determine the number of rows changed by the preceding query.
 */
function db_affected_rows() {
  global $active_db;

  return $active_db->affectedRows();
}

/**
 * Runs a limited-range query in the active database.
 *
 * Use this as a substitute for db_query() when a subset of the query is to be
 * returned.
 * User-supplied arguments to the query should be passed in as separate parameters
 * so that they can be properly escaped to avoid SQL injection attacks.
 *
 * @param $query
 *   A string containing an SQL query.
 * @param ...
 *   A variable number of arguments which are substituted into the query using
 *   printf() syntax.
 * @param $from
 *   The first result row to return.
 * @param $count
 *   The maximum number of result rows to return.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query_range($query) {
  global $active_db, $queries;

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $args = func_get_args();
  $count = array_pop($args);
  $from = array_pop($args);
  if (count(func_get_args()) > 3) {
    $args = array_map('check_query', $args);
    $query = db_prefix_tables($query);
    $args[0] = $query;
    $result = $active_db->limitQuery(call_user_func_array('sprintf', $args), $from, $count);
  }
  else {
    $query = func_get_arg(0);
    $query = db_prefix_tables($query);
    $result = $active_db->limitQuery( $query, $from, $count);
  }

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query. ' [LIMIT '. $from .', '. $count .']', $diff);
  }

  if (DB::isError($result)) {
    watchdog('error', t('Database: %db query: %query.', array('%db' => '<em>'. $result->getMessage() .'</em>', '%query' => '<em>'. htmlspecialchars($query) .'</em>')));
  }
  else {
    return $result;
  }
}

?>
