<?php

function db_connect($host, $user, $pass, $name) {
  mysql_pconnect($host, $user, $pass) or die(mysql_error());
  mysql_select_db($name) or die ("unable to select database");
  // NOTE: we are using a persistent connection!
}


function db_query($query, $debug = 0) {
  $result = mysql_query($query);
  if ($debug) print "<P>query: $query<BR>error:". mysql_error() ."</P>";
  if ($result) return $result;
  else watchdog("error", "database: ". mysql_error() ."\nquery: ". htmlspecialchars($query));
}

function db_fetch_object($qid) {
  if ($qid) return mysql_fetch_object($qid);
}

function db_num_rows($qid) {
  if ($qid) return mysql_num_rows($qid);
}

function db_fetch_row($qid) {
  if ($qid) return mysql_fetch_row($qid);
}

function db_fetch_array($qid) {
  if ($qid) return mysql_fetch_array($qid);
}

function db_result($qid, $field = 0) {
  if ($qid) return mysql_result($qid, $field);
}

function db_insert_id() {
  return mysql_insert_id();
}

// Setup database connection:
db_connect($db_host, $db_user, $db_pass, $db_name);

?>