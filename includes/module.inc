<?php

// initialize modules:
function module_init() {
  // Note: changing this also requires changing system_listing() @ modules/system.module.
  require_once "modules/admin.module";
  require_once "modules/filter.module";
  require_once "modules/system.module";
  require_once "modules/user.module";
  require_once "modules/watchdog.module";
  module_list();
  module_invoke_all("init");
}

// apply function $function to every known module:
function module_iterate($function, $argument = "") {
  foreach (module_list() as $name) {
    $function($name, $argument);
  }
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
    $result = module_invoke($name, $hook, $a1, $a2, $a3, $a4);
    if (isset($result)) {
      $return = array_merge($return, $result);
    }
  }

  return $return;
}

// return array of module names (includes lazy module loading if not in bootstrap mode)
function module_list($refresh = 0, $bootstrap = 0) {
  static $list;

  if ($refresh) {
    unset($list);
  }

  if (!$list) {
    $list = array("admin" => "admin", "filter" => "filter", "system" => "system", "user" => "user", "watchdog" => "watchdog");
    if ($bootstrap) {
      $result = db_query("SELECT name, filename, throttle, bootstrap FROM {system} WHERE type = 'module' AND status = 1 AND bootstrap = 1");
    }
    else {
      $result = db_query("SELECT name, filename, throttle, bootstrap FROM {system} WHERE type = 'module' AND status = 1");
    }
    while ($module = db_fetch_object($result)) {
      if (file_exists($module->filename)) {
        /*
        ** Determine the current throttle status and see if module should be
        **  loaded based on server load.  We have to directly access the
        **  throttle variables as the throttle.module may not be loaded yet.
        */
        $throttle = ($module->throttle && variable_get("throttle_level", 0) > 4);
        if (!$throttle) {
          $list[$module->name] = $module->name;
          include_once $module->filename;
        }
      }
    }
    asort($list);
  }
  return $list;
}

// return 1 if module $name exists, 0 otherwise:
function module_exist($name) {
  $list = module_list();
  return isset($list[$name]) ? 1 : 0;
}

// return 1 if module $name implements hook $hook, 0 otherwise:
function module_hook($name, $hook) {
  return function_exists($name ."_". $hook);
}

?>
