<?php

/**
 * @file
 * Database interface code for MySQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * Initialize a database connection.
 *
 * Note that you can change the mysql_connect() call to mysql_pconnect() if you
 * want to use persistent connections. This is not recommended on shared hosts,
 * and might require additional database/webserver tuning. It can increase
 * performance, however, when the overhead to connect to your database is high
 * (e.g. your database and web server live on different machines).
 */
function db_connect($url) {
  $url = parse_url($url);

  // Allow for non-standard MySQL port.
  if (isset($url['port'])) {
     $url['host'] = $url['host'] .':'. $url['port'];
  }

  $connection = mysql_connect($url['host'], $url['user'], $url['pass']) or die(mysql_error());
  mysql_select_db(substr($url['path'], 1)) or die('unable to select database');

  return $connection;
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
  global $active_db;
  global $queries;

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $result = mysql_query($query, $active_db);

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print '<p>query: '. $query .'<br />error:'. mysql_error() .'</p>';
  }

  if (!mysql_errno()) {
    return $result;
  }
  else {
    trigger_error(check_plain(mysql_error() ."\nquery: ". $query), E_USER_ERROR);
    return FALSE;
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
    return mysql_fetch_object($result);
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
    return mysql_fetch_array($result, MYSQL_ASSOC);
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
    return mysql_num_rows($result);
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
  if ($result && mysql_num_rows($result) > $row) {
    return mysql_result($result, $row);
  }
}

/**
 * Determine whether the previous query caused an error.
 */
function db_error() {
  return mysql_errno();
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
  $name = db_prefix_tables($name);
  db_query('LOCK TABLES {sequences} WRITE');
  $id = db_result(db_query("SELECT id FROM {sequences} WHERE name = '%s'", $name)) + 1;
  db_query("REPLACE INTO {sequences} VALUES ('%s', %d)", $name, $id);
  db_query('UNLOCK TABLES');

  return $id;
}

/**
 * Determine the number of rows changed by the preceding query.
 */
function db_affected_rows() {
  return mysql_affected_rows();
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
  $args = func_get_args();
  $count = array_pop($args);
  $from = array_pop($args);
  if (count(func_get_args()) > 3) {
    $args = array_map('check_query', $args);
    $query = db_prefix_tables($query);
    $args[0] = $query;
    $query = call_user_func_array('sprintf', $args);
  }
  else {
    $query = func_get_arg(0);
    $query = db_prefix_tables($query);
  }
  $query .= ' LIMIT '. $from .', '. $count;
  return _db_query($query);
}

/**
 * Returns a properly formatted Binary Large OBject value.
 *
 * @param $data
 *   Data to encode.
 * @return
 *  Encoded data.
 */
function db_encode_blob($data) {
  return $data;
}

/**
 * Returns text from a Binary Large OBject value.
 *
 * @param $data
 *   Data to decode.
 * @return
 *  Decoded data.
 */
function db_decode_blob($data) {
  return $data;
}

/**
 * @} End of "ingroup database".
 */

?>
