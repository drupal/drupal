<?php

$db_type = substr($db_url, 0, strpos($db_url, "://"));

if ($db_type == "mysql") {
  include_once "includes/database.mysql.inc";
}
else {
  include_once "includes/database.pear.inc";
}

db_connect($db_url);

?>