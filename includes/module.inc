<?php
// $Id$

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

/**
 * Pass this to module_implements when its cache needs to be written.
 */
define('MODULE_IMPLEMENTS_WRITE_CACHE', -1);

/**
 * Pass this to module_implements when its cache needs to be cleared.
 */
define('MODULE_IMPLEMENTS_CLEAR_CACHE', -2);


/**
 * Load all the modules that have been enabled in the system table.
 */
function module_load_all() {
  foreach (module_list(TRUE, FALSE) as $module) {
    drupal_load('module', $module);
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
 *   By default, modules are ordered by weight and filename. Set this option to
 *   TRUE to return a module list ordered only by module name.
 * @param $fixed_list
 *   (Optional) Override the module list with the given modules. Stays until the
 *   next call with $refresh = TRUE.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $bootstrap = TRUE, $sort = FALSE, $fixed_list = NULL) {
  static $list = array(), $sorted_list;

  if (empty($list) || $refresh || $fixed_list) {
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
        $result = db_query("SELECT name, filename FROM {system} WHERE type = 'module' AND status = 1 AND bootstrap = 1 ORDER BY weight ASC, filename ASC");
      }
      else {
        $result = db_query("SELECT name, filename FROM {system} WHERE type = 'module' AND status = 1 ORDER BY weight ASC, filename ASC");
      }
      while ($module = db_fetch_object($result)) {
        if (file_exists($module->filename)) {
          drupal_get_filename('module', $module->name, $module->filename);
          $list[$module->name] = $module->name;
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
  $files = drupal_system_listing('/\.module$/', 'modules', 'name', 0);

  // Extract current files from database.
  system_get_files_database($files, 'module');

  ksort($files);

  // Set defaults for module info
  $defaults = array(
    'dependencies' => array(),
    'dependents' => array(),
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => DRUPAL_MINIMUM_PHP,
    'files' => array(),
  );

  foreach ($files as $filename => $file) {
    // Look for the info file.
    $file->info = drupal_parse_info_file(dirname($file->filename) . '/' . $file->name . '.info');

    // Skip modules that don't provide info.
    if (empty($file->info)) {
      unset($files[$filename]);
      continue;
    }
    // Merge in defaults and save.
    $files[$filename]->info = $file->info + $defaults;

    // Invoke hook_system_info_alter() to give installed modules a chance to
    // modify the data in the .info files if necessary.
    drupal_alter('system_info', $files[$filename]->info, $files[$filename]);

    // Log the critical hooks implemented by this module.
    $bootstrap = 0;
    foreach (bootstrap_hooks() as $hook) {
      if (module_hook($file->name, $hook)) {
        $bootstrap = 1;
        break;
      }
    }

    // Update the contents of the system table:
    if (isset($file->status) || (isset($file->old_filename) && $file->old_filename != $file->filename)) {
      db_query("UPDATE {system} SET info = '%s', name = '%s', filename = '%s', bootstrap = %d WHERE filename = '%s'", serialize($files[$filename]->info), $file->name, $file->filename, $bootstrap, $file->old_filename);
    }
    else {
      // This is a new module.
      $files[$filename]->status = 0;
      db_query("INSERT INTO {system} (name, info, type, filename, status, bootstrap) VALUES ('%s', '%s', '%s', '%s', %d, %d)", $file->name, serialize($files[$filename]->info), 'module', $file->filename, 0, $bootstrap);
    }
  }
  $files = _module_build_dependencies($files);
  return $files;
}

/**
 * Find dependencies any level deep and fill in dependents information too.
 *
 * If module A depends on B which in turn depends on C then this function will
 * add C to the list of modules A depends on. This will be repeated until
 * module A has a list of all modules it depends on. If it depends on itself,
 * called a circular dependency, that's marked by adding a nonexistent module,
 * called -circular- to this list of modules. Because this does not exist,
 * it'll be impossible to switch module A on.
 *
 * Also we fill in a dependents array in $file->info. Using the names above,
 * the dependents array of module B lists A.
 *
 * @param $files
 *   The array of filesystem objects used to rebuild the cache.
 * @return
 *   The same array with dependencies and dependents added where applicable.
 */
function _module_build_dependencies($files) {
  do {
    $new_dependency = FALSE;
    foreach ($files as $filename => $file) {
      // We will modify this object (module A, see doxygen for module A, B, C).
      $file = &$files[$filename];
      if (isset($file->info['dependencies']) && is_array($file->info['dependencies'])) {
        foreach ($file->info['dependencies'] as $dependency_name) {
          // This is a nonexistent module.
          if ($dependency_name == '-circular-' || !isset($files[$dependency_name])) {
            continue;
          }
          // $dependency_name is module B (again, see doxygen).
          $files[$dependency_name]->info['dependents'][$filename] = $filename;
          $dependency = $files[$dependency_name];
          if (isset($dependency->info['dependencies']) && is_array($dependency->info['dependencies'])) {
            // Let's find possible C modules.
            foreach ($dependency->info['dependencies'] as $candidate) {
              if (array_search($candidate, $file->info['dependencies']) === FALSE) {
                // Is this a circular dependency?
                if ($candidate == $filename) {
                  // As a module name can not contain dashes, this makes
                  // impossible to switch on the module.
                  $candidate = '-circular-';
                  // Do not display the message or add -circular- more than once.
                  if (array_search($candidate, $file->info['dependencies']) !== FALSE) {
                    continue;
                  }
                  drupal_set_message(t('%module is part of a circular dependency. This is not supported and you will not be able to switch it on.', array('%module' => $file->info['name'])), 'error');
                }
                else {
                  // We added a new dependency to module A. The next loop will
                  // be able to use this as "B module" thus finding even
                  // deeper dependencies.
                  $new_dependency = TRUE;
                }
                $file->info['dependencies'][] = $candidate;
              }
            }
          }
        }
      }
      // Don't forget to break the reference.
      unset($file);
    }
  } while ($new_dependency);
  return $files;
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
  return isset($list[$module]);
}

/**
 * Load a module's installation hooks.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once DRUPAL_ROOT . '/includes/install.inc';

  module_load_include('install', $module);
}

/**
 * Load a module include file.
 *
 * @param $type
 *   The include file's type (file extension).
 * @param $module
 *   The module to which the include file belongs.
 * @param $name
 *   Optionally, specify the file name. If not set, the module's name is used.
 */
function module_load_include($type, $module, $name = NULL) {
  if (empty($name)) {
    $name = $module;
  }

  if (drupal_function_exists('drupal_get_path')) {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . "/$name.$type";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }
  return FALSE;
}

/**
 * Load an include file for each of the modules that have been enabled in
 * the system table.
 */
function module_load_all_includes($type, $name = NULL) {
  $modules = module_list();
  foreach ($modules as $module) {
    module_load_include($type, $module, $name);
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
    $existing = db_fetch_object(db_query("SELECT status FROM {system} WHERE type = '%s' AND name = '%s'", 'module', $module));
    if ($existing->status == 0) {
      module_load_install($module);
      db_query("UPDATE {system} SET status = %d WHERE type = '%s' AND name = '%s'", 1, 'module', $module);
      drupal_load('module', $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to include the new enabled module.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    registry_rebuild();
  }

  foreach ($invoke_modules as $module) {
    module_invoke($module, 'enable');
    // Check if node_access table needs rebuilding.
    // We check for the existence of node_access_needs_rebuild() since
    // at install time, module_enable() could be called while node.module
    // is not enabled yet.
    if (drupal_function_exists('node_access_needs_rebuild') && !node_access_needs_rebuild() && module_hook($module, 'node_grants')) {
      node_access_needs_rebuild(TRUE);
    }
  }

  if (!empty($invoke_modules)) {
    // Invoke the hook_module_enable after all the modules have been
    // enabled.
    module_invoke_all('modules_enabled', $invoke_modules);
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
      // Check if node_access table needs rebuilding.
      if (!node_access_needs_rebuild() && module_hook($module, 'node_grants')) {
        node_access_needs_rebuild(TRUE);
      }

      module_load_install($module);
      module_invoke($module, 'disable');
      db_query("UPDATE {system} SET status = %d WHERE type = '%s' AND name = '%s'", 0, 'module', $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Invoke hook_module_disable before disabling modules,
    // so we can still call module hooks to get information.
    module_invoke_all('modules_disabled', $invoke_modules);
    // Refresh the module list to exclude the disabled modules.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    registry_rebuild();
  }

  // If there remains no more node_access module, rebuilding will be
  // straightforward, we can do it right now.
  if (node_access_needs_rebuild() && count(module_implements('node_grants')) == 0) {
    node_access_rebuild();
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
  $function = $module . '_' . $hook;
  if (defined('MAINTENANCE_MODE')) {
    return function_exists($function);
  }
  else {
    return drupal_function_exists($function);
  }
}

/**
 * Determine which modules are implementing a hook.
 *
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu"). Special cases:
 *     MODULE_IMPLEMENTS_CLEAR_CACHE: Force the stored list of hook
 *   implementations to be regenerated (such as after enabling a new module,
 *     before processing hook_enable).
 *     MODULE_IMPLEMENTS_WRITE_CACHE: Write the stored list of hook
 *     implementations into the cache_registry table.
 * @param $sort
 *   By default, modules are ordered by weight and filename.  By setting this
 *   option to TRUE, modules will be ordered by module name.
 * @return
 *   An array with the names of the modules which are implementing this hook.
 *   All enabled modules are taken into consideration and the files containing
 *   the implementations are loaded as necessary.
 */
function module_implements($hook, $sort = FALSE) {
  static $implementations = array(), $sorted_implementations = array(), $loaded = array(), $cached_hooks = 0;

  if (defined('MAINTENANCE_MODE')) {
    return _module_implements_maintenance($hook, $sort);
  }
  if ($hook === MODULE_IMPLEMENTS_CLEAR_CACHE) {
    $implementations = array();
    $sorted_implementations = array();
    $loaded = array();
    $cached_hooks = 0;
    cache_clear_all('hooks', 'cache_registry');
    return;
  }
  if ($hook === MODULE_IMPLEMENTS_WRITE_CACHE) {
    // Only write this to cache if we loaded new implementations.
    if (count($implementations) > $cached_hooks) {
      cache_set('hooks', $implementations, 'cache_registry');
    }
    return;
  }

  if (!isset($loaded[$hook])) {
    if (empty($implementations) && ($cache = cache_get('hooks', 'cache_registry'))) {
      $implementations = $cache->data;
      $cached_hooks = count($implementations);
    }
    if (!isset($implementations[$hook])) {
      $implementations[$hook] = db_query("SELECT module FROM {registry} WHERE type = 'function' AND suffix = :hook ORDER BY weight, module", array(':hook' => $hook))->fetchCol();
    }
    foreach ($implementations[$hook] as $module) {
      $function = $module . '_' . $hook;
      if (!function_exists($function)) {
        drupal_function_exists($function);
      }
    }
    $loaded[$hook] = TRUE;
  }

  if ($sort) {
    if (!isset($sorted_implementations[$hook])) {
      $sorted_implementations[$hook] = $implementations[$hook];
      sort($sorted_implementations[$hook]);
    }
    return $sorted_implementations[$hook];
  }
  else {
    return $implementations[$hook];
  }
}

/**
 * This is the maintenance version of module_implements for internal use only.
 *
 * This function is called whenever MAINTENANCE_MODE is defined and is a
 * safe code path for Drupal installation or upgrade because it does not use
 * the database, instead it uses module_list. @see module_list $fixed_list on
 * how to make module_list also DB independent.
 *
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu").
 * @param $sort
 *   By default, modules are ordered by weight and filename, settings this
 *   option to TRUE, module list will be ordered by module name.
 * @return
 *   An array with the names of the modules which are implementing this hook.
 *   Only enabled and already loaded modules are taken into consideration.
 */
function _module_implements_maintenance($hook, $sort = FALSE) {
  $implementations = array();
  foreach (module_list() as $module) {
    $function = $module . '_' . $hook;
    if (function_exists($function)) {
      $implementations[] = $module;
    }
    if ($sort) {
      sort($implementations);
    }
  }
  return $implementations;
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
  $module = $args[0];
  $hook = $args[1];
  unset($args[0], $args[1]);
  if (module_hook($module, $hook)) {
    return call_user_func_array($module . '_' . $hook, $args);
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
  $hook = $args[0];
  unset($args[0]);
  $return = array();
  foreach (module_implements($hook) as $module) {
    $function = $module . '_' . $hook;
    if (drupal_function_exists($function)) {
      $result = call_user_func_array($function, $args);
      if (isset($result) && is_array($result)) {
        $return = array_merge_recursive($return, $result);
      }
      elseif (isset($result)) {
        $return[] = $result;
      }
    }
  }

  return $return;
}

/**
 * @} End of "defgroup hooks".
 */

/**
 * Array of modules required by core.
 */
function drupal_required_modules() {
  $files = drupal_system_listing('/\.info$/', 'modules', 'name', 0);
  $required = array();
  foreach ($files as $name => $file) {
    $info = drupal_parse_info_file($file->filename);
    if (!empty($info) && !empty($info['required']) && $info['required']) {
      $required[] = $name;
    }
  }
  return $required;
}
