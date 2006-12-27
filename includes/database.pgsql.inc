<?php
// $Id: database.pgsql.inc,v 1.43 2006/12/27 22:13:56 dries Exp $

/**
 * @file
 * Database interface code for PostgreSQL database servers.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * Report database status.
 */
function db_status_report() {
  $t = get_t();

  $version = db_version();

  $form['pgsql'] = array(
    'title' => $t('PostgreSQL database'),
    'value' => $version,
  );

  if (version_compare($version, DRUPAL_MINIMUM_PGSQL) < 0) {
    $form['pgsql']['severity'] = REQUIREMENT_ERROR;
    $form['pgsql']['description'] = $t('Your PostgreSQL Server is too old. Drupal requires at least PostgreSQL %version.', array('%version' => DRUPAL_MINIMUM_PGSQL));
  }

  return $form;
}

/**
 * Returns the version of the database server currently in use.
 *
 * @return Database server version
 */
function db_version() {
  return db_result(db_query("SHOW SERVER_VERSION"));
}

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
   // Check if MySQL support is present in PHP
  if (!function_exists('pg_connect')) {
    drupal_maintenance_theme();
    drupal_set_title('PHP PostgreSQL support not enabled');
    print theme('maintenance_page', '<p>We were unable to use the PostgreSQL database because the PostgreSQL extension for PHP is not installed. Check your <code>PHP.ini</code> to see how you can enable it.</p>
<p>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>');
    exit;
  }

  $url = parse_url($url);
  $conn_string = '';

  // Decode url-encoded information in the db connection string
  if (isset($url['user'])) {
    $conn_string .= ' user=' . urldecode($url['user']);
  }
  if (isset($url['pass'])) {
    $conn_string .= ' password=' . urldecode($url['pass']);
  }
  if (isset($url['host'])) {
    $conn_string .= ' host=' . urldecode($url['host']);
  }
  if (isset($url['path'])) {
    $conn_string .= ' dbname=' . substr(urldecode($url['path']), 1);
  }
  if (isset($url['port'])) {
    $conn_string .= ' port=' . urldecode($url['port']);
  }

  // pg_last_error() does not return a useful error message for database
  // connection errors. We must turn on error tracking to get at a good error
  // message, which will be stored in $php_errormsg.
  $track_errors_previous = ini_get('track_errors');
  ini_set('track_errors', 1);

  $connection = @pg_connect($conn_string);
  if (!$connection) {
    drupal_maintenance_theme();
    drupal_set_header('HTTP/1.1 503 Service Unavailable');
    drupal_set_title('Unable to connect to database');
    print theme('maintenance_page', '<p>If you still have to install Drupal, proceed to the <a href="'. base_path() .'install.php">installation page</a>.</p>
<p>If you have already finished installed Drupal, this either means that the username and password information in your <code>settings.php</code> file is incorrect or that we can\'t connect to the PostgreSQL database server. This could mean your hosting provider\'s database server is down.</p>
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
    $bt = debug_backtrace();
    $query = $bt[2]['function'] . "\n" . $query;
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print '<p>query: '. $query .'<br />error:'. pg_last_error($active_db) .'</p>';
  }

  if ($last_result !== FALSE) {
    return $last_result;
  }
  else {
    trigger_error(check_plain(pg_last_error($active_db) ."\nquery: ". $query), E_USER_WARNING);
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
 *   The resulting field or FALSE.
 */
function db_result($result, $row = 0) {
  if ($result && pg_num_rows($result) > $row) {
    $res = pg_fetch_row($result, $row);

    return $res[0];
  }
  return FALSE;
}

/**
 * Determine whether the previous query caused an error.
 */
function db_error() {
  global $active_db;
  return pg_last_error($active_db);
}

/**
 * Return a new unique ID in the given sequence.
 *
 * For compatibility reasons, Drupal does not use auto-numbered fields in its
 * database tables. Instead, this function is used to return a new unique ID
 * of the type requested. If necessary, a new sequence with the given name
 * will be created.
 *
 * Note that the table name should be in curly brackets to preserve compatibility
 * with table prefixes. For example, db_next_id('{node}_nid');
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
 * Use this as a substitute for db_query() when a subset of the query
 * is to be returned.
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
  array_shift($args);

  $query = db_prefix_tables($query);
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  $query .= ' LIMIT '. (int)$count .' OFFSET '. (int)$from;
  return _db_query($query);
}

/**
 * Runs a SELECT query and stores its results in a temporary table.
 *
 * Use this as a substitute for db_query() when the results need to stored
 * in a temporary table. Temporary tables exist for the duration of the page
 * request.
 * User-supplied arguments to the query should be passed in as separate parameters
 * so that they can be properly escaped to avoid SQL injection attacks.
 *
 * Note that if you need to know how many results were returned, you should do
 * a SELECT COUNT(*) on the temporary table afterwards. db_num_rows() and
 * db_affected_rows() do not give consistent result across different database
 * types in this case.
 *
 * @param $query
 *   A string containing a normal SELECT SQL query.
 * @param ...
 *   A variable number of arguments which are substituted into the query
 *   using printf() syntax. The query arguments can be enclosed in one
 *   array instead.
 *   Valid %-modifiers are: %s, %d, %f, %b (binary data, do not enclose
 *   in '') and %%.
 *
 *   NOTE: using this syntax will cast NULL and FALSE values to decimal 0,
 *   and TRUE values to decimal 1.
 *
 * @param $table
 *   The name of the temporary table to select into. This name will not be
 *   prefixed as there is no risk of collision.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 */
function db_query_temporary($query) {
  $args = func_get_args();
  $tablename = array_pop($args);
  array_shift($args);

  $query = preg_replace('/^SELECT/i', 'CREATE TEMPORARY TABLE '. $tablename .' AS SELECT', db_prefix_tables($query));
  if (isset($args[0]) and is_array($args[0])) { // 'All arguments in one array' syntax
    $args = $args[0];
  }
  _db_query_callback($args, TRUE);
  $query = preg_replace_callback(DB_QUERY_REGEXP, '_db_query_callback', $query);
  return _db_query($query);
}

/**
 * Returns a properly formatted Binary Large OBject value.
 * In case of PostgreSQL encodes data for insert into bytea field.
 *
 * @param $data
 *   Data to encode.
 * @return
 *  Encoded data.
 */
function db_encode_blob($data) {
  return "'". pg_escape_bytea($data) ."'";
}

/**
 * Returns text from a Binary Large OBject value.
 * In case of PostgreSQL decodes data after select from bytea field.
 *
 * @param $data
 *   Data to decode.
 * @return
 *  Decoded data.
 */
function db_decode_blob($data) {
  return pg_unescape_bytea($data);
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
  db_query('BEGIN; LOCK TABLE {'. db_escape_table($table) .'} IN EXCLUSIVE MODE');
}

/**
 * Unlock all locked tables.
 * This function automatically commits a transaction.
 */
function db_unlock_tables() {
  db_query('COMMIT');
}

/**
 * Check if a table exists.
 */
function db_table_exists($table) {
  return db_num_rows(db_query("SELECT relname FROM pg_class WHERE relname = '{" . db_escape_table($table) . "}'"));
}

/**
 * Verify if the database is set up correctly.
 */
function db_check_setup() {
  $t = get_t();

  $encoding = db_result(db_query('SHOW server_encoding'));
  if (!in_array(strtolower($encoding), array('unicode', 'utf8'))) {
    drupal_set_message($t('Your PostgreSQL database is set up with the wrong character encoding (%encoding). It is possible it will not work as expected. It is advised to recreate it with UTF-8/Unicode encoding. More information can be found in the <a href="@url">PostgreSQL documentation</a>.', array('%encoding' => $encoding, '@url' => 'http://www.postgresql.org/docs/7.4/interactive/multibyte.html')), 'status');
  }
}

/**
 * Wraps the given table.field entry with a DISTINCT(). The wrapper is added to
 * the SELECT list entry of the given query and the resulting query is returned.
 * This function only applies the wrapper if a DISTINCT doesn't already exist in
 * the query.
 *
 * @param $table Table containing the field to set as DISTINCT
 * @param $field Field to set as DISTINCT
 * @param $query Query to apply the wrapper to
 * @return SQL query with the DISTINCT wrapper surrounding the given table.field.
 */
function db_distinct_field($table, $field, $query) {
  $field_to_select = 'DISTINCT ON ('. $table .'.'. $field .") $table.$field";
  // (?<!text) is a negative look-behind (no need to rewrite queries that already use DISTINCT).
  $query = preg_replace('/(SELECT.*)(?:'. $table .'\.|\s)(?<!DISTINCT\()(?<!DISTINCT\('. $table .'\.)'. $field .'(.*FROM )/AUsi', '\1 '. $field_to_select .'\2', $query);
  $query = preg_replace('/(ORDER BY )(?!'.$table.'\.'.$field.')/', '\1'."$table.$field, ", $query);
  return $query;
}

/**
 * @} End of "ingroup database".
 */


