<?php
// $Id$

function db_prefix_tables($sql) {
  global $db_prefix;

  if (is_array($db_prefix)) {
    $prefix = $db_prefix["default"];
    foreach ($db_prefix as $key => $val) {
      if ($key !== "default") {
        $sql = strtr($sql, array("{". $key. "}" => $val. $key));
      }
    }
  }
  else {
    $prefix = $db_prefix;
  }
  return strtr($sql, array("{" => $prefix, "}" => ""));
}


/**
* Use the specified database connection for queries. Initialize the connection if it does not already exist,
* and if no such member exists, a duplicate of the default connection is made.
* Be very careful to switch the connection back to the default connection, so as to avoid errors. As the $name
* parameter defaults to 'default', you only need to run db_set_active() without any arguments to use
* the default database
*
* @param $name The named connection specified in the $db_url variable.
*/
function db_set_active($name = 'default') {
  global $db_url;
  global $active_db;
  static $db_conns;

  if (!isset($db_conns[$name])) {
    //Initiate a new connection, using the named db url specified
    if (is_array($db_url)) {
      $connect_url = ($db_url[$name]) ? $db_url[$name] : $db_url['default'];
    }
    else {
      $connect_url = $db_url;
    }


    $db_type = substr($connect_url, 0, strpos($connect_url, "://"));

    //TODO : Allow more than one database api to be present. ie: pgsl and mysql
    if ($db_type == "mysql") {
      include_once "includes/database.mysql.inc";
    }
    else {
      include_once "includes/database.pear.inc";
    }

    $db_conns[$name] = db_connect($connect_url);

  }
  //set the active connection
  $active_db = $db_conns[$name];


}


// initialize the default db_url
db_set_active();


?>
