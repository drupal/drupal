<?php
// $Id: database.inc,v 1.25 2003/07/10 17:46:41 dries Exp $

function db_prefix_tables($sql) {
  global $db_prefix;

  return strtr($sql, array("{" => $db_prefix, "}" => ""));
}

$db_type = substr($db_url, 0, strpos($db_url, "://"));

if ($db_type == "mysql") {
  include_once "includes/database.mysql.inc";
}
else {
  include_once "includes/database.pear.inc";
}

db_connect($db_url);

?>
