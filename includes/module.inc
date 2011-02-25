<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

/**
 * Initialize all modules.
 *
 */
function module_init() {
  module_load_all();
  module_invoke_all('init');
}

/**
 * Call a function repeatedly with each module in turn as an argument.
 */
function module_iterate($function, $argument = '') {
  foreach (module_list() as $name) {
    $function($name, $argument);
  }
}

/**
 * Collect a list of all installed and enabled modules.
 *
 * @param $refresh
 *   Whether to force the module list to be regenerated (such as after the
 *   administrator has changed the system settings).
 * @param $bootstrap
 *   Whether to return the reduced set of modules loaded in "bootstrap mode" for
 *   cached pages. See bootstrap.inc.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $bootstrap = FALSE) {
  static $list;

  if ($refresh) {
    $list = array();
  }

  if (!$list) {
    $list = array('admin' => 'admin', 'filter' => 'filter', 'system' => 'system', 'user' => 'user', 'watchdog' => 'watchdog');
    if ($bootstrap) {
      $result = db_query("SELECT name, filename, throttle, bootstrap FROM {system} WHERE type = 'module' AND status = 1 AND bootstrap = 1");
    }
    else {
      $result = db_query("SELECT name, filename, throttle, bootstrap FROM {system} WHERE type = 'module' AND status = 1");
    }
    while ($module = db_fetch_object($result)) {
      if (file_exists($module->filename)) {
        // Determine the current throttle status and see if the module should be
        // loaded based on server load. We have to directly access the throttle
        // variables, since throttle.module may not be loaded yet.
        $throttle = ($module->throttle && variable_get('throttle_level', 0) > 4);
        if (!$throttle) {
          $list[$module->name] = $module->name;
          module_set_filename($module->name, $module->filename);
        }
      }
    }
    asort($list);
  }
  return $list;
}

/**
 * Set the filename of a module, for future loading through module_load()
 *
 * @param $module
 *   Name of the module which to specify the filename of.
 * @param $pa
 *   Filename of the module named $module.
 * @return
 *   Filename of module, if no $path has been specified.
 */
function module_set_filename($module, $path = null) {
  static $list = array();

  if ($path) {
    $list[$module] = $path;
  }
  else {
    return array_key_exists($module, $list) ? $list[$module] : "modules/$module.module";
  }
}

/**
 * Retrieve the filename of a module
 *
 * @param $module
 *   Name of the module which to retrieve the filename of.
 * @return
 *   Filename of module.
 */
function module_get_filename($module) {
  return module_set_filename($module);
}

/**
 * Retrieve the path of a module
 *
 * @param $module
 *   Name of the module which to retrieve the path of.
 * @return
 *   Path of module.
 */
function module_get_path($module) {
  return dirname(module_get_filename($module));
}

/**
 * Load a module into Drupal, but check first whether a module by the same name
 * has been loaded, and that the filename being included exists.
 * @param $module
 *   The name of the module to be loaded.
 * @return
 *   TRUE if the load was successful.
 */
function module_load($module) {
  static $loaded = array();

  if (!array_key_exists($module, $loaded)) {
    $filename = module_get_filename($module);
    if (file_exists($filename)) {
      include_once($filename);
      $loaded[$module] = $filename;
      return true;
    }
  }
  return false;
}


/**
 * Load all the modules that have been enabled in the system table.
 *
 * @return
 *   TRUE if all modules were loaded successfully
 */
function module_load_all() {
  $list = module_list(TRUE, FALSE);
  $status = true;
  foreach ($list as $module) {
    $status &= module_load($module);
  }
  return $status;
}


/**
 * Determine whether a given module exists.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 * @return
 *   TRUE if the module is both installed and enabled.
 */
function module_exist($module) {
  $list = module_list();
  return array_key_exists($module, $list);
}

/**
 * @defgroup hooks Hooks
 * @{
 * Allow modules to interact with the Drupal core.
 *
 * Drupal's module system is based on the concept of "hooks". A hook is a PHP
 * function that is named foo_bar(), where "foo" is the name of the module (whose
 * filename is thus foo.module) and "bar" is the name of the hook. Each hook has
 * a defined set of parameters and a specified result type.
 *
 * To extend Drupal, a module need simply implement a hook. When Drupal wishes to
 * allow intervention from modules, it determines which modules implement a hook
 * and call that hook in all enabled modules that implement it.
 *
 * The available hooks to implement are explained here in the Hooks section of
 * the developer documentation. The string "hook" is used as a placeholder for
 * the module name is the hook definitions. For example, if the module file is
 * called example.module, then hook_help() as implemented by that module would be
 * defined as example_help().
 */

/**
 * Determine whether a module implements a hook.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu").
 * @return
 *   TRUE if the module is both installed and enabled, and the hook is
 *   implemented in that module.
 */
function module_hook($module, $hook) {
  return function_exists($module .'_'. $hook);
}

/**
 * Invoke a hook in a particular module.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 * @param $hook
 *   The name of the hook to invoke.
 * @param ...
 *   Arguments to pass to the hook implementation.
 * @return
 *   The return value of the hook implementation.
 */
function module_invoke($module, $hook, $a1 = NULL, $a2 = NULL, $a3 = NULL, $a4 = NULL) {
  $function = $module .'_'. $hook;
  if (function_exists($function)) {
    return $function($a1, $a2, $a3, $a4);
  }
}

/**
 * Invoke a hook in all enabled modules that implement it.
 *
 * @param $hook
 *   The name of the hook to invoke.
 * @param ...
 *   Arguments to pass to the hook.
 * @return
 *   An array of return values of the hook implementations. If modules return
 *   arrays from their implementations, those are merged into one array.
 */
function module_invoke_all($hook, $a1 = NULL, $a2 = NULL, $a3 = NULL, $a4 = NULL) {
  $return = array();
  foreach (module_list() as $module) {
    $result = module_invoke($module, $hook, $a1, $a2, $a3, $a4);
    if (is_array($result)) {
      $return = array_merge($return, $result);
    }
    else if (isset($result)) {
      $return[] = $result;
    }
  }

  return $return;
}

/**
 * @} End of "defgroup hooks".
 */

?>
