<?php

// initialize modules:
function module_init() {
  require_once "modules/user.module";
  require_once "modules/drupal.module";
  require_once "modules/system.module";
  require_once "modules/watchdog.module";
  module_list();
}

// apply function $function to every known module:
function module_iterate($function, $argument = "") {
  foreach (module_list() as $name) $function($name, $argument);
}

// invoke hook $hook of module $name with optional arguments:
function module_invoke($name, $hook, $a1 = NULL, $a2 = NULL, $a3 = NULL, $a4 = NULL) {
  $function = $name ."_". $hook;
  if (function_exists($function)) {
    return $function($a1, $a2, $a3, $a4);
  }
}

// invoke $hook for all appropriate modules:
function module_invoke_all($hook, $a1 = NULL, $a2 = NULL, $a3 = NULL, $a4 = NULL) {
  $return = array();
  foreach (module_list() as $name) {
    if (module_hook($name, $hook)) {
      if ($result = module_invoke($name, $hook, $a1, $a2, $a3, $a4)) {
        $return = array_merge($return, $result);
      }
    }
  }

  return $return;
}

// return array of module names (includes lazy module loading):
function module_list() {
  static $list;

  if (!$list) {
    $list = array("drupal" => "drupal", "system" => "system", "user" => "user", "watchdog" => "watchdog");
    $result = db_query("SELECT name, filename FROM system WHERE type = 'module' AND status = '1' ORDER BY name");
    while ($module = db_fetch_object($result)) {
      $list[$module->name] = $module->name;
      if (file_exists("modules/$module->filename")) {
        include_once "modules/$module->filename";
      }
    }
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
    foreach ($blocks as $delta => $block) {
      foreach ($block as $item => $data) {
        $block[$item] = addslashes($data);
      }
      if (!db_fetch_object(db_query("SELECT * FROM blocks WHERE module = '$name' AND name = '$block[info]'"))) {
        db_query("INSERT INTO blocks (name, module, delta) VALUES ('$block[info]', '$name', '$delta')");
      }
      else {
        db_query("UPDATE blocks SET delta = '$delta', remove = '0' WHERE module = '$name' AND name = '$block[info]'");
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