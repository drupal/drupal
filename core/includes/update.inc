<?php

/**
 * @file
 * Drupal database update API.
 *
 * This file contains functions to perform database updates for a Drupal
 * installation. It is included and used extensively by update.php.
 */

/**
 * Minimum schema version of Drupal 7 required for upgrade to Drupal 8.
 *
 * Upgrades from Drupal 7 to Drupal 8 require that Drupal 7 be running
 * the most recent version, or the upgrade could fail. We can't easily
 * check the Drupal 7 version once the update process has begun, so instead
 * we check the schema version of system.module in the system table.
 */
const REQUIRED_D7_SCHEMA_VERSION = '7069';

/**
 * Disable any items in the {system} table that are not core compatible.
 */
function update_fix_compatibility() {
  $incompatible = array();
  $result = db_query("SELECT name, type, status FROM {system} WHERE status = 1 AND type IN ('module','theme')");
  foreach ($result as $row) {
    if (update_check_incompatibility($row->name, $row->type)) {
      $incompatible[] = $row->name;
    }
  }
  if (!empty($incompatible)) {
    db_update('system')
      ->fields(array('status' => 0))
      ->condition('name', $incompatible, 'IN')
      ->execute();
  }
}

/**
 * Helper function to test compatibility of a module or theme.
 */
function update_check_incompatibility($name, $type = 'module') {
  static $themes, $modules;

  // Store values of expensive functions for future use.
  if (empty($themes) || empty($modules)) {
    // We need to do a full rebuild here to make sure the database reflects any
    // code changes that were made in the filesystem before the update script
    // was initiated.
    $themes = system_rebuild_theme_data();
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
      || $file->info['core'] != DRUPAL_CORE_COMPATIBILITY
      || version_compare(phpversion(), $file->info['php']) < 0) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Performs extra steps required to bootstrap when using a Drupal 7 database.
 *
 * Users who still have a Drupal 7 database (and are in the process of
 * updating to Drupal 8) need extra help before a full bootstrap can be
 * achieved. This function does the necessary preliminary work that allows
 * the bootstrap to be successful.
 *
 * No access check has been performed when this function is called, so no
 * irreversible changes to the database are made here.
 */
function update_prepare_d8_bootstrap() {
  // Allow the database system to work even if the registry has not been
  // created yet.
  include_once DRUPAL_ROOT . '/core/includes/install.inc';
  include_once DRUPAL_ROOT . '/core/modules/entity/entity.controller.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

  // If the site has not updated to Drupal 8 yet, check to make sure that it is
  // running an up-to-date version of Drupal 7 before proceeding. Note this has
  // to happen AFTER the database bootstraps because of
  // drupal_get_installed_schema_version().
  $system_schema = drupal_get_installed_schema_version('system');
  if ($system_schema < 8000) {
    $has_required_schema = $system_schema >= REQUIRED_D7_SCHEMA_VERSION;
    $requirements = array(
      'drupal 7 version' => array(
        'title' => 'Drupal 7 version',
        'value' => $has_required_schema ? 'You are running a current version of Drupal 7.' : 'You are not running a current version of Drupal 7',
        'severity' => $has_required_schema ? REQUIREMENT_OK : REQUIREMENT_ERROR,
        'description' => $has_required_schema ? '' : 'Please update your Drupal 7 installation to the most recent version before attempting to upgrade to Drupal 8',
      ),
    );
    if ($has_required_schema) {
      // Bootstrap variables so we can update theme while preparing the update
      // process.
      drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
      // Update the dynamic include paths that might be used before running the
      // proper update functions.
      update_prepare_stored_includes();
      // Update the environment for the language bootstrap if needed.
      update_prepare_d8_language();

      // Change language column to langcode in url_alias.
      if (db_table_exists('url_alias') && db_field_exists('url_alias', 'language')) {
        db_drop_index('url_alias', 'alias_language_pid');
        db_drop_index('url_alias', 'source_language_pid');
        $langcode_spec = array(
          'description' => "The language code this alias is for; if 'und', the alias will be used for unknown languages. Each Drupal path can have an alias for each supported language.",
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
         'default' => '',
        );
        $langcode_indexes = array('indexes' =>
          array(
            'alias_langcode_pid' => array('alias', 'langcode', 'pid'),
            'source_langcode_pid' => array('source', 'langcode', 'pid'),
          ),
        );
        db_change_field('url_alias', 'language', 'langcode', $langcode_spec, $langcode_indexes);
      }
    }
  }
}

/**
 * Fix stored include paths to match the "/core" migration.
 */
function update_prepare_stored_includes() {
  // Update language negotiation settings.
  foreach (language_types() as $language_type) {
    $negotiation = variable_get("language_negotiation_$language_type", array());
    foreach ($negotiation as $id => &$provider) {
      if (isset($negotiation[$id]['file']) && $negotiation[$id]['file'] == 'includes/locale.inc') {
        $negotiation[$id]['file'] = 'core/includes/locale.inc';
      }
    }
    variable_set("language_negotiation_$language_type", $negotiation);
  }
}

/**
 * Prepare Drupal 8 language changes for the bootstrap if needed.
 */
function update_prepare_d8_language() {
  if (db_table_exists('languages')) {
    $languages = db_select('languages', 'l')
      ->fields('l')
      ->execute();
    $plurals = array();
    $javascript = array();
    $prefixes = array();
    $domains = array();
    foreach ($languages as $language) {
      $plurals[$language->language] = array(
        'plurals' => $language->plurals,
        'formula' => $language->formula,
      );
      $javascript[$language->language] = $language->javascript;
      $prefixes[$language->language] = $language->prefix;
      $domains[$language->language] = $language->domain;
    }
    variable_set('locale_translation_plurals', $plurals);
    variable_set('locale_translation_javascript', $javascript);
    variable_set('locale_language_negotiation_url_prefixes', $prefixes);
    variable_set('locale_language_negotiation_url_domains', $domains);

    // Drop now unneeded columns.
    db_drop_field('languages', 'plurals');
    db_drop_field('languages', 'formula');
    db_drop_field('languages', 'javascript');
    db_drop_field('languages', 'prefix');
    db_drop_field('languages', 'domain');
    db_drop_field('languages', 'native');

    // Rename language column to langcode and set it again as the primary key.
    db_drop_primary_key('languages');
    $langcode_spec = array(
      'type' => 'varchar',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
      'description' => "Language code, e.g. 'de' or 'en-US'.",
    );
    db_change_field('languages', 'language', 'langcode', $langcode_spec, array('primary key' => array('langcode')));

    // Rename the languages table to language.
    db_rename_table('languages', 'language');

    // Finally install/enable the language module. We need to use the update
    // specific version of this function to ensure schema conflicts don't happen
    // due to our updated data.
    $modules = array('language');
    update_module_add_to_system($modules);
    update_module_enable($modules);
  }
}

/**
 * Adds modules to the system table in a Drupal core update.
 *
 * @param $modules
 *   Array of module names.
 */
function update_module_add_to_system($modules = array()) {
  // Insert module data, so we can enable the module. Calling a full module
  // list rebuild so early is costly and complex, so we just have a stop-gap.
  $info_defaults = array(
    'dependencies' => array(),
    'description' => '',
    'package' => 'Other',
    'version' => NULL,
    'php' => DRUPAL_MINIMUM_PHP,
    'files' => array(),
    'bootstrap' => 0,
  );
  foreach ($modules as $module) {
    $module_info = drupal_parse_info_file('core/modules/' . $module . '/' . $module . '.info');
    db_insert('system')
      ->fields(array(
        'filename' => 'core/modules/' . $module . '/' . $module . '.module',
        'name' => $module,
        'type' => 'module',
        'status' => 0,
        'bootstrap' => 0,
        'schema_version' => -1,
        'weight' => 0,
        'info' => serialize($module_info + $info_defaults),
      ))
      ->execute();
  }
}

/**
 * Perform Drupal 7.x to 8.x updates that are required for update.php
 * to function properly.
 *
 * This function runs when update.php is run the first time for 8.x,
 * even before updates are selected or performed. It is important
 * that if updates are not ultimately performed that no changes are
 * made which make it impossible to continue using the prior version.
 */
function update_fix_d8_requirements() {
  global $conf;

  if (drupal_get_installed_schema_version('system') < 8000 && !variable_get('update_d8_requirements', FALSE)) {
    // @todo: Make critical, first-run changes to the database here.
    variable_set('update_d8_requirements', TRUE);
  }
}

/**
 * Helper function to install a new module in Drupal 8 via hook_update_N().
 */
function update_module_enable(array $modules) {
  foreach ($modules as $module) {
    // Check for initial schema and install it. The schema version of a newly
    // installed module is always 0. Using 8000 here would be inconsistent
    // since $module_update_8000() may involve a schema change, and we want
    // to install the schema as it was before any updates were added.
    $function = $module . '_schema_0';
    if (function_exists($function)) {
      $schema = $function();
      foreach ($schema as $table => $spec) {
        db_create_table($table, $spec);
      }
    }
    // Change the schema version from SCHEMA_UNINSTALLED to 0, so any module
    // updates since the module's inception are executed in a core upgrade.
    db_update('system')
      ->condition('type', 'module')
      ->condition('name', $module)
      ->fields(array('schema_version' => 0, 'status' => 1))
      ->execute();

    // system_list_reset() is in module.inc but that would only be available
    // once the variable bootstrap is done.
    require_once DRUPAL_ROOT . '/core/includes/module.inc';
    system_list_reset();
    //  @todo: figure out what to do about hook_install() and hook_enable().
  }
}

/**
 * Perform one update and store the results for display on finished page.
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
 * DrupalUpdateException with an appropriate error message, for example:
 * @code
 * throw new DrupalUpdateException(t('Description of what went wrong'));
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

      require_once DRUPAL_ROOT . '/core/includes/errors.inc';
      $variables = _drupal_decode_exception($e);
      // The exception message is run through check_plain() by _drupal_decode_exception().
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

  $context['message'] = 'Updating ' . check_plain($module) . ' module';
}

/**
 * @class Exception class used to throw error if a module update fails.
 */
class DrupalUpdateException extends Exception { }

/**
 * Start the database update batch process.
 *
 * @param $start
 *   An array whose keys contain the names of modules to be updated during the
 *   current batch process, and whose values contain the number of the first
 *   requested update for that module. The actual updates that are run (and the
 *   order they are run in) will depend on the results of passing this data
 *   through the update dependency system.
 * @param $redirect
 *   Path to redirect to when the batch has finished processing.
 * @param $url
 *   URL of the batch processing page (should only be used for separate
 *   scripts like update.php).
 * @param $batch
 *   Optional parameters to pass into the batch API.
 * @param $redirect_callback
 *   (optional) Specify a function to be called to redirect to the progressive
 *   processing page.
 *
 * @see update_resolve_dependencies()
 */
function update_batch($start, $redirect = NULL, $url = NULL, $batch = array(), $redirect_callback = 'drupal_goto') {
  // During the update, bring the site offline so that schema changes do not
  // affect visiting users.
  $_SESSION['maintenance_mode'] = variable_get('maintenance_mode', FALSE);
  if ($_SESSION['maintenance_mode'] == FALSE) {
    variable_set('maintenance_mode', TRUE);
  }

  // Resolve any update dependencies to determine the actual updates that will
  // be run and the order they will be run in.
  $updates = update_resolve_dependencies($start);

  // Store the dependencies for each update function in an array which the
  // batch API can pass in to the batch operation each time it is called. (We
  // do not store the entire update dependency array here because it is
  // potentially very large.)
  $dependency_map = array();
  foreach ($updates as $function => $update) {
    $dependency_map[$function] = !empty($update['reverse_paths']) ? array_keys($update['reverse_paths']) : array();
  }

  $operations = array();
  foreach ($updates as $update) {
    if ($update['allowed']) {
      // Set the installed version of each module so updates will start at the
      // correct place. (The updates are already sorted, so we can simply base
      // this on the first one we come across in the above foreach loop.)
      if (isset($start[$update['module']])) {
        drupal_set_installed_schema_version($update['module'], $update['number'] - 1);
        unset($start[$update['module']]);
      }
      // Add this update function to the batch.
      $function = $update['module'] . '_update_' . $update['number'];
      $operations[] = array('update_do_one', array($update['module'], $update['number'], $dependency_map[$function]));
    }
  }
  $batch['operations'] = $operations;
  $batch += array(
    'title' => 'Updating',
    'init_message' => 'Starting updates',
    'error_message' => 'An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.',
    'finished' => 'update_finished',
    'file' => 'core/includes/update.inc',
  );
  batch_set($batch);
  batch_process($redirect, $url, $redirect_callback);
}

/**
 * Finish the update process and store results for eventual display.
 *
 * After the updates run, all caches are flushed. The update results are
 * stored into the session (for example, to be displayed on the update results
 * page in update.php). Additionally, if the site was off-line, now that the
 * update process is completed, the site is set back online.
 *
 * @param $success
 *   Indicate that the batch API tasks were all completed successfully.
 * @param $results
 *   An array of all the results that were updated in update_do_one().
 * @param $operations
 *   A list of all the operations that had not been completed by the batch API.
 *
 * @see update_batch()
 */
function update_finished($success, $results, $operations) {
  // Clear the caches in case the data has been updated.
  drupal_flush_all_caches();

  $_SESSION['update_results'] = $results;
  $_SESSION['update_success'] = $success;
  $_SESSION['updates_remaining'] = $operations;

  // Now that the update is done, we can put the site back online if it was
  // previously in maintenance mode.
  if (isset($_SESSION['maintenance_mode']) && $_SESSION['maintenance_mode'] == FALSE) {
    variable_set('maintenance_mode', FALSE);
    unset($_SESSION['maintenance_mode']);
  }
}

/**
 * Return a list of all the pending database updates.
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
    // Otherwise, get the list of updates defined by this module.
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      // module_invoke returns NULL for nonexisting hooks, so if no updates
      // are removed, it will == 0.
      $last_removed = module_invoke($module, 'update_last_removed');
      if ($schema_version < $last_removed) {
        $ret[$module]['warning'] = '<em>' . $module . '</em> module can not be updated. Its schema version is ' . $schema_version . '. Updates up to and including ' . $last_removed . ' have been removed in this release. In order to update <em>' . $module . '</em> module, you will first <a href="http://drupal.org/upgrade">need to upgrade</a> to the last version in which these updates were available.';
        continue;
      }

      $updates = drupal_map_assoc($updates);
      foreach (array_keys($updates) as $update) {
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
 * algorithm in drupal_depth_first_search().
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
 *   containing all the keys provided by the drupal_depth_first_search()
 *   algorithm, which encode detailed information about the dependency chain
 *   for this update function (for example: 'paths', 'reverse_paths', 'weight',
 *   and 'component'), as well as the following additional keys:
 *   - 'allowed': A boolean which is TRUE when the update function's
 *     dependencies are met, and FALSE otherwise. Calling functions should
 *     inspect this value before running the update.
 *   - 'missing_dependencies': An array containing the names of any other
 *     update functions that are required by this one but that are unavailable
 *     to be run. This array will be empty when 'allowed' is TRUE.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see drupal_depth_first_search()
 */
function update_resolve_dependencies($starting_updates) {
  // Obtain a dependency graph for the requested update functions.
  $update_functions = update_get_update_function_list($starting_updates);
  $graph = update_build_dependency_graph($update_functions);

  // Perform the depth-first search and sort the results.
  require_once DRUPAL_ROOT . '/core/includes/graph.inc';
  drupal_depth_first_search($graph);
  uasort($graph, 'drupal_sort_weight');

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
 * system_update_8000 ---> system_update_8001 ---> system_update_8002
 *
 * Visually, this indicates that system_update_8000() must run before
 * system_update_8001(), which in turn must run before system_update_8002().
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
 *   passing in to drupal_depth_first_search(), but with extra information
 *   about each update function also included. Each array key contains the name
 *   of an update function, including all update functions from the provided
 *   list as well as any outside update functions which they directly depend
 *   on. Each value is an associative array containing the following keys:
 *   - 'edges': A representation of any other update functions that immediately
 *     depend on this one. See drupal_depth_first_search() for more details on
 *     the format.
 *   - 'module': The name of the module that this update function belongs to.
 *   - 'number': The number of this update function within that module.
 *
 * @see drupal_depth_first_search()
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
 * Invoke hook_update_dependencies() in all installed modules.
 *
 * This function is similar to module_invoke_all(), with the main difference
 * that it does not require that a module be enabled to invoke its hook, only
 * that it be installed. This allows the update system to properly perform
 * updates even on modules that are currently disabled.
 *
 * @return
 *   An array of return values obtained by merging the results of the
 *   hook_update_dependencies() implementations in all installed modules.
 *
 * @see module_invoke_all()
 * @see hook_update_dependencies()
 */
function update_retrieve_dependencies() {
  $return = array();
  // Get a list of installed modules, arranged so that we invoke their hooks in
  // the same order that module_invoke_all() does.
  $modules = db_query("SELECT name FROM {system} WHERE type = 'module' AND schema_version <> :schema ORDER BY weight ASC, name ASC", array(':schema' => SCHEMA_UNINSTALLED))->fetchCol();
  foreach ($modules as $module) {
    $function = $module . '_update_dependencies';
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
              // system_update_8001 ---> user_update_8000
              //
              // but another module's implementation of the hook required this
              // one:
              //
              // system_update_8002 ---> user_update_8000
              //
              // we record the second one, since system_update_8001() is always
              // guaranteed to run before system_update_8002() anyway (within
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
 * @defgroup update-api-7.x-to-8.x Update versions of API functions
 * @{
 * Functions similar to normal API function but not firing hooks.
 *
 * During update, it is impossible to judge the consequences of firing a hook
 * as it might hit a module not yet updated. So simplified versions of some
 * core APIs are provided.
 */

/**
 * @} End of "defgroup update-api-7.x-to-8.x"
 */
