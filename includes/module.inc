<?php

// initialize modules:
function module_init() {
  module_list();
}

// apply function $function to every known module:
function module_iterate($function, $argument = "") {
  foreach (module_list() as $name) $function($name, $argument);
}

// invoke hook $hook of module $name with optional arguments:
function module_invoke($name, $hook, $argument = "") {
  $function = $name ."_". $hook;
  if (function_exists($function)) return $function($argument);
}

// return true if module $name supports hook $hook, and false otherwise:
function module_is_hook($name, $hook) {
  return function_exists($name ."_". $hook);
}

// return an array of module names (includes lazy module loading):
function module_list() {
  static $list;

  if (!$list) {
    $handle = opendir("modules");
    $list = array();
    while ($file = readdir($handle)) {
      if (".module" == substr($file, -7)) {
        $filename = substr($file, 0, -7);
        include "modules/$filename.module";
        $list[$filename] = $filename;
      }
    }
    closedir($handle);
    asort($list);
  }

  return $list;
}

// return 1 if module $name exists, 0 otherwise:
function module_exist($name) {
  $list = module_list();
  return ($list[$name]) ? 1 : 0;
}

// return 1 if module $name implements hook $hook, 0 otherwise:
function module_hook($name, $hook) {
  return function_exists($name ."_". $hook);
}

// rehash module-exported blocks:
function module_rehash_blocks($name) {
  db_query("UPDATE blocks SET remove = '1' WHERE module = '$name'");
  if ($blocks = module_invoke($name, "block")) {
    foreach ($blocks as $offset=>$block) {
      foreach ($block as $item=>$data) {
        $block[$item] = addslashes($data);
      }
      if (!db_fetch_object(db_query("SELECT * FROM blocks WHERE module = '$name' AND name = '$block[info]'"))) {
        db_query("INSERT INTO blocks (name, module, offset) VALUES ('$block[info]', '$name', '$offset')");
      }
      else {
        db_query("UPDATE blocks SET offset = '$offset', remove = '0' WHERE module = '$name' AND name = '$block[info]'");
      }
    }
  }
  db_query("DELETE FROM blocks WHERE module = '$name' AND remove = '1'");
}

// rehash a module:
function module_rehash($name) {
  if (module_exist($name)) {
    $result = db_query("SELECT * FROM modules WHERE name = '$name'");

    if (!$object = db_fetch_object($result)) {
      db_query("INSERT INTO modules (name) VALUES ('$name')");
    }

    // rehash module-exported blocks (if necessary):
    module_rehash_blocks($name);
  }
  else {
    // remove all reference to module:
    db_query("DELETE FROM modules WHERE name = '$name'");
    db_query("DELETE FROM blocks WHERE module = '$name'");
  }
}

?>