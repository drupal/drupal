<?php

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM variable");
  while ($variable = db_fetch_object($result)) $conf[$variable->name] = $variable->value;
  return $conf;
}

function handler_post_threshold($node, $default) {
  if ($node->type) {
    $function = $node->type ."_post_threshold";
    return $function($node, $default);
  }
  else {
    return $default;
  }
}

function handler_dump_threshold($node, $default) {
  if ($node->type) {
    $function = $node->type ."_dump_threshold";
    return $function($node, $default);
  }
  else {
    return $default;
  }
}

function handler_timout_threshold($node, $default) {
  if ($node->type) {
    $function = $node->type ."_timout_threshold";
    return $function($node, $default);
  }
  else {
    return $default;
  }
}

function variable_get($name, $default, $object = 0) {
  global $conf;

  switch ($name) {
    case "post_threshold":
      return handler_post_threshold($object, $default);
    case "dump_threshold":
      return handler_dump_threshold($object, $default);
    case "timout_threshold":
      return handler_timout_threshold($object, $default);
    default:
      return ($conf[$name] ? $conf[$name] : $default);
  }
}

?>