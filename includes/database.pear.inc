<?php
// $Id$

require_once 'DB.php';

function db_connect($url) {
  global $db_handle;

  $db_handle = DB::connect($url);

  if (DB::isError($db_handle)) {
    die("Database problem: ". $db_handle->getMessage());
  }

  $db_handle->setFetchMode(DB_FETCHMODE_ASSOC);
}

function db_query($query, $debug = 0) {
  global $db_handle, $queries;

  if (variable_get("dev_query", 0)) {
    $queries[] = $query;
  }

  $result = $db_handle->query($query);

  if ($debug) {
    print "<p>query: $query<br />"; // error:". $result->getMessage() ."</p>";
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

function db_error($result) {
  global $db_handle;

  return DB::isError($db_handle);
}

?>