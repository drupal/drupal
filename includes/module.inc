<?php
// $Id: module.inc,v 1.184 2010/03/03 07:38:08 dries Exp $

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

/**
 * Load all the modules that have been enabled in the system table.
 *
 * @param $bootstrap
 *   Whether to load only the reduced set of modules loaded in "bootstrap mode"
 *   for cached pages. See bootstrap.inc.
 * @return
 *   If $bootstrap is NULL, return a boolean indicating whether all modules
 *   have been loaded.
 */
function module_load_all($bootstrap = FALSE) {
  static $has_run = FALSE;

  if (isset($bootstrap)) {
    foreach (module_list(TRUE, $bootstrap) as $module) {
      drupal_load('module', $module);
    }
    // $has_run will be TRUE if $bootstrap is FALSE.
    $has_run = !$bootstrap;
  }
  return $has_run;
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
 *   By default, modules are ordered by weight and module name. Set this option
 *   to TRUE to return a module list ordered only by module name.
 * @param $fixed_list
 *   (Optional) Override the module list with the given modules. Stays until the
 *   next call with $refresh = TRUE.
 * @return
 *   An associative array whose keys and values are the names of all loaded
 *   modules.
 */
function module_list($refresh = FALSE, $bootstrap = FALSE, $sort = FALSE, $fixed_list = NULL) {
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
      // As this is the $refresh case, make sure that system_list() returns
      // fresh data.
      drupal_static_reset('system_list');
      if ($bootstrap) {
        $list = system_list('bootstrap');
      }
      else {
        $list = system_list('module_enabled');
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
 * Build a list of bootstrap modules and enabled modules and themes.
 *
 * @param $type
 *   The type of list to return:
 *   - module_enabled: All enabled modules.
 *   - bootstrap: All enabled modules required for bootstrap.
 *   - theme: All themes.
 *
 * @return
 *   An associative array of modules or themes, keyed by name, and having the
 *   respective database row as value. For $type 'module_enabled' and
 *   'bootstrap', the array values equal the keys.
 *
 * @see module_list()
 * @see list_themes()
 */
function system_list($type) {
  $lists = &drupal_static(__FUNCTION__);

  // For bootstrap modules, attempt to fetch the list from cache if possible.
  // if not fetch only the required information to fire bootstrap hooks
  // in case we are going to serve the page from cache.
  if ($type == 'bootstrap') {
    if ($cached = cache_get('bootstrap_modules', 'cache_bootstrap')) {
      $bootstrap_list = $cached->data;
    }
    else {
      $bootstrap_list = db_query("SELECT name, filename FROM {system} WHERE status = 1 AND bootstrap = 1 AND type = 'module' ORDER BY weight ASC, name ASC")->fetchAllAssoc('name');
      cache_set('bootstrap_modules', $bootstrap_list, 'cache_bootstrap');
    }
    // To avoid a separate database lookup for the filepath, prime the
    // drupal_get_filename() static cache for bootstrap modules only.
    // The rest is stored separately to keep the bootstrap module cache small.
    foreach ($bootstrap_list as $module) {
      drupal_get_filename('module', $module->name, $module->filename);
    }
    // We only return the module names here since module_list() doesn't need
    // the filename itself.
    $lists['bootstrap'] = array_keys($bootstrap_list);
  }
  // Otherwise build the list for enabled modules and themes.
  elseif (!isset($lists['module_enabled'])) {
    if ($cached = cache_get('system_list', 'cache_bootstrap')) {
      $lists = $cached->data;
    }
    else {
      $lists = array(
        'module_enabled' => array(),
        'theme' => array(),
        'filepaths' => array(),
      );
      // The module name (rather than the filename) is used as the fallback
      // weighting in order to guarantee consistent behavior across different
      // Drupal installations, which might have modules installed in different
      // locations in the file system. The ordering here must also be
      // consistent with the one used in module_implements().
      $result = db_query("SELECT * FROM {system} ORDER BY weight ASC, name ASC");
      foreach ($result as $record) {
        if ($record->type == 'module' && $record->status) {
          // Build a list of all enabled modules.
          $lists['module_enabled'][$record->name] = $record->name;
        }
        // Build a list of themes.
        if ($record->type == 'theme') {
          $lists['theme'][$record->name] = $record;
        }
        // Build a list of filenames so drupal_get_filename can use it.
        if ($record->status) {
          $lists['filepaths'][] = array('type' => $record->type, 'name' => $record->name, 'filepath' => $record->filename);
        }
      }
      cache_set('system_list', $lists, 'cache_bootstrap');
    }
    // To avoid a separate database lookup for the filepath, prime the
    // drupal_get_filename() static cache with all enabled modules and themes.
    foreach ($lists['filepaths'] as $item) {
      drupal_get_filename($item['type'], $item['name'], $item['filepath']);
    }
  }

  return $lists[$type];
}

/**
 * Reset all system_list() caches.
 */
function system_list_reset() {
  drupal_static_reset('system_list');
  cache_clear_all('bootstrap_modules', 'cache_bootstrap');
  cache_clear_all('system_list', 'cache_bootstrap');
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
      foreach ($file->info['dependencies'] as $dependency) {
        $dependency_data = drupal_parse_dependency($dependency);
        $graph[$file->name]['edges'][$dependency_data['name']] = $dependency_data;
        unset($roots[$dependency_data['name']]);
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
 * Do not use this function to load an install file, use module_load_install()
 * instead. Do not use this function in a global context since it requires
 * Drupal to be fully bootstrapped, use require_once DRUPAL_ROOT . '/path/file'
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

  if (function_exists('drupal_get_path')) {
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
 * @param $enable_dependencies
 *   If TRUE, dependencies will automatically be added and enabled in the
 *   correct order. This incurs a significant performance cost, so use FALSE
 *   if you know $module_list is already complete and in the correct order.
 *
 * @return
 *   FALSE if one or more dependencies are missing, TRUE otherwise.
 */
function module_enable($module_list, $enable_dependencies = TRUE) {
  if ($enable_dependencies) {
    // Get all module data so we can find dependencies and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module])) {
        // This module is not found in the filesystem, abort.
        return FALSE;
      }
      if ($module_data[$module]->status) {
        // Skip already enabled modules.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // Add dependencies to the list, with a placeholder weight.
      // The new modules will be processed as the while loop continues.
      foreach ($module_data[$module]->info['dependencies'] as $dependency) {
        if (!isset($module_list[$dependency])) {
          $module_list[$dependency] = 0;
        }
      }
    }

    if (!$module_list) {
      // Nothing to do. All modules already enabled.
      return TRUE;
    }

    // Sort the module list by pre-calculated weights.
    arsort($module_list);
    $module_list = array_keys($module_list);
  }

  // Required for module installation checks.
  include_once DRUPAL_ROOT . '/includes/install.inc';

  $modules_installed = array();
  $modules_enabled = array();
  foreach ($module_list as $module) {
    // Only process modules that are not already enabled.
    $existing = db_query("SELECT status FROM {system} WHERE type = :type AND name = :name", array(
      ':type' => 'module',
      ':name' => $module))
      ->fetchObject();
    if ($existing->status == 0) {
      // Load the module's code.
      drupal_load('module', $module);
      module_load_install($module);

      // Update the database and module list to reflect the new module. This
      // needs to be done first so that the module's hook implementations,
      // hook_schema() in particular, can be called while it is being
      // installed.
      db_update('system')
        ->fields(array('status' => 1))
        ->condition('type', 'module')
        ->condition('name', $module)
        ->execute();
      // Refresh the module list to include it.
      system_list_reset();
      module_list(TRUE);
      module_implements('', FALSE, TRUE);
      _system_update_bootstrap_status();
      // Update the registry to include it.
      registry_update();
      // Refresh the schema to include it.
      drupal_get_schema(NULL, TRUE);

      // Now install the module if necessary.
      if (drupal_get_installed_schema_version($module, TRUE) == SCHEMA_UNINSTALLED) {
        drupal_install_schema($module);
        // Allow the module to perform install tasks.
        module_invoke($module, 'install');
        $versions = drupal_get_schema_versions($module);
        drupal_set_installed_schema_version($module, $versions ? max($versions) : SCHEMA_INSTALLED);
        // Record the fact that it was installed.
        $modules_installed[] = $module;
        watchdog('system', '%module module installed.', array('%module' => $module), WATCHDOG_INFO);
      }

      // Enable the module.
      module_invoke($module, 'enable');

      // Record the fact that it was enabled.
      $modules_enabled[] = $module;
      watchdog('system', '%module module enabled.', array('%module' => $module), WATCHDOG_INFO);
    }
  }

  // If any modules were newly installed, invoke hook_modules_installed().
  if (!empty($modules_installed)) {
    module_invoke_all('modules_installed', $modules_installed);
  }

  // If any modules were newly enabled, invoke hook_modules_enabled().
  if (!empty($modules_enabled)) {
    module_invoke_all('modules_enabled', $modules_enabled);
  }

  return TRUE;
}

/**
 * Disable a given set of modules.
 *
 * @param $module_list
 *   An array of module names.
 * @param $disable_dependents
 *   If TRUE, dependent modules will automatically be added and disabled in the
 *   correct order. This incurs a significant performance cost, so use FALSE
 *   if you know $module_list is already complete and in the correct order.
 */
function module_disable($module_list, $disable_dependents = TRUE) {
  if ($disable_dependents) {
    // Get all module data so we can find dependents and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module]) || !$module_data[$module]->status) {
        // This module doesn't exist or is already disabled, skip it.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // Add dependent modules to the list, with a placeholder weight.
      // The new modules will be processed as the while loop continues.
      foreach ($module_data[$module]->required_by as $dependent => $dependent_data) {
        if (!isset($module_list[$dependent]) && !strstr($module_data[$dependent]->filename, '.profile')) {
          $module_list[$dependent] = 0;
        }
      }
    }

    // Sort the module list by pre-calculated weights.
    asort($module_list);
    $module_list = array_keys($module_list);
  }

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
    // Refresh the module list to exclude the disabled modules.
    system_list_reset();
    module_list(TRUE);
    module_implements('', FALSE, TRUE);
    // Invoke hook_modules_disabled before disabling modules,
    // so we can still call module hooks to get information.
    module_invoke_all('modules_disabled', $invoke_modules);
    // Update the registry to remove the newly-disabled module.
    registry_update();
    _system_update_bootstrap_status();
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
 * function that is named foo_bar(), where "foo" is the name of the module
 * (whose filename is thus foo.module) and "bar" is the name of the hook. Each
 * hook has a defined set of parameters and a specified result type.
 *
 * To extend Drupal, a module need simply implement a hook. When Drupal wishes
 * to allow intervention from modules, it determines which modules implement a
 * hook and calls that hook in all enabled modules that implement it.
 *
 * The available hooks to implement are explained here in the Hooks section of
 * the developer documentation. The string "hook" is used as a placeholder for
 * the module name in the hook definitions. For example, if the module file is
 * called example.module, then hook_help() as implemented by that module would
 * be defined as example_help().
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
  return function_exists($module . '_' . $hook);
}

/**
 * Determine which modules are implementing a hook.
 *
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu").
 * @param $sort
 *   By default, modules are ordered by weight and filename, settings this option
 *   to TRUE, module list will be ordered by module name.
 * @param $reset
 *   For internal use only: Whether to force the stored list of hook
 *   implementations to be regenerated (such as after enabling a new module,
 *   before processing hook_enable).
 *
 * @return
 *   An array with the names of the modules which are implementing this hook.
 *
 * @see module_implements_write_cache().
 */
function module_implements($hook, $sort = FALSE, $reset = FALSE) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['implementations'] = &drupal_static(__FUNCTION__);
  }
  $implementations = &$drupal_static_fast['implementations'];

  // We maintain a persistent cache of hook implementations in addition to the
  // static cache to avoid looping through every module and every hook on each
  // request. Benchmarks show that the benefit of this caching outweighs the
  // additional database hit even when using the default database caching
  // backend and only a small number of modules are enabled. The cost of the
  // cache_get() is more or less constant and reduced further when non-database
  // caching backends are used, so there will be more significant gains when a
  // large number of modules are installed or hooks invoked, since this can
  // quickly lead to module_hook() being called several thousand times
  // per request.
  if ($reset) {
    $implementations = array();
    cache_set('module_implements', array(), 'cache_bootstrap');
    drupal_static_reset('module_hook_info');
    drupal_static_reset('drupal_alter');
    cache_clear_all('hook_info', 'cache_bootstrap');
    return;
  }

  // Fetch implementations from cache.
  if (empty($implementations)) {
    $implementations = cache_get('module_implements', 'cache_bootstrap');
    if ($implementations === FALSE) {
      $implementations = array();
    }
    else {
      $implementations = $implementations->data;
    }
  }

  if (!isset($implementations[$hook])) {
    $hook_info = module_hook_info();
    $implementations[$hook] = array();
    $list = module_list(FALSE, FALSE, $sort);
    foreach ($list as $module) {
      $include_file = FALSE;
      if (module_hook($module, $hook) || (isset($hook_info[$hook]['group']) && $include_file = module_load_include('inc', $module, $module . '.' . $hook_info[$hook]['group']) && module_hook($module, $hook))) {
        $implementations[$hook][$module] = $include_file ? $hook_info[$hook]['group'] : FALSE;
        // We added something to the cache, so write it when we are done.
        $implementations['#write_cache'] = TRUE;
      }
    }
  }
  else {
    foreach ($implementations[$hook] as $module => $group) {
      // If this hook implementation is stored in a lazy-loaded file, so include
      // that file first.
      if ($group) {
        module_load_include('inc', $module, "$module.$group");
      }
      // It is possible that a module removed a hook implementation without the
      // implementations cache being rebuilt yet, so we check module_hook() on
      // each request to avoid undefined function errors.
      if (!module_hook($module, $hook)) {
        // Clear out the stale implementation from the cache and force a cache
        // refresh to forget about no longer existing hook implementations.
        unset($implementations[$hook][$module]);
        $implementations['#write_cache'] = TRUE;
      }
    }
  }

  return array_keys($implementations[$hook]);
}

/**
 * Retrieve a list of what hooks are explicitly declared.
 */
function module_hook_info() {
  $hook_info = &drupal_static(__FUNCTION__, array());

  if (empty($hook_info)) {
    $cache = cache_get('hook_info', 'cache_bootstrap');
    if ($cache === FALSE) {
      // Rebuild the cache and save it.
      // We can't use module_invoke_all() here or it would cause an infinite
      // loop.
      foreach (module_list() as $module) {
        $function = $module . '_hook_info';
        if (function_exists($function)) {
          $result = $function();
          if (isset($result) && is_array($result)) {
            $hook_info = array_merge_recursive($hook_info, $result);
          }
        }
      }
      // We can't use drupal_alter() for the same reason as above.
      foreach (module_list() as $module) {
        $function = $module . '_hook_info_alter';
        if (function_exists($function)) {
          $function($hook_info);
        }
      }
      cache_set('hook_info', $hook_info, 'cache_bootstrap');
    }
    else {
      $hook_info = $cache->data;
    }
  }

  return $hook_info;
}

/**
 * Writes the hook implementation cache.
 *
 * @see module_implements()
 */
function module_implements_write_cache() {
  $implementations = &drupal_static('module_implements');
  // Check whether we need to write the cache. We do not want to cache hooks
  // which are only invoked on HTTP POST requests since these do not need to be
  // optimized as tightly, and not doing so keeps the cache entry smaller.
  if (isset($implementations['#write_cache']) && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD')) {
    unset($implementations['#write_cache']);
    cache_set('module_implements', $implementations, 'cache_bootstrap');
  }
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
    if (function_exists($function)) {
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

  // An install profile is required and one must always be loaded.
  $required[] = drupal_get_profile();

  foreach ($files as $name => $file) {
    $info = drupal_parse_info_file($file->uri);
    if (!empty($info) && !empty($info['required']) && $info['required']) {
      $required[] = $name;
    }
  }

  return $required;
}
