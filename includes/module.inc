<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

/**
 * Load all the modules that have been enabled in the system table.
 */
function module_load_all() {
  foreach (module_list(TRUE, FALSE) as $module) {
    drupal_load('module', $module);
  }
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
 * @param $sort
 *   By default, modules are ordered by weight and filename, settings this option
 *   to TRUE, module list will be ordered by module name.
 * @param $fixed_list
 *   (Optional) Override the module list with the given modules. Stays until the
 *   next call with $refresh = TRUE.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $bootstrap = TRUE, $sort = FALSE, $fixed_list = NULL) {
  static $list, $sorted_list;

  if ($refresh || $fixed_list) {
    unset($sorted_list);
    $list = array();
    if ($fixed_list) {
      foreach ($fixed_list as $name => $module) {
        drupal_get_filename('module', $name, $module['filename']);
        $list[$name] = $name;
      }
    }
    else {
      if ($bootstrap) {
        $result = db_query("SELECT name, filename, throttle FROM {system} WHERE type = 'module' AND status = 1 AND bootstrap = 1 ORDER BY weight ASC, filename ASC");
      }
      else {
        $result = db_query("SELECT name, filename, throttle FROM {system} WHERE type = 'module' AND status = 1 ORDER BY weight ASC, filename ASC");
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
    }
  }
  if ($sort) {
    if (!isset($sorted_list)) {
      $sorted_list = $list;
      ksort($sorted_list);
    }
    return $sorted_list;
  }
  return $list;
}

/**
 * Rebuild the database cache of module files.
 *
 * @return
 *   The array of filesystem objects used to rebuild the cache.
 */
function module_rebuild_cache() {
  // Get current list of modules
  $files = drupal_system_listing('\.module$', 'modules', 'name', 0);

  // Extract current files from database.
  system_get_files_database($files, 'module');

  ksort($files);

  foreach ($files as $filename => $file) {
    $file->info = _module_parse_info_file(dirname($file->filename) .'/'. $file->name .'.info');
    // Skip modules that don't provide info.
    if (empty($file->info)) {
      unset($files[$filename]);
      continue;
    }
    $files[$filename]->info = $file->info;

    // log the critical hooks implemented by this module
    $bootstrap = 0;
    foreach (bootstrap_hooks() as $hook) {
      if (module_hook($file->name, $hook)) {
        $bootstrap = 1;
        break;
      }
    }

    // Update the contents of the system table:
    // TODO: We shouldn't actually need this description field anymore. Remove me next release.
    if (isset($file->status) || (isset($file->old_filename) && $file->old_filename != $file->filename)) {
      db_query("UPDATE {system} SET description = '%s', name = '%s', filename = '%s', bootstrap = %d WHERE filename = '%s'", $file->info['description'], $file->name, $file->filename, $bootstrap, $file->old_filename);
    }
    else {
      // This is a new module.
      db_query("INSERT INTO {system} (name, description, type, filename, status, throttle, bootstrap) VALUES ('%s', '%s', '%s', '%s', %d, %d, %d)", $file->name, $file->info['description'], 'module', $file->filename, $file->status, $file->throttle, $bootstrap);
    }
  }
  $files = _module_build_dependents($files);
  return $files;
}

/**
 * Find dependents; modules that are required by other modules.
 * Adds an array of dependents to the $file->info array.
 *
 * @return
 *   The list of files array with dependents added where applicable.
 */
function _module_build_dependents($files) {
  foreach ($files as $filename => $file) {
    if (isset($file->info['dependencies']) && is_array($file->info['dependencies'])) {
      foreach ($file->info['dependencies'] as $dependency) {
        if (!empty($files[$dependency]) && is_array($files[$dependency]->info)) {
          if (!isset($files[$dependency]->info['dependents'])) {
            $files[$dependency]->info['dependents'] = array();
          }
          $files[$dependency]->info['dependents'][] = $filename;
        }
      }
    }
  }
  return $files;
}

/**
 * Parse Drupal info file format.
 * Uses ini parser provided by php's parse_ini_file().
 *
 * Files should use the ini format to specify values.
 * e.g.
 * key = "value"
 * key2 = value2
 *
 * Some things to be aware of:
 * - This function is NOT for placing arbitrary module-specific settings. Use variable_get()
 *   and variable_set() for that.
 * - You may not use double-quotes in a value.
 *
 * Information stored in the module.info file:
 * name - The real name of the module for display purposes.
 * description - A brief description of the module.
 * dependencies - A space delimited list of the short names (shortname) of other modules this module depends on.
 * package - The name of the package of modules this module belongs to.
 *
 * Example of .info file:
 *   name = Forum
 *   description = Enables threaded discussions about general topics.
 *   dependencies = taxonomy comment
 *   package = Core - optional
 *
 * @param $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 * @return
 *   The info array.
 */
function _module_parse_info_file($filename) {
  $info = array();

  if (file_exists($filename)) {
    $info = parse_ini_file($filename);

    if (isset($info['dependencies'])) {
      $info['dependencies'] = explode(" ", $info['dependencies']);
    }
    else {
      $info['dependencies'] = NULL;
    }
  }
  return $info;
}

/**
 * Determine whether a given module exists.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 * @return
 *   TRUE if the module is both installed and enabled.
 */
function module_exists($module) {
  $list = module_list();
  return array_key_exists($module, $list);
}

/**
 * Load a module's installation hooks.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once './includes/install.inc';

  $install_file = './'. drupal_get_path('module', $module) .'/'. $module .'.install';
  if (is_file($install_file)) {
    include_once $install_file;
  }
}

/**
 * Enable a given list of modules.
 *
 * @param $module_list
 *   An array of module names.
 */
function module_enable($module_list) {
  $invoke_modules = array();
  foreach ($module_list as $module) {
    $existing = db_fetch_object(db_query("SELECT status FROM {system} WHERE type = 'module' AND name = '%s'", $module));
    if ($existing->status === '0') {
      module_load_install($module);
      db_query("UPDATE {system} SET status = 1, throttle = 0 WHERE type = 'module' AND name = '%s'", $module);
      drupal_load('module', $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to include the new enabled module.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    module_implements('', FALSE, TRUE);
    cache_clear_all('*', 'cache_menu', TRUE);
  }

  foreach ($invoke_modules as $module) {
    module_invoke($module, 'enable');
  }
}

/**
 * Disable a given set of modules.
 *
 * @param $module_list
 *   An array of module names.
 */
function module_disable($module_list) {
  $invoke_modules = array();
  foreach ($module_list as $module) {
    if (module_exists($module)) {
      module_load_install($module);
      module_invoke($module, 'disable');
      db_query("UPDATE {system} SET status = 0, throttle = 0 WHERE type = 'module' AND name = '%s'", $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to exclude the disabled modules.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    module_implements('', FALSE, TRUE);
    cache_clear_all('*', 'cache_menu', TRUE);
  }
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
 * @param $sort
 *   By default, modules are ordered by weight and filename, settings this option
 *   to TRUE, module list will be ordered by module name.
 * @param $refresh
 *   For internal use only: Whether to force the stored list of hook
 *   implementations to be regenerated (such as after enabling a new module,
 *   before processing hook_enable).
 * @return
 *   An array with the names of the modules which are implementing this hook.
 */
function module_implements($hook, $sort = FALSE, $refresh = FALSE) {
  static $implementations;

  if ($refresh) {
    $implementations = array();
    return;
  }

  if (!isset($implementations[$hook])) {
    $implementations[$hook] = array();
    $list = module_list(FALSE, TRUE, $sort);
    foreach ($list as $module) {
      if (module_hook($module, $hook)) {
        $implementations[$hook][] = $module;
      }
    }
  }

  // The explicit cast forces a copy to be made. This is needed because
  // $implementations[$hook] is only a reference to an element of
  // $implementations and if there are nested foreaches (due to nested node
  // API calls, for example), they would both manipulate the same array's
  // references, which causes some modules' hooks not to be called.
  // See also http://www.zend.com/zend/art/ref-count.php.
  return (array)$implementations[$hook];
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
    if (isset($result) && is_array($result)) {
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


