<?php

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM variable");
  while ($variable = db_fetch_object($result)) $conf[$variable->name] = $variable->value;
  return $conf;
}

function variable_get($name, $default, $object = 0) {
  global $conf;
  return isset($conf[$name]) ? $conf[$name] : $default;
}

function variable_set($name, $value) {
  global $conf;

  db_query("DELETE FROM variable WHERE name = '". check_query($name) ."'");
  db_query("INSERT INTO variable (name, value) VALUES ('". check_query($name) ."', '". check_query($value) ."')");

  $conf[$name] = $value;
}

function variable_del($name) {
  global $conf;

  db_query("DELETE FROM variable WHERE name = '". check_query($name) ."'");

  $conf[$name] = "";
}

?>