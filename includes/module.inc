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
  foreach (module_list(TRUE) as $module) {
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
 * @param $sort
 *   By default, modules are ordered by weight and module name. Set this option
 *   to TRUE to return a module list ordered only by module name.
 * @param $fixed_list
 *   (Optional) Override the module list with the given modules. Stays until the
 *   next call with $refresh = TRUE.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $sort = FALSE, $fixed_list = NULL) {
  static $list = array(), $sorted_list;

  if (empty($list) || $refresh || $fixed_list) {
    $list = array();
    $sorted_list = NULL;
    if ($fixed_list) {
      foreach ($fixed_list as $name => $module) {
        drupal_get_filename('module', $name, $module['filename']);
        $list[$name] = $name;
      }
    }
    else {
      // The module name (rather than the filename) is used as the fallback
      // weighting in order to guarantee consistent behavior across different
      // Drupal installations, which might have modules installed in different
      // locations in the file system. The ordering here must also be
      // consistent with the one used in module_implements().
      $result = db_query("SELECT name, filename FROM {system} WHERE type = 'module' AND status = 1 ORDER BY weight ASC, name ASC");
      foreach ($result as $module) {
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
 * Find dependencies any level deep and fill in required by information too.
 *
 * @param $files
 *   The array of filesystem objects used to rebuild the cache.
 * @return
 *   The same array with the new keys for each module:
 *   - requires: An array with the keys being the modules that this module
 *     requires.
 *   - required_by: An array with the keys being the modules that will not work
 *     without this module.
 */
function _module_build_dependencies($files) {
  require_once DRUPAL_ROOT . '/includes/graph.inc';
  $roots = $files;
  foreach ($files as $filename => $file) {
    $graph[$file->name]['edges'] = array();
    if (isset($file->info['dependencies']) && is_array($file->info['dependencies'])) {
      foreach ($file->info['dependencies'] as $dependency_name) {
        $graph[$file->name]['edges'][$dependency_name] = 1;
        unset($roots[$dependency_name]);
      }
    }
  }
  drupal_depth_first_search($graph, array_keys($roots));
  foreach ($graph as $module => $data) {
    $files[$module]->required_by = isset($data['reverse_paths']) ? $data['reverse_paths'] : array();
    $files[$module]->requires = isset($data['paths']) ? $data['paths'] : array();
    $files[$module]->sort = $data['weight'];
  }
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
 * Examples:
 * @code
 *   // Load node.admin.inc from the node module.
 *   module_load_include('inc', 'node', 'node.admin');
 *   // Load content_types.inc from the node module.
 *   module_load_include('inc', 'node', 'content_types'); 
 * @endcode
 *
 * Do not use this function to load an install file. Use module_load_install()
 * instead.
 *
 * @param $type
 *   The include file's type (file extension).
 * @param $module
 *   The module to which the include file belongs.
 * @param $name
 *   Optionally, specify the base file name (without the $type extension). 
 *   If not set, $module is used.
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
 * @param $disable_modules_installed_hook
 *   Normally just testing wants to set this to TRUE.
 */
function module_enable($module_list, $disable_modules_installed_hook = FALSE) {
  $invoke_modules = array();
  
  // Try to install the enabled modules and collect which were installed.
  // $module_list is not changed and already installed modules are ignored.
  $modules_installed = array_filter($module_list, '_drupal_install_module');
  foreach ($module_list as $module) {
    $existing = db_query("SELECT status FROM {system} WHERE type = :type AND name = :name", array(
      ':type' => 'module',
      ':name' => $module))
      ->fetchObject();
    if ($existing->status == 0) {
      module_load_install($module);
      db_update('system')
        ->fields(array('status' => 1))
        ->condition('type', 'module')
        ->condition('name', $module)
        ->execute();
      drupal_load('module', $module);
      $invoke_modules[] = $module;
      watchdog('system', '%module module enabled.', array('%module' => $module), WATCHDOG_INFO);
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to include the new enabled module.
    module_list(TRUE);
    // Force to regenerate the stored list of hook implementations.
    registry_rebuild();

    // If any modules were newly installed, execute the hook for them.
    if (!$disable_modules_installed_hook && !empty($modules_installed)) {
      module_invoke_all('modules_installed', $modules_installed);
    }
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
      db_update('system')
        ->fields(array('status' => 0))
        ->condition('type', 'module')
        ->condition('name', $module)
        ->execute();
      $invoke_modules[] = $module;
      watchdog('system', '%module module disabled.', array('%module' => $module), WATCHDOG_INFO);
    }
  }

  if (!empty($invoke_modules)) {
    // Invoke hook_module_disable before disabling modules,
    // so we can still call module hooks to get information.
    module_invoke_all('modules_disabled', $invoke_modules);
    // Refresh the module list to exclude the disabled modules.
    module_list(TRUE);
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
 *   By default, modules are ordered by weight and module name. By setting this
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
      // The module name (rather than the filename) is used as the fallback
      // weighting in order to guarantee consistent behavior across different
      // Drupal installations, which might have modules installed in different
      // locations in the file system. The ordering here must also be
      // consistent with the one used in module_list().
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
    $info = drupal_parse_info_file($file->filepath);
    if (!empty($info) && !empty($info['required']) && $info['required']) {
      $required[] = $name;
    }
  }
  return $required;
}
