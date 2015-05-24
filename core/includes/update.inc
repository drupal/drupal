<?php

/**
 * @file
 * Drupal database update API.
 *
 * This file contains functions to perform database updates for a Drupal
 * installation. It is included and used extensively by update.php.
 */

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\Error;

/**
 * Disables any extensions that are incompatible with the current core version.
 */
function update_fix_compatibility() {
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');
  $save = FALSE;
  foreach (array('module', 'theme') as $type) {
    foreach ($extension_config->get($type) as $name => $weight) {
      if (update_check_incompatibility($name, $type)) {
        $extension_config->clear("$type.$name");
        $save = TRUE;
      }
    }
  }
  if ($save) {
    $extension_config->set('module', module_config_sort($extension_config->get('module')));
    $extension_config->save();
  }
}

/**
 * Tests the compatibility of a module or theme.
 */
function update_check_incompatibility($name, $type = 'module') {
  static $themes, $modules;

  // Store values of expensive functions for future use.
  if (empty($themes) || empty($modules)) {
    // We need to do a full rebuild here to make sure the database reflects any
    // code changes that were made in the filesystem before the update script
    // was initiated.
    $themes = \Drupal::service('theme_handler')->rebuildThemeData();
    $modules = system_rebuild_module_data();
  }

  if ($type == 'module' && isset($modules[$name])) {
    $file = $modules[$name];
  }
  elseif ($type == 'theme' && isset($themes[$name])) {
    $file = $themes[$name];
  }
  if (!isset($file)
      || !isset($file->info['core'])
      || $file->info['core'] != \Drupal::CORE_COMPATIBILITY
      || version_compare(phpversion(), $file->info['php']) < 0) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Returns whether the minimum schema requirement has been satisfied.
 *
 * @return array
 *  A requirements info array.
 */
function update_system_schema_requirements() {
  $requirements = array();

  $system_schema = drupal_get_installed_schema_version('system');

  $requirements['minimum schema']['title'] = 'Minimum schema version';
  if ($system_schema >= \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
    $requirements['minimum schema'] += array(
      'value' => 'The installed schema version meets the minimum.',
      'description' => 'Schema version: ' . $system_schema,
    );
  }
  else {
    $requirements['minimum schema'] += array(
      'value' => 'The installed schema version does not meet the minimum.',
      'severity' => REQUIREMENT_ERROR,
      'description' => 'Your system schema version is ' . $system_schema . '. Updating directly from a schema version prior to 8000 is not supported. You must <a href="https://www.drupal.org/node/2179269">migrate your site to Drupal 8</a> first.',
    );
  }

  return $requirements;
}

/**
 * Checks update requirements and reports errors and (optionally) warnings.
 */
function update_check_requirements() {
  // Check requirements of all loaded modules.
  $requirements = \Drupal::moduleHandler()->invokeAll('requirements', array('update'));
  $requirements += update_system_schema_requirements();
  return $requirements;
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
 */
function update_set_schema($module, $schema_version) {
  \Drupal::keyValue('system.schema')->set($module, $schema_version);
  // system_list_reset() is in module.inc but that would only be available
  // once the variable bootstrap is done.
  require_once __DIR__ . '/module.inc';
  system_list_reset();
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
 * throw new UpdateException(t('Description of what went wrong'));
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
  if (!empty($context['results']['#abort']) && array_intersect($context['results']['#abort'], array_merge($dependency_map, array($function)))) {
    return;
  }

  $ret = array();
  if (function_exists($function)) {
    try {
      $ret['results']['query'] = $function($context['sandbox']);
      $ret['results']['success'] = TRUE;
    }
    // @TODO We may want to do different error handling for different
    // exception types, but for now we'll just log the exception and
    // return the message for printing.
    catch (Exception $e) {
      watchdog_exception('update', $e);

      $variables = Error::decodeException($e);
      unset($variables['backtrace']);
      // The exception message is run through
      // \Drupal\Component\Utility\SafeMarkup::checkPlain() by
      // \Drupal\Core\Utility\Error::decodeException().
      $ret['#abort'] = array('success' => FALSE, 'query' => t('%type: !message in %function (line %line of %file).', $variables));
    }
  }

  if (isset($context['sandbox']['#finished'])) {
    $context['finished'] = $context['sandbox']['#finished'];
    unset($context['sandbox']['#finished']);
  }

  if (!isset($context['results'][$module])) {
    $context['results'][$module] = array();
  }
  if (!isset($context['results'][$module][$number])) {
    $context['results'][$module][$number] = array();
  }
  $context['results'][$module][$number] = array_merge($context['results'][$module][$number], $ret);

  if (!empty($ret['#abort'])) {
    // Record this function in the list of updates that were aborted.
    $context['results']['#abort'][] = $function;
  }

  // Record the schema update if it was completed successfully.
  if ($context['finished'] == 1 && empty($ret['#abort'])) {
    drupal_set_installed_schema_version($module, $number);
  }

  $context['message'] = 'Updating ' . SafeMarkup::checkPlain($module) . ' module';
}

/**
 * Performs entity definition updates, which can trigger schema updates.
 *
 * @param $module
 *   The module whose update will be run.
 * @param $number
 *   The update number to run.
 * @param $context
 *   The batch context array.
 */
function update_entity_definitions($module, $number, &$context) {
  try {
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }
  catch (EntityStorageException $e) {
    watchdog_exception('update', $e);
    $variables = Error::decodeException($e);
    unset($variables['backtrace']);
    // The exception message is run through
    // \Drupal\Component\Utility\SafeMarkup::checkPlain() by
    // \Drupal\Core\Utility\Error::decodeException().
    $ret['#abort'] = array('success' => FALSE, 'query' => t('%type: !message in %function (line %line of %file).', $variables));
    $context['results'][$module][$number] = $ret;
    $context['results']['#abort'][] = 'update_entity_definitions';
  }
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
  $ret = array('system' => array());

  $modules = drupal_get_installed_schema_version(NULL, FALSE, TRUE);
  foreach ($modules as $module => $schema_version) {
    // Skip uninstalled and incompatible modules.
    if ($schema_version == SCHEMA_UNINSTALLED || update_check_incompatibility($module)) {
      continue;
    }
    // Display a requirements error if the user somehow has a schema version
    // from the previous Drupal major version.
    if ($schema_version < \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
      $ret[$module]['warning'] = '<em>' . $module . '</em> module cannot be updated. Its schema version is ' . $schema_version . ', which is from an earlier major release of Drupal. You will need to <a href="https://www.drupal.org/node/2127611">migrate the data for this module</a> instead.';
      continue;
    }
    // Otherwise, get the list of updates defined by this module.
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      // \Drupal::moduleHandler()->invoke() returns NULL for non-existing hooks,
      // so if no updates are removed, it will == 0.
      $last_removed = \Drupal::moduleHandler()->invoke($module, 'update_last_removed');
      if ($schema_version < $last_removed) {
        $ret[$module]['warning'] = '<em>' . $module . '</em> module cannot be updated. Its schema version is ' . $schema_version . '. Updates up to and including ' . $last_removed . ' have been removed in this release. In order to update <em>' . $module . '</em> module, you will first <a href="https://www.drupal.org/upgrade">need to upgrade</a> to the last version in which these updates were available.';
        continue;
      }

      $updates = array_combine($updates, $updates);
      foreach (array_keys($updates) as $update) {
        if ($update == \Drupal::CORE_MINIMUM_SCHEMA_VERSION) {
          $ret[$module]['warning'] = '<em>' . $module . '</em> module cannot be updated. It contains an update numbered as ' . \Drupal::CORE_MINIMUM_SCHEMA_VERSION . ' which is reserved for the earliest installation of a module in Drupal ' .  \Drupal::CORE_COMPATIBILITY . ', before any updates. In order to update <em>' . $module . '</em> module, you will need to install a version of the module with valid updates.';
          continue 2;
        }
        if ($update > $schema_version) {
          // The description for an update comes from its Doxygen.
          $func = new ReflectionFunction($module . '_update_' . $update);
          $description = str_replace(array("\n", '*', '/'), '', $func->getDocComment());
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
  uasort($graph, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));

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
      $data['missing_dependencies'] = array();
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
  $update_functions = array();
  foreach ($starting_updates as $module => $version) {
    $update_functions[$module] = array();
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
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
  $graph = array();

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
  return $number <= drupal_get_installed_schema_version($module);
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
 * @see \Drupal::moduleHandler()->invokeAll()
 * @see hook_update_dependencies()
 */
function update_retrieve_dependencies() {
  $return = array();
  // Get a list of installed modules, arranged so that we invoke their hooks in
  // the same order that \Drupal::moduleHandler()->invokeAll() does.
  foreach (\Drupal::keyValue('system.schema')->getAll() as $module => $schema) {
    if ($schema == SCHEMA_UNINSTALLED) {
      // Nothing to upgrade.
      continue;
    }
    $function = $module . '_update_dependencies';
    // Ensure install file is loaded.
    module_load_install($module);
    if (function_exists($function)) {
      $result = $function();
      // Each implementation of hook_update_dependencies() returns a
      // multidimensional, associative array containing some keys that
      // represent module names (which are strings) and other keys that
      // represent update function numbers (which are integers). We cannot use
      // array_merge_recursive() to properly merge these results, since it
      // treats strings and integers differently. Therefore, we have to
      // explicitly loop through the expected array structure here and perform
      // the merge manually.
      if (isset($result) && is_array($result)) {
        foreach ($result as $module => $module_data) {
          foreach ($module_data as $update => $update_data) {
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
              if (!isset($return[$module][$update][$module_dependency]) || $update_dependency > $return[$module][$update][$module_dependency]) {
                $return[$module][$update][$module_dependency] = $update_dependency;
              }
            }
          }
        }
      }
    }
  }

  return $return;
}

/**
 * Replace permissions during update.
 *
 * This function can replace one permission to several or even delete an old
 * one.
 *
 * @param array $replace
 *   An associative array. The keys are the old permissions the values are lists
 *   of new permissions. If the list is an empty array, the old permission is
 *   removed.
 */
function update_replace_permissions($replace) {
  $prefix = 'user.role.';
  $cut = strlen($prefix);
  $role_names = \Drupal::service('config.storage')->listAll($prefix);
  foreach ($role_names as $role_name) {
    $rid = substr($role_name, $cut);
    $config = \Drupal::config("user.role.$rid");
    $permissions = $config->get('permissions') ?: array();
    foreach ($replace as $old_permission => $new_permissions) {
      if (($index = array_search($old_permission, $permissions)) !== FALSE) {
        unset($permissions[$index]);
        $permissions = array_unique(array_merge($permissions, $new_permissions));
      }
    }
    $config
      ->set('permissions', $permissions)
      ->save();
  }
}

/**
 * Returns a list of languages set up on the site during upgrades.
 *
 * @param $flags
 *   (optional) Specifies the state of the languages that have to be returned.
 *   It can be: LanguageInterface::STATE_CONFIGURABLE,
 *   LanguageInterface::STATE_LOCKED, or LanguageInterface::STATE_ALL.
 *
 * @return \Drupal\Core\Language\LanguageInterface[]
 *   An associative array of languages, keyed by the language code, ordered by
 *   weight ascending and name ascending.
 */
function update_language_list($flags = LanguageInterface::STATE_CONFIGURABLE) {

  $languages = &drupal_static(__FUNCTION__);

  // Initialize master language list.
  if (!isset($languages)) {
    // Initialize local language list cache.
   $languages = array();

    // Fill in master language list based on current configuration.
    $default = \Drupal::languageManager()->getDefaultLanguage();
    if (\Drupal::languageManager()->isMultilingual() || \Drupal::moduleHandler()->moduleExists('language')) {
      // Use language module configuration if available. We can not use
      // entity_load_multiple() because this breaks during updates.
      $language_entities = \Drupal::configFactory()->listAll('language.entity.');

      // Initialize default property so callers have an easy reference and can
      // save the same object without data loss.
      foreach ($language_entities as $langcode_config_name) {
        $langcode = substr($langcode_config_name, strlen('language.entity.'));
        $info = \Drupal::config($langcode_config_name)->get();
        $languages[$langcode] = new Language(array(
          'default' => ($info['id'] == $default->getId()),
          'name' => $info['label'],
          'id' => $info['id'],
          'direction' => $info['direction'],
          'locked' => $info['locked'],
          'weight' => $info['weight'],
        ));
      }
      Language::sort($languages);
    }
    else {
      // No language module, so use the default language only.
      $languages = array($default->getId() => $default);
      // Add the special languages, they will be filtered later if needed.
      $languages += \Drupal::languageManager()->getDefaultLockedLanguages($default->getWeight());
    }
  }

  // Filter the full list of languages based on the value of the $all flag. By
  // default we remove the locked languages, but the caller may request for
  // those languages to be added as well.
  $filtered_languages = array();

  // Add the site's default language if flagged as allowed value.
  if ($flags & LanguageInterface::STATE_SITE_DEFAULT) {
    $default = \Drupal::languageManager()->getDefaultLanguage();
    // Rename the default language.
    $default->setName(t("Site's default language (@lang_name)", array('@lang_name' => $default->getName())));
    $filtered_languages[LanguageInterface::LANGCODE_SITE_DEFAULT] = $default;
  }

  foreach ($languages as $langcode => $language) {
    if (($language->isLocked() && !($flags & LanguageInterface::STATE_LOCKED)) || (!$language->isLocked() && !($flags & LanguageInterface::STATE_CONFIGURABLE))) {
      continue;
    }
    $filtered_languages[$langcode] = $language;
  }

  return $filtered_languages;
}
