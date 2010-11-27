<?php
// $Id$

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
 *
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
 * Returns a list of currently active modules.
 *
 * Usually, this returns a list of all enabled modules. When called early on in
 * the bootstrap, it will return a list of vital modules only (those needed to
 * generate cached pages).
 *
 * All parameters to this function are optional and should generally not be
 * changed from their defaults.
 *
 * @param $refresh
 *   (optional) Whether to force the module list to be regenerated (such as
 *   after the administrator has changed the system settings). Defaults to
 *   FALSE.
 * @param $bootstrap_refresh
 *   (optional) When $refresh is TRUE, setting $bootstrap_refresh to TRUE forces
 *   the module list to be regenerated using the reduced set of modules loaded
 *   in "bootstrap mode" for cached pages. Otherwise, setting $refresh to TRUE
 *   generates the complete list of enabled modules.
 * @param $sort
 *   (optional) By default, modules are ordered by weight and module name. Set
 *   this option to TRUE to return a module list ordered only by module name.
 * @param $fixed_list
 *   (optional) If an array of module names is provided, this will override the
 *   module list with the given set of modules. This will persist until the next
 *   call with $refresh set to TRUE or with a new $fixed_list passed in. This
 *   parameter is primarily intended for internal use (e.g., in install.php and
 *   update.php).
 *
 * @return
 *   An associative array whose keys and values are the names of the modules in
 *   the list.
 */
function module_list($refresh = FALSE, $bootstrap_refresh = FALSE, $sort = FALSE, $fixed_list = NULL) {
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
      if ($refresh) {
        // For the $refresh case, make sure that system_list() returns fresh
        // data.
        drupal_static_reset('system_list');
      }
      if ($bootstrap_refresh) {
        $list = system_list('bootstrap');
      }
      else {
        // Not using drupal_map_assoc() here as that requires common.inc.
        $list = array_keys(system_list('module_enabled'));
        $list = (!empty($list) ? array_combine($list, $list) : array());
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
 *   An associative array of modules or themes, keyed by name. For $type
 *   'bootstrap', the array values equal the keys. For $type 'module_enabled'
 *   or 'theme', the array values are objects representing the respective
 *   database row, with the 'info' property already unserialized.
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
      $result = db_query("SELECT * FROM {system} WHERE type = 'theme' OR (type = 'module' AND status = 1) ORDER BY weight ASC, name ASC");
      foreach ($result as $record) {
        $record->info = unserialize($record->info);
        // Build a list of all enabled modules.
        if ($record->type == 'module') {
          $lists['module_enabled'][$record->name] = $record;
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
  drupal_static_reset('system_rebuild_module_data');
  drupal_static_reset('list_themes');
  cache_clear_all('bootstrap_modules', 'cache_bootstrap');
  cache_clear_all('system_list', 'cache_bootstrap');
}

/**
 * Find dependencies any level deep and fill in required by information too.
 *
 * @param $files
 *   The array of filesystem objects used to rebuild the cache.
 *
 * @return
 *   The same array with the new keys for each module:
 *   - requires: An array with the keys being the modules that this module
 *     requires.
 *   - required_by: An array with the keys being the modules that will not work
 *     without this module.
 */
function _module_build_dependencies($files) {
  require_once DRUPAL_ROOT . '/includes/graph.inc';
  foreach ($files as $filename => $file) {
    $graph[$file->name]['edges'] = array();
    if (isset($file->info['dependencies']) && is_array($file->info['dependencies'])) {
      foreach ($file->info['dependencies'] as $dependency) {
        $dependency_data = drupal_parse_dependency($dependency);
        $graph[$file->name]['edges'][$dependency_data['name']] = $dependency_data;
      }
    }
  }
  drupal_depth_first_search($graph);
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
 *
 * @return
 *   TRUE if the module is both installed and enabled.
 */
function module_exists($module) {
  $list = module_list();
  return isset($list[$module]);
}

/**
 * Load a module's installation hooks.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 *
 * @return
 *   The name of the module's install file, if successful; FALSE otherwise.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once DRUPAL_ROOT . '/includes/install.inc';

  return module_load_include('install', $module);
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
 *   (optional) The base file name (without the $type extension). If omitted,
 *   $module is used; i.e., resulting in "$module.$type" by default.
 *
 * @return
 *   The name of the included file, if successful; FALSE otherwise.
 */
function module_load_include($type, $module, $name = NULL) {
  if (!isset($name)) {
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
 * Enables or installs a given list of modules.
 *
 * Definitions:
 * - "Enabling" is the process of activating a module for use by Drupal.
 * - "Disabling" is the process of deactivating a module.
 * - "Installing" is the process of enabling it for the first time or after it
 *   has been uninstalled.
 * - "Uninstalling" is the process of removing all traces of a module.
 *
 * Order of events:
 * - Gather and add module dependencies to $module_list (if applicable).
 * - For each module that is being enabled:
 *   - Install module schema and update system registries and caches.
 *   - If the module is being enabled for the first time or had been
 *     uninstalled, invoke hook_install() and add it to the list of installed
 *     modules.
 *   - Invoke hook_enable().
 * - Invoke hook_modules_installed().
 * - Invoke hook_modules_enabled().
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
 *
 * @see hook_install()
 * @see hook_enable()
 * @see hook_modules_installed()
 * @see hook_modules_enabled()
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
      // Clear entity cache.
      entity_info_cache_clear();

      // Now install the module if necessary.
      if (drupal_get_installed_schema_version($module, TRUE) == SCHEMA_UNINSTALLED) {
        drupal_install_schema($module);

        // Set the schema version to the number of the last update provided
        // by the module.
        $versions = drupal_get_schema_versions($module);
        $version = $versions ? max($versions) : SCHEMA_INSTALLED;

        // If the module has no current updates, but has some that were
        // previously removed, set the version to the value of
        // hook_update_last_removed().
        if ($last_removed = module_invoke($module, 'update_last_removed')) {
          $version = max($version, $last_removed);
        }
        drupal_set_installed_schema_version($module, $version);
        // Allow the module to perform install tasks.
        module_invoke($module, 'install');
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

    $profile = drupal_get_profile();
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
        if (!isset($module_list[$dependent]) && $dependent != $profile) {
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
 *
 * The example functions included are not part of the Drupal core, they are
 * just models that you can modify. Only the hooks implemented within modules
 * are executed when running Drupal.
 *
 * See also @link themeable the themeable group page. @endlink
 */

/**
 * Determine whether a module implements a hook.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 * @param $hook
 *   The name of the hook (e.g. "help" or "menu").
 *
 * @return
 *   TRUE if the module is both installed and enabled, and the hook is
 *   implemented in that module.
 */
function module_hook($module, $hook) {
  $function = $module . '_' . $hook;
  if (function_exists($function)) {
    return TRUE;
  }
  // If the hook implementation does not exist, check whether it may live in an
  // optional include file registered via hook_hook_info().
  $hook_info = module_hook_info();
  if (isset($hook_info[$hook]['group'])) {
    module_load_include('inc', $module, $module . '.' . $hook_info[$hook]['group']);
    if (function_exists($function)) {
      return TRUE;
    }
  }
  return FALSE;
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
 * @see module_implements_write_cache()
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
    // The hook is not cached, so ensure that whether or not it has
    // implementations, that the cache is updated at the end of the request.
    $implementations['#write_cache'] = TRUE;
    $hook_info = module_hook_info();
    $implementations[$hook] = array();
    $list = module_list(FALSE, FALSE, $sort);
    foreach ($list as $module) {
      $include_file = isset($hook_info[$hook]['group']) && module_load_include('inc', $module, $module . '.' . $hook_info[$hook]['group']);
      // Since module_hook() may needlessly try to load the include file again,
      // function_exists() is used directly here.
      if (function_exists($module . '_' . $hook)) {
        $implementations[$hook][$module] = $include_file ? $hook_info[$hook]['group'] : FALSE;
      }
    }
    // Allow modules to change the weight of specific implementations but avoid
    // an infinite loop.
    if ($hook != 'module_implements_alter') {
      drupal_alter('module_implements', $implementations[$hook], $hook);
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
      // implementations cache being rebuilt yet, so we check whether the
      // function exists on each request to avoid undefined function errors.
      // Since module_hook() may needlessly try to load the include file again,
      // function_exists() is used directly here.
      if (!function_exists($module . '_' . $hook)) {
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
  // This function is indirectly invoked from bootstrap_invoke_all(), in which
  // case common.inc, subsystems, and modules are not loaded yet, so it does not
  // make sense to support hook groups resp. lazy-loaded include files prior to
  // full bootstrap.
  if (drupal_bootstrap(NULL, FALSE) != DRUPAL_BOOTSTRAP_FULL) {
    return array();
  }
  $hook_info = &drupal_static(__FUNCTION__);

  if (!isset($hook_info)) {
    $hook_info = array();
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
 *
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
 *
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
  $files = drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.info$/', 'modules', 'name', 0);
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

/**
 * Hands off alterable variables to type-specific *_alter implementations.
 *
 * This dispatch function hands off the passed in variables to type-specific
 * hook_TYPE_alter() implementations in modules. It ensures a consistent
 * interface for all altering operations.
 *
 * A maximum of 2 alterable arguments is supported. In case more arguments need
 * to be passed and alterable, modules provide additional variables assigned by
 * reference in the last $context argument:
 * @code
 *   $context = array(
 *     'alterable' => &$alterable,
 *     'unalterable' => $unalterable,
 *     'foo' => 'bar',
 *   );
 *   drupal_alter('mymodule_data', $alterable1, $alterable2, $context);
 * @endcode
 *
 * Note that objects are always passed by reference in PHP5. If it is absolutely
 * required that no implementation alters a passed object in $context, then an
 * object needs to be cloned:
 * @code
 *   $context = array(
 *     'unalterable_object' => clone $object,
 *   );
 *   drupal_alter('mymodule_data', $data, $context);
 * @endcode
 *
 * @param $type
 *   A string describing the type of the alterable $data. 'form', 'links',
 *   'node_content', and so on are several examples. Alternatively can be an
 *   array, in which case hook_TYPE_alter() is invoked for each value in the
 *   array, ordered first by module, and then for each module, in the order of
 *   values in $type. For example, when Form API is using drupal_alter() to
 *   execute both hook_form_alter() and hook_form_FORM_ID_alter()
 *   implementations, it passes array('form', 'form_' . $form_id) for $type.
 * @param &$data
 *   The variable that will be passed to hook_TYPE_alter() implementations to be
 *   altered. The type of this variable depends on the value of the $type
 *   argument. For example, when altering a 'form', $data will be a structured
 *   array. When altering a 'profile', $data will be an object.
 * @param &$context1
 *   (optional) An additional variable that is passed by reference.
 * @param &$context2
 *   (optional) An additional variable that is passed by reference. If more
 *   context needs to be provided to implementations, then this should be an
 *   associative array as described above.
 */
function drupal_alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['functions'] = &drupal_static(__FUNCTION__);
  }
  $functions = &$drupal_static_fast['functions'];

  // Most of the time, $type is passed as a string, so for performance,
  // normalize it to that. When passed as an array, usually the first item in
  // the array is a generic type, and additional items in the array are more
  // specific variants of it, as in the case of array('form', 'form_FORM_ID').
  if (is_array($type)) {
    $cid = implode(',', $type);
    $extra_types = $type;
    $type = array_shift($extra_types);
    // Allow if statements in this function to use the faster isset() rather
    // than !empty() both when $type is passed as a string, or as an array with
    // one item.
    if (empty($extra_types)) {
      unset($extra_types);
    }
  }
  else {
    $cid = $type;
  }

  // Some alter hooks are invoked many times per page request, so statically
  // cache the list of functions to call, and on subsequent calls, iterate
  // through them quickly.
  if (!isset($functions[$cid])) {
    $functions[$cid] = array();
    $hook = $type . '_alter';
    $modules = module_implements($hook);
    if (!isset($extra_types)) {
      // For the more common case of a single hook, we do not need to call
      // function_exists(), since module_implements() returns only modules with
      // implementations.
      foreach ($modules as $module) {
        $functions[$cid][] = $module . '_' . $hook;
      }
    }
    else {
      // For multiple hooks, we need $modules to contain every module that
      // implements at least one of them.
      $extra_modules = array();
      foreach ($extra_types as $extra_type) {
        $extra_modules = array_merge($extra_modules, module_implements($extra_type . '_alter'));
      }
      // If any modules implement one of the extra hooks that do not implement
      // the primary hook, we need to add them to the $modules array in their
      // appropriate order.
      if (array_diff($extra_modules, $modules)) {
        // Order the modules by the order returned by module_list().
        $modules = array_intersect(module_list(), array_merge($modules, $extra_modules));
      }
      foreach ($modules as $module) {
        // Since $modules is a merged array, for any given module, we do not
        // know whether it has any particular implementation, so we need a
        // function_exists().
        $function = $module . '_' . $hook;
        if (function_exists($function)) {
          $functions[$cid][] = $function;
        }
        foreach ($extra_types as $extra_type) {
          $function = $module . '_' . $extra_type . '_alter';
          if (function_exists($function)) {
            $functions[$cid][] = $function;
          }
        }
      }
    }
    // Allow the theme to alter variables after the theme system has been
    // initialized.
    global $theme, $base_theme_info;
    if (isset($theme)) {
      $theme_keys = array();
      foreach ($base_theme_info as $base) {
        $theme_keys[] = $base->name;
      }
      $theme_keys[] = $theme;
      foreach ($theme_keys as $theme_key) {
        $function = $theme_key . '_' . $hook;
        if (function_exists($function)) {
          $functions[$cid][] = $function;
        }
        if (isset($extra_types)) {
          foreach ($extra_types as $extra_type) {
            $function = $theme_key . '_' . $extra_type . '_alter';
            if (function_exists($function)) {
              $functions[$cid][] = $function;
            }
          }
        }
      }
    }
  }

  foreach ($functions[$cid] as $function) {
    $function($data, $context1, $context2);
  }
}

