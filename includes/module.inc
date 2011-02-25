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
 * Collect a list of all loaded modules. During the bootstrap, return only
 * vital modules. See bootstrap.inc
 *
 * @param $refresh
 *   Whether to force the module list to be regenerated (such as after the
 *   administrator has changed the system settings).
 * @param $bootstrap
 *   Whether to return the reduced set of modules loaded in "bootstrap mode"
 *   for cached pages. See bootstrap.inc.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $bootstrap = TRUE) {
  static $list;

  if ($refresh) {
    $list = array();
  }

  if (!$list) {
    $list = array('filter' => 'filter', 'system' => 'system', 'user' => 'user', 'watchdog' => 'watchdog');
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
        $throttle = ($module->throttle && variable_get('throttle_level', 0) > 0);
        if (!$throttle) {
          drupal_get_filename('module', $module->name, $module->filename);
          $list[$module->name] = $module->name;
        }
      }
    }
    asort($list);
  }
  return $list;
}

/**
 * Load all the modules that have been enabled in the system table.
 *
 * @return
 *   TRUE if all modules were loaded successfully.
 */
function module_load_all() {
  $list = module_list(TRUE, FALSE);
  $status = TRUE;

  foreach ($list as $module) {
    $status = (drupal_load('module', $module) && $status);
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
 * Determine which modules are implementing a hook.
 *
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu").
 * @return
 *   An array with the names of the modules which are implementing this hook.
 */
function module_implements($hook) {
  static $implementations;

  if (!isset($implementations[$hook])) {
    $implementations[$hook] = array();
    $list = module_list();
    foreach ($list as $module) {
      if (module_hook($module, $hook)) {
        $implementations[$hook][] = $module;
      }
    }
  }

  return $implementations[$hook];
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
function module_invoke() {
  $args = func_get_args();
  $module = array_shift($args);
  $hook = array_shift($args);
  $function = $module .'_'. $hook;
  if (module_hook($module, $hook)) {
    return call_user_func_array($function, $args);
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
function module_invoke_all() {
  $args = func_get_args();
  $hook = array_shift($args);
  $return = array();
  foreach (module_implements($hook) as $module) {
    $function = $module .'_'. $hook;
    $result = call_user_func_array($function, $args);
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
