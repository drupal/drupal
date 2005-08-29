<?php
// $Id: database.pgsql.inc,v 1.14 2005/08/29 19:32:55 dries Exp $

/**
 * @file
 * Database interface code for PostgreSQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * Initialize a database connection.
 *
 * Note that you can change the pg_connect() call to pg_pconnect() if you
 * want to use persistent connections. This is not recommended on shared hosts,
 * and might require additional database/webserver tuning. It can increase
 * performance, however, when the overhead to connect to your database is high
 * (e.g. your database and web server live on different machines).
 */
function db_connect($url) {
  $url = parse_url($url);

  $conn_string = ' user='. $url['user'] .' dbname='. substr($url['path'], 1) .' password='. $url['pass'] . ' host=' . $url['host'];
  $conn_string .= isset($url['port']) ? ' port=' . $url['port'] : '';

  // pg_last_error() does not return a useful error message for database
  // connection errors. We must turn on error tracking to get at a good error
  // message, which will be stored in $php_errormsg.
  $track_errors_previous = ini_get('track_errors');
  ini_set('track_errors', 1);

  $connection = @pg_connect($conn_string);
  if (!$connection) {
    drupal_maintenance_theme();
    drupal_set_title('Unable to connect to database');
    print theme('maintenance_page', '<p>This either means that the database information in your <code>settings.php</code> file is incorrect or we can\'t contact the PostgreSQL database server. This could mean your hosting provider\'s database server is down.</p>
<p>The PostgreSQL error was: '. theme('placeholder', decode_entities($php_errormsg)) .'</p>
<p>Currently, the database is '. theme('placeholder', substr($url['path'], 1)) .', the username is '. theme('placeholder', $url['user']) .', and the database server is '. theme('placeholder', $url['host']) .'.</p>
<ul>
  <li>Are you sure you have the correct username and password?</li>
  <li>Are you sure that you have typed the correct hostname?</li>
  <li>Are you sure you have the correct database name?</li>
  <li>Are you sure that the database server is running?</li>
</ul>
<p>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>');
    exit;
  }

  // Restore error tracking setting
  ini_set('track_errors', $track_errors_previous);

  return $connection;
}

/**
 * Helper function for db_query().
 */
function _db_query($query, $debug = 0) {
  global $active_db, $last_result, $queries;

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $last_result = pg_query($active_db, $query);

  if (variable_get('dev_query', 0)) {
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print '<p>query: '. $query .'<br />error:'. pg_last_error() .'</p>';
  }

  if ($last_result !== FALSE) {
    return $last_result;
  }
  else {
    trigger_error(pg_last_error() ."\nquery: ". htmlspecialchars($query), E_USER_ERROR);
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
    return pg_fetch_object($result);
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
    return pg_fetch_assoc($result);
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
    return pg_num_rows($result);
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
  if ($result && pg_num_rows($result) > $row) {
    $res = pg_fetch_row($result, $row);

    return $res[0];
  }
}

/**
 * Determine whether the previous query caused an error.
 */
function db_error() {
  return pg_last_error();
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
  $id = db_result(db_query("SELECT nextval('%s_seq')", db_prefix_tables($name)));
  return $id;
}

/**
 * Determine the number of rows changed by the preceding query.
 */
function db_affected_rows() {
  global $last_result;
  return pg_affected_rows($last_result);
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
 *   printf() syntax. Instead of a variable number of query arguments, you may
 *   also pass a single array containing the query arguments.
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

  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    // Check for array (alternative syntax).
    if (is_array($args[1])) {
      $args = array_merge(array($query), $args[1]);
    }
    $args = array_map('db_escape_string', $args);
    $args[0] = $query;
    $query = call_user_func_array('sprintf', $args);
  }
  $query .= ' LIMIT '. $count .' OFFSET '. $from;
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
  return addcslashes($data, "\0..\37\\");
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
  return stripcslashes($data);
}

/**
 * Prepare user input for use in a database query, preventing SQL injection attacks.
 * Note: This function requires PostgreSQL 7.2 or later.
 */
function db_escape_string($text) {
  return pg_escape_string($text);
}

/**
 * Lock a table.
 * This function automatically starts a transaction.
 */
function db_lock_table($table) {
  db_query('BEGIN; LOCK TABLE {%s} IN EXCLUSIVE MODE', $table);
}

/**
 * Unlock all locked tables.
 * This function automatically commits a transation.
 */
function db_unlock_tables() {
  db_query('COMMIT');
}

/**
 * @} End of "ingroup database".
 */


