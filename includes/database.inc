<?php

require_once 'DB.php';

/*
** Setup a database connection:
*/

$db_handle = DB::connect($db_url);
if (DB::isError($db_handle)) {
  die ("Database problem: ". $db_handle->getMessage());
}
$db_handle->setFetchMode(DB_FETCHMODE_ASSOC);

function db_query($sql, $debug = 0) {
  global $db_handle;

  $result = $db_handle->query($sql);
  if (DB::isError($result)) {
    //if ($debug) {
      print "<p>query: $sql<br />error:". $result->getMessage() ."</p>";
    //}
    watchdog("error", "database: ". $result->getMessage() ."\nquery: ". htmlspecialchars($sql));
  }
  return $result;
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

function db_result($result, $field = 0) {
  if ($result) {
    $tmp = $result->fetchRow(DB_FETCHMODE_ORDERED);
    return $tmp[$field];
  }
}

function db_error($result) {
  global $db_handle;

  if (DB::isError($db_handle)) {
    return 1;
  }

  return 0;
}

?>