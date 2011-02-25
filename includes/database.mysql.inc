<?php

function db_connect($url) {
  $url = parse_url($url);

  // Allow for non-standard MySQL port.
  if (isset($url["port"])) {
     $url["host"] = $url["host"] . ":" . $url["port"];
  }

  mysql_pconnect($url["host"], $url["user"], $url["pass"]) or die(mysql_error());
  mysql_select_db(substr($url["path"], 1)) or die("unable to select database");

  // NOTE: we are using a persistent connection!
}

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
  global $queries;

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $timer = (float)$usec + (float)$sec;
  }

  $result = mysql_query($query);

  if (variable_get("dev_query", 0)) {
    list($usec, $sec) = explode(" ", microtime());
    $stop = (float)$usec + (float)$sec;
    $diff = $stop - $timer;
    $queries[] = array($query, $diff);
  }

  if ($debug) {
    print "<p>query: $query<br />error:". mysql_error() ."</p>";
  }

  if (!mysql_errno()) {
    return $result;
  }
  else {
    trigger_error(mysql_error() ."\nquery: ". htmlspecialchars($query), E_USER_ERROR);
  }
}

function db_fetch_object($result) {
  if ($result) {
    return mysql_fetch_object($result);
  }
}

function db_fetch_array($result) {
  if ($result) {
    return mysql_fetch_array($result, MYSQL_ASSOC);
  }
}

function db_num_rows($result) {
  if ($result) {
    return mysql_num_rows($result);
  }
}

function db_result($result, $row = 0) {
  if ($result && mysql_num_rows($result) > $row) {
    return mysql_result($result, $row);
  }
}

function db_error() {
  return mysql_errno();
}

function db_next_id($name) {

  /*
  ** Note that REPLACE query below correctly creates a new sequence
  ** when needed
  */

  db_query("LOCK TABLES sequences WRITE");
  $id = db_result(db_query("SELECT id FROM sequences WHERE name = '%s'", $name)) + 1;
  db_query("REPLACE INTO sequences VALUES ('%s', '%d')", $name, $id);
  db_query("UNLOCK TABLES");

  return $id;
}

function db_affected_rows() {
  return mysql_affected_rows();
}

/**
 * Generates a limited query
 *
 * @param string  $query query
 * @param integer $from  the row to start to fetching
 * @param integer $count the numbers of rows to fetch
 *
 * @access public
 */
function db_query_range($query, $from, $count) {
  $query .= " LIMIT $from, $count";
  // TODO: debug version
  return db_query($query);
}

?>