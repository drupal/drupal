<?php

$db_type = substr($db_url, 0, strpos($db_url, "://"));

if (file_exists("includes/database.$db_type.inc")) {
  include_once "includes/database.$db_type.inc";
}
else {
  include_once "includes/database.pear.inc";
}

db_connect($db_url);

?>