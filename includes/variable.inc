<?php

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM variable");
  while ($variable = db_fetch_object($result)) $conf[$variable->name] = $variable->value;
  return $conf;
}

function handler_post_threshold($node, $default) {
  $threshold = category_post_threshold($node->cid);
  return $threshold ? $threshold : $default;
}

function handler_dump_threshold($node, $default) {
  $threshold = category_dump_threshold($node->cid);
  return $threshold ? $threshold : $default;
}

function handler_expire_threshold($node, $default) {
  $threshold = category_expire_threshold($node->cid);
  return $threshold ? $threshold : $default;
}

function variable_get($name, $default, $object = 0) {
  global $conf;

  switch ($name) {
    case "post_threshold":
      return handler_post_threshold($object, $default);
    case "dump_threshold":
      return handler_dump_threshold($object, $default);
    case "expire_threshold":
      return handler_expire_threshold($object, $default);
    default:
      return $conf[$name] ? $conf[$name] : $default;
  }
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