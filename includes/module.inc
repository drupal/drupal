<?php
// $Id$

/**
 * Initialize all modules.
 *
 * To change the required set of modules, change this function as well as
 * system_listing() and module_list().
 */
function module_init() {
  require_once 'modules/admin.module';
  require_once 'modules/filter.module';
  require_once 'modules/system.module';
  require_once 'modules/user.module';
  require_once 'modules/watchdog.module';
  module_list();
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
 * If any modules are enabled but have not yet been loaded, this function performs
 * the include_once() to load them.
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
    unset($list);
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
          include_once $module->filename;
        }
      }
    }
    asort($list);
  }
  return $list;
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
 *
 * @{
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
    if (isset($result)) {
      $return = array_merge($return, $result);
    }
  }

  return $return;
}

/**
 * @} end of defgroup hooks
 */

?>
