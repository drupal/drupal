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
 * @param $query sql query
 * @param $type  module type of this item
 * @return sql result resource
 */
function db_query($query) {
  $args = func_get_args();
  if (count($args) > 1) {
    $args = array_map("check_query", $args);
    $args[0] = $query;
    return _db_query(call_user_func_array("sprintf", $args));
  }
  else {
    return _db_query($query);
  }
}

// debug version
function db_queryd($query) {
  $args = func_get_args();
  if (count($args) > 1) {
    $args = array_map("check_query", $args);
    $args[0] = $query;
    return _db_query(call_user_func_array("sprintf", $args), 1);
  }
  else {
    return _db_query($query, 1);
  }
}

// private
function _db_query($query, $debug = 0) {
  global $db_handle, $queries;

  if (variable_get("dev_query", 0)) {
    $queries[] = $query;
  }

  $result = $db_handle->query($query);

  if ($debug) {
    print "<p>query: $query<br />";
  }

  if (DB::isError($result)) {
    watchdog("error", "database: ". $result->getMessage() ."\nquery: ". htmlspecialchars($query));
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

  return $db_handle->nextID($name);
}

function db_affected_rows() {
  global $db_handle;

  return $db_handle->affectedRows();
}

/**
 * Generates a limited query
 *
 * @param string  $query query
 * @param integer $from  the row to start to fetching
 * @param integer $count the numbers of rows to fetch
 *
 * @return mixed a DB_Result object or a DB_Error
 *
 * @access public
 */

function db_query_range($query, $from, $count) {
  global $db_handle;

  return $db_handle->limitQuery($query, $from, $count);
}

?>
