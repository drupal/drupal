<?php

function db_connect($url) {
  $url = parse_url($url);

  mysql_pconnect($url["host"], $url["user"], $url["pass"]) or die(mysql_error());
  mysql_select_db(substr($url["path"], 1)) or die ("unable to select database");

  // NOTE: we are using a persistent connection!
}

function db_query($query, $debug = 0) {

  $result = mysql_query($query);

  if ($debug) {
    print "<p>query: $query<br />error:". mysql_error() ."</p>";
  }

  if ($result) {
    return $result;
  }
  else {
    watchdog("error", "database: ". mysql_error() ."\nquery: ". htmlspecialchars($query));
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

?>