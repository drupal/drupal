<?php
// $Id$

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
    $list = array();
    $sorted_list = NULL;
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

  // Set defaults for module info
  $defaults = array(
    'dependencies' => array(),
    'dependents' => array(),
    'description' => '',
    'version' => NULL,
    'php' => DRUPAL_MINIMUM_PHP,
  );

  foreach ($files as $filename => $file) {
    // Look for the info file.
    $file->info = drupal_parse_info_file(dirname($file->filename) .'/'. $file->name .'.info');

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
      $files[$filename]->throttle = 0;
      db_query("INSERT INTO {system} (name, info, type, filename, status, throttle, bootstrap) VALUES ('%s', '%s', '%s', '%s', %d, %d, %d)", $file->name, serialize($files[$filename]->info), 'module', $file->filename, 0, 0, $bootstrap);
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
  return array_key_exists($module, $list);
}

/**
 * Load a module's installation hooks.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once './includes/install.inc';

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

  $file = './'. drupal_get_path('module', $module) ."/$name.$type";

  if (is_file($file)) {
    require_once $file;
  }
  else {
    return FALSE;
  }
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
      db_query("UPDATE {system} SET status = %d, throttle = %d WHERE type = '%s' AND name = '%s'", 1, 0, 'module', $module);
      drupal_load('module', $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to include the new enabled module.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    module_implements('', FALSE, TRUE);
  }

  foreach ($invoke_modules as $module) {
    module_invoke($module, 'enable');
    // Check if node_access table needs rebuilding.
    // We check for the existence of node_access_needs_rebuild() since
    // at install time, module_enable() could be called while node.module
    // is not enabled yet.
    if (function_exists('node_access_needs_rebuild') && !node_access_needs_rebuild() && module_hook($module, 'node_grants')) {
      node_access_needs_rebuild(TRUE);
    }
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
      db_query("UPDATE {system} SET status = %d, throttle = %d WHERE type = '%s' AND name = '%s'", 0, 0, 'module', $module);
      $invoke_modules[] = $module;
    }
  }

  if (!empty($invoke_modules)) {
    // Refresh the module list to exclude the disabled modules.
    module_list(TRUE, FALSE);
    // Force to regenerate the stored list of hook implementations.
    module_implements('', FALSE, TRUE);
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
  $module = $args[0];
  $hook = $args[1];
  unset($args[0], $args[1]);
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
  $hook = $args[0];
  unset($args[0]);
  $return = array();
  foreach (module_implements($hook) as $module) {
    $function = $module .'_'. $hook;
    $result = call_user_func_array($function, $args);
    if (isset($result) && is_array($result)) {
      $return = array_merge_recursive($return, $result);
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

/**
 * Array of modules required by core.
 */
function drupal_required_modules() {
  return array('block', 'filter', 'node', 'system', 'user');
}
