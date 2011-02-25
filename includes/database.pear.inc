<?php

require_once 'DB.php';

function db_connect($url) {
  global $db_handle;

  $db_handle = DB::connect($url);

  if (DB::isError($db_handle)) {
    die("Database problem: ". $db_handle->getMessage());
  }

  $db_handle->setFetchMode(DB_FETCHMODE_ASSOC);
}

/**
 * Runs a query in the database.
 *
 * @param  $query SQL query
 * @param  $type  module type of this item
 * @return sql    result resource
 */
function db_query($query) {

  $args = func_get_args();

  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    if(is_array($args[1])){
      $args1 = array_map("check_query", $args[1]);
      $nargs = array_merge(array($query), $args1);
    }
    else {
      $nargs = array_map("check_query", $args);
      $nargs[0] = $query;
    }
    return _db_query(call_user_func_array("sprintf", $nargs));
  }
  else {
    return _db_query($query);
  }
}

// debug version
function db_queryd($query) {
  $args = func_get_args();
  $query = db_prefix_tables($query);
  if (count($args) > 1) {
    if(is_array($args[1])){
      $args1 = array_map("check_query", $args[1]);
      $nargs = array_merge(array($query), $args1);
    }
    else {
      $nargs = array_map("check_query", $args);
      $nargs[0] = $query;
    }
    return _db_query(call_user_func_array("sprintf", $nargs), 1);
  }
  else {
    return _db_query($query, 1);
  }
}

// private
function _db_query($query, $debug = 0) {
  global $db_handle, $queries;

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $result = $db_handle->query($query);

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print "<p>query: $query</p>";
  }

  if (DB::isError($result)) {
    trigger_error($result->getMessage() ."\nquery: ". htmlspecialchars($query), E_USER_ERROR);
  }
  else {
    return $result;
  }
}

function db_fetch_object($result) {
  if ($result) {
    return $result->fetchRow(DB_FETCHMODE_OBJECT);
  }
}

function db_fetch_array($result) {
  if ($result) {
    return $result->fetchRow(DB_FETCHMODE_ASSOC);
  }
}

function db_num_rows($result) {
  if ($result) {
    return $result->numRows($result);
  }
}

function db_result($result, $row = 0) {
  if ($result && $result->numRows($result) > $row) {
    $tmp = $result->fetchRow(DB_FETCHMODE_ORDERED);
    return $tmp[$row];
  }
}

function db_error() {
  global $db_handle;

  return DB::isError($db_handle);
}

function db_next_id($name) {
  global $db_handle;

  $name = db_prefix_tables($name);
  $result = $db_handle->nextID($name);
  if (DB::isError($result)) {
    watchdog("error", "database: ". $result->getMessage() ."\nsequence table: $name");
  }
  else {
    return $result;
  }
}

function db_affected_rows() {
  global $db_handle;

  return $db_handle->affectedRows();
}

/**
 * Runs a LIMIT query in the database.
 *
 * @param   mixed   $query  SQL query followed by a variable number of arguments which are substituted into query by sprintf, followed by 'from' and 'count' parameters. 'from' is the row to start fetching, 'count' the numbers of rows to fetch.
 * @return  mixed   a DB_Result object or a DB_Error
 * @access public
 */
function db_query_range($query) {
  global $db_handle, $queries;

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $args = func_get_args();
  $count = array_pop($args);
  $from = array_pop($args);
  if (count(func_get_args()) > 3) {
    $args = array_map("check_query", $args);
    $query = db_prefix_tables($query);
    $args[0] = $query;
    $result = $db_handle->limitQuery(call_user_func_array("sprintf", $args), $from, $count);
  }
  else {
    $query = func_get_arg(0);
    $query = db_prefix_tables($query);
    $result = $db_handle->limitQuery( $query, $from, $count);
  }

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query. " [LIMIT $from, $count]", $diff);
  }

  if (DB::isError($result)) {
    watchdog("error", "database: ". $result->getMessage() ."\nquery: ". htmlspecialchars($query));
  }
  else {
    return $result;
  }
}

?>
