<?php

/**
 * @file
 * Drupal database update API.
 *
 * This file contains functions to perform database updates for a Drupal
 * installation. It is included and used extensively by update.php.
 */

use Drupal\Component\Graph\Graph;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Utility\Error;

/**
 * Tests the compatibility of a module or theme.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/3150727
 * @see \Drupal\Core\Extension\ExtensionList::checkIncompatibility()
 */
function update_check_incompatibility($name, $type = 'module') {
  @trigger_error('update_check_incompatibility() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3150727', E_USER_DEPRECATED);
  static $themes, $modules;

  // Store values of expensive functions for future use.
  if (empty($themes) || empty($modules)) {
    // We need to do a full rebuild here to make sure the database reflects any
    // code changes that were made in the filesystem before the update script
    // was initiated.
    $themes = \Drupal::service('theme_handler')->rebuildThemeData();
    $modules = \Drupal::service('extension.list.module')->reset()->getList();
  }

  if ($type == 'module' && isset($modules[$name])) {
    $file = $modules[$name];
  }
  elseif ($type == 'theme' && isset($themes[$name])) {
    $file = $themes[$name];
  }
  if (!isset($file)
      || $file->info['core_incompatible']
      || version_compare(phpversion(), $file->info['php']) < 0) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns whether the minimum schema requirement has been satisfied.
 *
 * @return array
 *   A requirements info array.
 */
function update_system_schema_requirements() {
  $requirements = [];

  $system_schema = \Drupal::service('update.update_hook_registry')->getInstalledVersion('system');

  $requirements['minimum schema']['title'] = 'Minimum schema version';
  if ($system_schema >= \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
    $requirements['minimum schema'] += [
      'value' => 'The installed schema version meets the minimum.',
      'description' => 'Schema version: ' . $system_schema,
    ];
  }
  else {
    $requirements['minimum schema'] += [
      'value' => 'The installed schema version does not meet the minimum.',
      'severity' => REQUIREMENT_ERROR,
      'description' => 'Your system schema version is ' . $system_schema . '. Updating directly from a schema version prior to 8000 is not supported. You must upgrade your site to Drupal 8 first, see https://www.drupal.org/docs/8/upgrade.',
    ];
  }

  return $requirements;
}

/**
 * Checks update requirements and reports errors and (optionally) warnings.
 */
function update_check_requirements() {
  // Because this is one of the earliest points in the update process,
  // detect and fix missing schema versions for modules here to ensure
  // it runs on all update code paths.
  _update_fix_missing_schema();

  // Check requirements of all loaded modules.
  $requirements = \Drupal::moduleHandler()->invokeAll('requirements', ['update']);
  $requirements += update_system_schema_requirements();
  return $requirements;
}

/**
 * Helper to detect and fix 'missing' schema information.
 *
 * Repairs the case where a module has no schema version recorded.
 * This has to be done prior to updates being run, otherwise the update
 * system would detect and attempt to run all historical updates for a
 * module.
 *
 * @todo: remove in a major version after
 * https://www.drupal.org/project/drupal/issues/3130037 has been fixed.
 */
function _update_fix_missing_schema() {
  /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
  $update_registry = \Drupal::service('update.update_hook_registry');
  $versions = $update_registry->getAllInstalledVersions();
  $module_handler = \Drupal::moduleHandler();
  $enabled_modules = $module_handler->getModuleList();

  foreach (array_keys($enabled_modules) as $module) {
    // All modules should have a recorded schema version, but when they
    // don't, detect and fix the problem.
    if (!isset($versions[$module])) {
      // Ensure the .install file is loaded.
      $module_handler->loadInclude($module, 'install');
      $all_updates = $update_registry->getAvailableUpdates($module);
      // If the schema version of a module hasn't been recorded, we cannot
      // know the actual schema version a module is at, because
      // no updates will ever have been run on the site and it was not set
      // correctly when the module was installed, so instead set it to
      // the same as the last update. This means that updates will proceed
      // again the next time the module is updated and a new update is
      // added. Updates added in between the module being installed and the
      // schema version being fixed here (if any have been added) will never
      // be run, but we have no way to identify which updates these are.
      if ($all_updates) {
        $last_update = max($all_updates);
      }
      else {
        $last_update = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
      }
      // If the module implements hook_update_last_removed() use the
      // value of that if it's higher than the schema versions found so
      // far.
      if ($last_removed = $module_handler->invoke($module, 'update_last_removed')) {
        $last_update = max($last_update, $last_removed);
      }
      $update_registry->setInstalledVersion($module, $last_update);
      $args = ['%module' => $module, '%last_update_hook' => $module . '_update_' . $last_update . '()'];
      \Drupal::messenger()->addWarning(t('Schema information for module %module was missing from the database. You should manually review the module updates and your database to check if any updates have been skipped up to, and including, %last_update_hook.', $args));
      \Drupal::logger('update')->warning('Schema information for module %module was missing from the database. You should manually review the module updates and your database to check if any updates have been skipped up to, and including, %last_update_hook.', $args);
    }
  }
}

/**
 * Forces a module to a given schema version.
 *
 * This function is rarely necessary.
 *
 * @param string $module
 *   Name of the module.
 * @param string $schema_version
 *   The schema version the module should be set to.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/3210925
 */
function update_set_schema($module, $schema_version) {
  @trigger_error('update_set_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/3210925', E_USER_DEPRECATED);
  \Drupal::service('update.update_hook_registry')->setInstalledVersion($module, $schema_version);
  \Drupal::service('extension.list.profile')->reset();
  \Drupal::service('extension.list.module')->reset();
  \Drupal::service('extension.list.theme_engine')->reset();
  \Drupal::service('extension.list.theme')->reset();
}

/**
 * Implements callback_batch_operation().
 *
 * Performs one update and stores the results for display on the results page.
 *
 * If an update function completes successfully, it should return a message
 * as a string indicating success, for example:
 * @code
 * return t('New index added successfully.');
 * @endcode
 *
 * Alternatively, it may return nothing. In that case, no message
 * will be displayed at all.
 *
 * If it fails for whatever reason, it should throw an instance of
 * Drupal\Core\Utility\UpdateException with an appropriate error message, for
 * example:
 * @code
 * use Drupal\Core\Utility\UpdateException;
 * throw new UpdateException('Description of what went wrong');
 * @endcode
 *
 * If an exception is thrown, the current update and all updates that depend on
 * it will be aborted. The schema version will not be updated in this case, and
 * all the aborted updates will continue to appear on update.php as updates
 * that have not yet been run.
 *
 * If an update function needs to be re-run as part of a batch process, it
 * should accept the $sandbox array by reference as its first parameter
 * and set the #finished property to the percentage completed that it is, as a
 * fraction of 1.
 *
 * @param $module
 *   The module whose update will be run.
 * @param $number
 *   The update number to run.
 * @param $dependency_map
 *   An array whose keys are the names of all update functions that will be
 *   performed during this batch process, and whose values are arrays of other
 *   update functions that each one depends on.
 * @param $context
 *   The batch context array.
 *
 * @see update_resolve_dependencies()
 */
function update_do_one($module, $number, $dependency_map, &$context) {
  $function = $module . '_update_' . $number;

  // If this update was aborted in a previous step, or has a dependency that
  // was aborted in a previous step, go no further.
  if (!empty($context['results']['#abort']) && array_intersect($context['results']['#abort'], array_merge($dependency_map, [$function]))) {
    return;
  }

  $ret = [];
  if (function_exists($function)) {
    try {
      $ret['results']['query'] = $function($context['sandbox']);
      $ret['results']['success'] = TRUE;
    }
    // @TODO We may want to do different error handling for different
    // exception types, but for now we'll just log the exception and
    // return the message for printing.
    // @see https://www.drupal.org/node/2564311
    catch (Exception $e) {
      watchdog_exception('update', $e);

      $variables = Error::decodeException($e);
      unset($variables['backtrace'], $variables['exception'], $variables['severity_level']);
      $ret['#abort'] = ['success' => FALSE, 'query' => t(Error::DEFAULT_ERROR_MESSAGE, $variables)];
    }
  }

  if (isset($context['sandbox']['#finished'])) {
    $context['finished'] = $context['sandbox']['#finished'];
    unset($context['sandbox']['#finished']);
  }

  if (!isset($context['results'][$module])) {
    $context['results'][$module] = [];
  }
  if (!isset($context['results'][$module][$number])) {
    $context['results'][$module][$number] = [];
  }
  $context['results'][$module][$number] = array_merge($context['results'][$module][$number], $ret);

  if (!empty($ret['#abort'])) {
    // Record this function in the list of updates that were aborted.
    $context['results']['#abort'][] = $function;
  }

  // Record the schema update if it was completed successfully.
  if ($context['finished'] == 1 && empty($ret['#abort'])) {
    \Drupal::service('update.update_hook_registry')->setInstalledVersion($module, $number);
  }

  $context['message'] = t('Updating @module', ['@module' => $module]);
}

/**
 * Executes a single hook_post_update_NAME().
 *
 * @param string $function
 *   The function name, that should be executed.
 * @param array $context
 *   The batch context array.
 */
function update_invoke_post_update($function, &$context) {
  $ret = [];

  // If this update was aborted in a previous step, or has a dependency that was
  // aborted in a previous step, go no further.
  if (!empty($context['results']['#abort'])) {
    return;
  }

  // Ensure extension post update code is loaded.
  [$extension, $name] = explode('_post_update_', $function, 2);
  \Drupal::service('update.post_update_registry')->getUpdateFunctions($extension);

  if (function_exists($function)) {
    try {
      $ret['results']['query'] = $function($context['sandbox']);
      $ret['results']['success'] = TRUE;

      if (!isset($context['sandbox']['#finished']) || (isset($context['sandbox']['#finished']) && $context['sandbox']['#finished'] >= 1)) {
        \Drupal::service('update.post_update_registry')->registerInvokedUpdates([$function]);
      }
    }
    // @TODO We may want to do different error handling for different exception
    // types, but for now we'll just log the exception and return the message
    // for printing.
    // @see https://www.drupal.org/node/2564311
    catch (Exception $e) {
      watchdog_exception('update', $e);

      $variables = Error::decodeException($e);
      unset($variables['backtrace'], $variables['exception'], $variables['severity_level']);
      $ret['#abort'] = [
        'success' => FALSE,
        'query' => t(Error::DEFAULT_ERROR_MESSAGE, $variables),
      ];
    }
  }

  if (isset($context['sandbox']['#finished'])) {
    $context['finished'] = $context['sandbox']['#finished'];
    unset($context['sandbox']['#finished']);
  }
  if (!isset($context['results'][$extension][$name])) {
    $context['results'][$extension][$name] = [];
  }
  $context['results'][$extension][$name] = array_merge($context['results'][$extension][$name], $ret);

  if (!empty($ret['#abort'])) {
    // Record this function in the list of updates that were aborted.
    $context['results']['#abort'][] = $function;
  }

  $context['message'] = t('Post updating @extension', ['@extension' => $extension]);
}

/**
 * Returns a list of all the pending database updates.
 *
 * @return
 *   An associative array keyed by module name which contains all information
 *   about database updates that need to be run, and any updates that are not
 *   going to proceed due to missing requirements. The system module will
 *   always be listed first.
 *
 *   The subarray for each module can contain the following keys:
 *   - start: The starting update that is to be processed. If this does not
 *       exist then do not process any updates for this module as there are
 *       other requirements that need to be resolved.
 *   - warning: Any warnings about why this module can not be updated.
 *   - pending: An array of all the pending updates for the module including
 *       the update number and the description from source code comment for
 *       each update function. This array is keyed by the update number.
 */
function update_get_update_list() {
  // Make sure that the system module is first in the list of updates.
  $ret = ['system' => []];

  /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
  $update_registry = \Drupal::service('update.update_hook_registry');
  $modules = $update_registry->getAllInstalledVersions();
  /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
  $extension_list = \Drupal::service('extension.list.module');
  /** @var array $installed_module_info */
  $installed_module_info = $extension_list->getAllInstalledInfo();
  foreach ($modules as $module => $schema_version) {
    // Skip uninstalled and incompatible modules.
    try {
      if ($schema_version == $update_registry::SCHEMA_UNINSTALLED || $extension_list->checkIncompatibility($module)) {
        continue;
      }
    }
    // It is possible that the system schema has orphaned entries, so the
    // incompatibility checking might throw an exception.
    catch (UnknownExtensionException $e) {
      $args = [
        '%name' => $module,
        ':url' => 'https://www.drupal.org/node/3137656',
      ];
      \Drupal::messenger()->addWarning(t('Module %name has an entry in the system.schema key/value storage, but is missing from your site. <a href=":url">More information about this error</a>.', $args));
      \Drupal::logger('system')->notice('Module %name has an entry in the system.schema key/value storage, but is missing from your site. <a href=":url">More information about this error</a>.', $args);
      continue;
    }
    // There might be orphaned entries for modules that are in the filesystem
    // but not installed. Also skip those, but warn site admins about it.
    if (empty($installed_module_info[$module])) {
      $args = [
        '%name' => $module,
        ':url' => 'https://www.drupal.org/node/3137656',
      ];
      \Drupal::messenger()->addWarning(t('Module %name has an entry in the system.schema key/value storage, but is not installed. <a href=":url">More information about this error</a>.', $args));
      \Drupal::logger('system')->notice('Module %name has an entry in the system.schema key/value storage, but is not installed. <a href=":url">More information about this error</a>.', $args);
      continue;
    }

    // Display a requirements error if the user somehow has a schema version
    // from the previous Drupal major version.
    if ($schema_version < \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
      $ret[$module]['warning'] = '<em>' . $module . '</em> module cannot be updated. Its schema version is ' . $schema_version . ', which is from an earlier major release of Drupal. See the <a href="https://www.drupal.org/docs/upgrading-drupal">Upgrading Drupal guide</a>.';
      continue;
    }
    // Otherwise, get the list of updates defined by this module.
    $updates = $update_registry->getAvailableUpdates($module);
    if ($updates) {
      foreach ($updates as $update) {
        if ($update == \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
          $ret[$module]['warning'] = '<em>' . $module . '</em> module cannot be updated. It contains an update numbered as ' . \Drupal::CORE_MINIMUM_SCHEMA_VERSION . ' which is reserved for the earliest installation of a module in Drupal ' . \Drupal::CORE_COMPATIBILITY . ', before any updates. In order to update <em>' . $module . '</em> module, you will need to install a version of the module with valid updates.';
          continue 2;
        }
        if ($update > $schema_version) {
          // The description for an update comes from its Doxygen.
          $func = new ReflectionFunction($module . '_update_' . $update);
          $patterns = [
            '/^\s*[\/*]*/',
            '/(\n\s*\**)(.*)/',
            '/\/$/',
            '/^\s*/',
          ];
          $replacements = ['', '$2', '', ''];
          $description = preg_replace($patterns, $replacements, $func->getDocComment());
          $ret[$module]['pending'][$update] = "$update - $description";
          if (!isset($ret[$module]['start'])) {
            $ret[$module]['start'] = $update;
          }
        }
      }
      if (!isset($ret[$module]['start']) && isset($ret[$module]['pending'])) {
        $ret[$module]['start'] = $schema_version;
      }
    }
  }

  if (empty($ret['system'])) {
    unset($ret['system']);
  }
  return $ret;
}

/**
 * Resolves dependencies in a set of module updates, and orders them correctly.
 *
 * This function receives a list of requested module updates and determines an
 * appropriate order to run them in such that all update dependencies are met.
 * Any updates whose dependencies cannot be met are included in the returned
 * array but have the key 'allowed' set to FALSE; the calling function should
 * take responsibility for ensuring that these updates are ultimately not
 * performed.
 *
 * In addition, the returned array also includes detailed information about the
 * dependency chain for each update, as provided by the depth-first search
 * algorithm in Drupal\Component\Graph\Graph::searchAndSort().
 *
 * @param $starting_updates
 *   An array whose keys contain the names of modules with updates to be run
 *   and whose values contain the number of the first requested update for that
 *   module.
 *
 * @return
 *   An array whose keys are the names of all update functions within the
 *   provided modules that would need to be run in order to fulfill the
 *   request, arranged in the order in which the update functions should be
 *   run. (This includes the provided starting update for each module and all
 *   subsequent updates that are available.) The values are themselves arrays
 *   containing all the keys provided by the
 *   Drupal\Component\Graph\Graph::searchAndSort() algorithm, which encode
 *   detailed information about the dependency chain for this update function
 *   (for example: 'paths', 'reverse_paths', 'weight', and 'component'), as
 *   well as the following additional keys:
 *   - 'allowed': A boolean which is TRUE when the update function's
 *     dependencies are met, and FALSE otherwise. Calling functions should
 *     inspect this value before running the update.
 *   - 'missing_dependencies': An array containing the names of any other
 *     update functions that are required by this one but that are unavailable
 *     to be run. This array will be empty when 'allowed' is TRUE.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see \Drupal\Component\Graph\Graph::searchAndSort()
 */
function update_resolve_dependencies($starting_updates) {
  // Obtain a dependency graph for the requested update functions.
  $update_functions = update_get_update_function_list($starting_updates);
  $graph = update_build_dependency_graph($update_functions);

  // Perform the depth-first search and sort on the results.
  $graph_object = new Graph($graph);
  $graph = $graph_object->searchAndSort();
  uasort($graph, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

  foreach ($graph as $function => &$data) {
    $module = $data['module'];
    $number = $data['number'];
    // If the update function is missing and has not yet been performed, mark
    // it and everything that ultimately depends on it as disallowed.
    if (update_is_missing($module, $number, $update_functions) && !update_already_performed($module, $number)) {
      $data['allowed'] = FALSE;
      foreach (array_keys($data['paths']) as $dependent) {
        $graph[$dependent]['allowed'] = FALSE;
        $graph[$dependent]['missing_dependencies'][] = $function;
      }
    }
    elseif (!isset($data['allowed'])) {
      $data['allowed'] = TRUE;
      $data['missing_dependencies'] = [];
    }
    // Now that we have finished processing this function, remove it from the
    // graph if it was not part of the original list. This ensures that we
    // never try to run any updates that were not specifically requested.
    if (!isset($update_functions[$module][$number])) {
      unset($graph[$function]);
    }
  }

  return $graph;
}

/**
 * Returns an organized list of update functions for a set of modules.
 *
 * @param $starting_updates
 *   An array whose keys contain the names of modules and whose values contain
 *   the number of the first requested update for that module.
 *
 * @return
 *   An array containing all the update functions that should be run for each
 *   module, including the provided starting update and all subsequent updates
 *   that are available. The keys of the array contain the module names, and
 *   each value is an ordered array of update functions, keyed by the update
 *   number.
 *
 * @see update_resolve_dependencies()
 */
function update_get_update_function_list($starting_updates) {
  // Go through each module and find all updates that we need (including the
  // first update that was requested and any updates that run after it).
  $update_functions = [];
  /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
  $update_registry = \Drupal::service('update.update_hook_registry');
  foreach ($starting_updates as $module => $version) {
    $update_functions[$module] = [];
    $updates = $update_registry->getAvailableUpdates($module);
    if ($updates) {
      $max_version = max($updates);
      if ($version <= $max_version) {
        foreach ($updates as $update) {
          if ($update >= $version) {
            $update_functions[$module][$update] = $module . '_update_' . $update;
          }
        }
      }
    }
  }
  return $update_functions;
}

/**
 * Constructs a graph which encodes the dependencies between module updates.
 *
 * This function returns an associative array which contains a "directed graph"
 * representation of the dependencies between a provided list of update
 * functions, as well as any outside update functions that they directly depend
 * on but that were not in the provided list. The vertices of the graph
 * represent the update functions themselves, and each edge represents a
 * requirement that the first update function needs to run before the second.
 * For example, consider this graph:
 *
 * system_update_8001 ---> system_update_8002 ---> system_update_8003
 *
 * Visually, this indicates that system_update_8001() must run before
 * system_update_8002(), which in turn must run before system_update_8003().
 *
 * The function takes into account standard dependencies within each module, as
 * shown above (i.e., the fact that each module's updates must run in numerical
 * order), but also finds any cross-module dependencies that are defined by
 * modules which implement hook_update_dependencies(), and builds them into the
 * graph as well.
 *
 * @param $update_functions
 *   An organized array of update functions, in the format returned by
 *   update_get_update_function_list().
 *
 * @return
 *   A multidimensional array representing the dependency graph, suitable for
 *   passing in to Drupal\Component\Graph\Graph::searchAndSort(), but with extra
 *   information about each update function also included. Each array key
 *   contains the name of an update function, including all update functions
 *   from the provided list as well as any outside update functions which they
 *   directly depend on. Each value is an associative array containing the
 *   following keys:
 *   - 'edges': A representation of any other update functions that immediately
 *     depend on this one. See Drupal\Component\Graph\Graph::searchAndSort() for
 *     more details on the format.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see \Drupal\Component\Graph\Graph::searchAndSort()
 * @see update_resolve_dependencies()
 */
function update_build_dependency_graph($update_functions) {
  // Initialize an array that will define a directed graph representing the
  // dependencies between update functions.
  $graph = [];

  // Go through each update function and build an initial list of dependencies.
  foreach ($update_functions as $module => $functions) {
    $previous_function = NULL;
    foreach ($functions as $number => $function) {
      // Add an edge to the directed graph representing the fact that each
      // update function in a given module must run after the update that
      // numerically precedes it.
      if ($previous_function) {
        $graph[$previous_function]['edges'][$function] = TRUE;
      }
      $previous_function = $function;

      // Define the module and update number associated with this function.
      $graph[$function]['module'] = $module;
      $graph[$function]['number'] = $number;
    }
  }

  // Now add any explicit update dependencies declared by modules.
  $update_dependencies = update_retrieve_dependencies();
  foreach ($graph as $function => $data) {
    if (!empty($update_dependencies[$data['module']][$data['number']])) {
      foreach ($update_dependencies[$data['module']][$data['number']] as $module => $number) {
        $dependency = $module . '_update_' . $number;
        $graph[$dependency]['edges'][$function] = TRUE;
        $graph[$dependency]['module'] = $module;
        $graph[$dependency]['number'] = $number;
      }
    }
  }

  return $graph;
}

/**
 * Determines if a module update is missing or unavailable.
 *
 * @param $module
 *   The name of the module.
 * @param $number
 *   The number of the update within that module.
 * @param $update_functions
 *   An organized array of update functions, in the format returned by
 *   update_get_update_function_list(). This should represent all module
 *   updates that are requested to run at the time this function is called.
 *
 * @return
 *   TRUE if the provided module update is not installed or is not in the
 *   provided list of updates to run; FALSE otherwise.
 */
function update_is_missing($module, $number, $update_functions) {
  return !isset($update_functions[$module][$number]) || !function_exists($update_functions[$module][$number]);
}

/**
 * Determines if a module update has already been performed.
 *
 * @param $module
 *   The name of the module.
 * @param $number
 *   The number of the update within that module.
 *
 * @return
 *   TRUE if the database schema indicates that the update has already been
 *   performed; FALSE otherwise.
 */
function update_already_performed($module, $number) {
  return $number <= \Drupal::service('update.update_hook_registry')->getInstalledVersion($module);
}

/**
 * Invokes hook_update_dependencies() in all installed modules.
 *
 * This function is similar to \Drupal::moduleHandler()->invokeAll(), with the
 * main difference that it does not require that a module be enabled to invoke
 * its hook, only that it be installed. This allows the update system to
 * properly perform updates even on modules that are currently disabled.
 *
 * @return
 *   An array of return values obtained by merging the results of the
 *   hook_update_dependencies() implementations in all installed modules.
 *
 * @see \Drupal\Core\Extension\ModuleHandlerInterface::invokeAll()
 * @see hook_update_dependencies()
 */
function update_retrieve_dependencies() {
  $return = [];
  /** @var \Drupal\Core\Extension\ModuleExtensionList */
  $extension_list = \Drupal::service('extension.list.module');
  /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
  $update_registry = \Drupal::service('update.update_hook_registry');
  // Get a list of installed modules, arranged so that we invoke their hooks in
  // the same order that \Drupal::moduleHandler()->invokeAll() does.
  foreach ($update_registry->getAllInstalledVersions() as $module => $schema) {
    // Skip modules that are entirely missing from the filesystem here, since
    // module_load_install() will call trigger_error() if invoked on a module
    // that doesn't exist. There's no way to catch() that, so avoid it entirely.
    // This can happen when there are orphaned entries in the system.schema k/v
    // store for modules that have been removed from a site without first being
    // cleanly uninstalled. We don't care here if the module has been installed
    // or not, since we'll filter those out in update_get_update_list().
    if ($schema == $update_registry::SCHEMA_UNINSTALLED || !$extension_list->exists($module)) {
      // Nothing to upgrade.
      continue;
    }
    $function = $module . '_update_dependencies';
    // Ensure install file is loaded.
    \Drupal::moduleHandler()->loadInclude($module, 'install');
    if (function_exists($function)) {
      $updated_dependencies = $function();
      // Each implementation of hook_update_dependencies() returns a
      // multidimensional, associative array containing some keys that
      // represent module names (which are strings) and other keys that
      // represent update function numbers (which are integers). We cannot use
      // array_merge_recursive() to properly merge these results, since it
      // treats strings and integers differently. Therefore, we have to
      // explicitly loop through the expected array structure here and perform
      // the merge manually.
      if (isset($updated_dependencies) && is_array($updated_dependencies)) {
        foreach ($updated_dependencies as $module_name => $module_data) {
          foreach ($module_data as $update_version => $update_data) {
            foreach ($update_data as $module_dependency => $update_dependency) {
              // If there are redundant dependencies declared for the same
              // update function (so that it is declared to depend on more than
              // one update from a particular module), record the dependency on
              // the highest numbered update here, since that automatically
              // implies the previous ones. For example, if one module's
              // implementation of hook_update_dependencies() required this
              // ordering:
              //
              // system_update_8002 ---> user_update_8001
              //
              // but another module's implementation of the hook required this
              // one:
              //
              // system_update_8003 ---> user_update_8001
              //
              // we record the second one, since system_update_8002() is always
              // guaranteed to run before system_update_8003() anyway (within
              // an individual module, updates are always run in numerical
              // order).
              if (!isset($return[$module_name][$update_version][$module_dependency]) || $update_dependency > $return[$module_name][$update_version][$module_dependency]) {
                $return[$module_name][$update_version][$module_dependency] = $update_dependency;
              }
            }
          }
        }
      }
    }
  }

  return $return;
}
