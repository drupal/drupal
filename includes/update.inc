<?php
// $Id$

/**
 * @file
 * Drupal database update API.
 *
 * This file contains functions to perform database updates for a Drupal
 * installation. It is included and used extensively by update.php.
 */


/**
 * Add a column to a database using syntax appropriate for PostgreSQL.
 *
 * Saves the result of the SQL commands in the referenced $ret array.
 *
 * Note: when you add a column with NOT NULL and you are not sure if there are
 * already rows in the table, you MUST also add DEFAULT. Otherwise PostgreSQL
 * won't work when the table is not empty, and db_add_column() will fail.
 * To have an empty string as the default, you must use: 'default' => "''"
 * in the $attributes array. If NOT NULL and DEFAULT are set the PostgreSQL
 * version will set values of the added column in old rows to the
 * DEFAULT value.
 *
 * @param $ret
 *   Reference to an array to which results will be added.
 * @param $table
 *   Name of the table, without {}
 * @param $column
 *   Name of the column
 * @param $type
 *   Type of column
 * @param $attributes
 *   Additional optional attributes. Recognized attributes:
 *     not null => TRUE|FALSE
 *     default  => NULL|FALSE|value (the value must be enclosed in '' marks)
 * @return
 *   nothing, but modifies $ret parameter.
 */
function db_add_column(&$ret, $table, $column, $type, $attributes = array()) {
  if (array_key_exists('not null', $attributes) and $attributes['not null']) {
    $not_null = 'NOT NULL';
  }
  if (array_key_exists('default', $attributes)) {
    if (is_null($attributes['default'])) {
      $default_val = 'NULL';
      $default = 'default NULL';
    }
    elseif ($attributes['default'] === FALSE) {
      $default = '';
    }
    else {
      $default_val = "$attributes[default]";
      $default = "default $attributes[default]";
    }
  }

  $ret[] = update_sql("ALTER TABLE {" . $table . "} ADD $column $type");
  if (!empty($default)) {
    $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $column SET $default");
  }
  if (!empty($not_null)) {
    if (!empty($default)) {
      $ret[] = update_sql("UPDATE {" . $table . "} SET $column = $default_val");
    }
    $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $column SET NOT NULL");
  }
}

/**
 * Change a column definition using syntax appropriate for PostgreSQL.
 * Save result of SQL commands in $ret array.
 *
 * Remember that changing a column definition involves adding a new column
 * and dropping an old one. This means that any indices, primary keys and
 * sequences from serial-type columns are dropped and might need to be
 * recreated.
 *
 * @param $ret
 *   Array to which results will be added.
 * @param $table
 *   Name of the table, without {}
 * @param $column
 *   Name of the column to change
 * @param $column_new
 *   New name for the column (set to the same as $column if you don't want to change the name)
 * @param $type
 *   Type of column
 * @param $attributes
 *   Additional optional attributes. Recognized attributes:
 *     not null => TRUE|FALSE
 *     default  => NULL|FALSE|value (with or without '', it won't be added)
 * @return
 *   nothing, but modifies $ret parameter.
 */
function db_change_column(&$ret, $table, $column, $column_new, $type, $attributes = array()) {
  if (array_key_exists('not null', $attributes) and $attributes['not null']) {
    $not_null = 'NOT NULL';
  }
  if (array_key_exists('default', $attributes)) {
    if (is_null($attributes['default'])) {
      $default_val = 'NULL';
      $default = 'default NULL';
    }
    elseif ($attributes['default'] === FALSE) {
      $default = '';
    }
    else {
      $default_val = "$attributes[default]";
      $default = "default $attributes[default]";
    }
  }

  $ret[] = update_sql("ALTER TABLE {" . $table . "} RENAME $column TO " . $column . "_old");
  $ret[] = update_sql("ALTER TABLE {" . $table . "} ADD $column_new $type");
  $ret[] = update_sql("UPDATE {" . $table . "} SET $column_new = " . $column . "_old");
  if ($default) {
    $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $column_new SET $default");
  }
  if ($not_null) {
    $ret[] = update_sql("ALTER TABLE {" . $table . "} ALTER $column_new SET NOT NULL");
  }
  $ret[] = update_sql("ALTER TABLE {" . $table . "} DROP " . $column . "_old");
}

/**
 * Disable anything in the {system} table that is not compatible with the
 * current version of Drupal core.
 */
function update_fix_compatibility() {
  $ret = array();
  $incompatible = array();
  $result = db_query("SELECT name, type, status FROM {system} WHERE status = 1 AND type IN ('module','theme')");
  foreach ($result as $row) {
    if (update_check_incompatibility($row->name, $row->type)) {
      $incompatible[] = $row->name;
    }
  }
  if (!empty($incompatible)) {
    $ret[] = update_sql("UPDATE {system} SET status = 0 WHERE name IN ('" . implode("','", $incompatible) . "')");
  }
  return $ret;
}

/**
 * Helper function to test compatibility of a module or theme.
 */
function update_check_incompatibility($name, $type = 'module') {
  static $themes, $modules;

  // Store values of expensive functions for future use.
  if (empty($themes) || empty($modules)) {
    $themes = _system_get_theme_data();
    $modules = system_get_module_data();
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
      || version_compare(phpversion(), $file->info['php']) < 0
      || ($type == 'module' && empty($file->info['files']))) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Users who still have a Drupal 6 database (and are in the process of
 * updating to Drupal 7) need extra help before a full bootstrap can be
 * achieved. This function does the necessary preliminary work that allows
 * the bootstrap to be successful.
 *
 * No access check has been performed when this function is called, so no
 * changes to the database should be made here.
 */
function update_prepare_d7_bootstrap() {
  // Allow the bootstrap to proceed even if a Drupal 6 settings.php file is
  // still being used.
  include_once DRUPAL_ROOT . '/includes/install.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
  global $databases, $db_url, $update_rewrite_settings;
  if (empty($databases) && !empty($db_url)) {
    $databases = update_parse_db_url($db_url);
    // Record the fact that the settings.php file will need to be rewritten.
    $update_rewrite_settings = TRUE;
    $settings_file = conf_path() . '/settings.php';
    $writable = drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_WRITABLE);
    $requirements = array(
      'settings file' => array(
        'title' => 'Settings file',
        'value' => $writable ? 'The settings file is writable.' : 'The settings file is not writable.',
        'severity' => $writable ? REQUIREMENT_OK : REQUIREMENT_ERROR,
        'description' => $writable ? '' : 'Drupal requires write permissions to <em>' . $settings_file . '</em> during the update process. If you are unsure how to grant file permissions, please consult the <a href="http://drupal.org/server-permissions">online handbook</a>.',
      ),
    );
    update_extra_requirements($requirements);
  }
  // Allow the database system to work even if the registry has not been
  // created yet.
  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
  drupal_install_initialize_database();
  spl_autoload_unregister('drupal_autoload_class');
  spl_autoload_unregister('drupal_autoload_interface');
  // The new {blocked_ips} table is used in Drupal 7 to store a list of
  // banned IP addresses. If this table doesn't exist then we are still
  // running on a Drupal 6 database, so suppress the unavoidable errors
  // that occur.
  try {
    drupal_bootstrap(DRUPAL_BOOTSTRAP_ACCESS);
  }
  catch (Exception $e) {
    if (db_table_exists('blocked_ips')) {
      throw $e;
    }
  }
}

/**
 * Perform Drupal 6.x to 7.x updates that are required for update.php
 * to function properly.
 *
 * This function runs when update.php is run the first time for 7.x,
 * even before updates are selected or performed. It is important
 * that if updates are not ultimately performed that no changes are
 * made which make it impossible to continue using the prior version.
 */
function update_fix_d7_requirements() {
  global $conf;
  $ret = array();

  // Rewrite the settings.php file if necessary.
  // @see update_prepare_d7_bootstrap().
  global $update_rewrite_settings, $db_url;
  if (!empty($update_rewrite_settings)) {
    $databases = update_parse_db_url($db_url);
    file_put_contents(conf_path() . '/settings.php', "\n" . '$databases = ' . var_export($databases, TRUE) . ';', FILE_APPEND);
  }
  if (drupal_get_installed_schema_version('system') < 7000 && !variable_get('update_d7_requirements', FALSE)) {
    // Add the cache_path table.
    $schema['cache_path'] = drupal_get_schema_unprocessed('system', 'cache');
    $schema['cache_path']['description'] = 'Cache table used for path alias lookups.';
    db_create_table($ret, 'cache_path', $schema['cache_path']);

    // Add column for locale context.
    if (db_table_exists('locales_source')) {
      db_add_field($ret, 'locales_source', 'context', array('type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => '', 'description' => 'The context this string applies to.'));
    }

    // Rename 'site_offline_message' variable to 'maintenance_mode_message'.
    // Old variable is removed in update for system.module.
    // @see system_update_7036().
    if ($message = variable_get('site_offline_message', NULL)) {
      variable_set('maintenance_mode_message', $message);
    }

    variable_set('update_d7_requirements', TRUE);
  }

  update_fix_d7_install_profile();

  return $ret;
}

/**
 * Register the currently installed profile in the system table.
 *
 * Install profiles are now treated as modules by Drupal, and have an upgrade path
 * based on their schema version in the system table.
 *
 * The install profile will be set to schema_version 0, as it has already been
 * installed. Any other hook_update_N functions provided by the install profile
 * will be run by update.php.
 */
function update_fix_d7_install_profile() {
  $profile = drupal_get_profile();


  $results = db_select('system', 's')
    ->fields('s', array('name', 'schema_version'))
    ->condition('name', $profile)
    ->condition('type', 'module')
    ->execute()
    ->fetchAll();

  if (empty($results)) {
    $filename = 'profiles/' . $profile . '/' . $profile . '.profile';

    // Read profile info file
    $info = drupal_parse_info_file(dirname($filename) . '/' . $profile . '.info');

    // Merge in defaults.
    $info = $info + array(
      'dependencies' => array(),
      'dependents' => array(),
      'description' => '',
      'package' => 'Other',
      'version' => NULL,
      'php' => DRUPAL_MINIMUM_PHP,
      'files' => array(),
    );

    // The install profile is always required.
    $file->info['required'] = TRUE;

    $values = array(
      'filename' => $filename,
      'name' => $profile,
      'info' => serialize($info),
      'schema_version' => 0,
      'type' => 'module',
      'status' => 1,
      'owner' => '',
    );

    // Install profile hooks are always executed last by the module system
    $values['weight'] = 1000;

    // Initializing the system table entry for the install profile
    db_insert('system')
      ->fields(array_keys($values))
      ->values($values)
      ->execute();

    // Reset the cached schema version.
    drupal_get_installed_schema_version($profile, TRUE);

    // Load the updates again to make sure the install profile updates are loaded
    drupal_load_updates();
  }
}

/**
 * Parse database connection URLs (in the old, pre-Drupal 7 format) and
 * return them as an array of database connection information.
 */
function update_parse_db_url($db_url) {
  $databases = array();
  if (!is_array($db_url)) {
    $db_url = array('default' => $db_url);
  }
  foreach ($db_url as $database => $url) {
    $url = parse_url($url);
    $databases[$database]['default'] = array(
      // MySQLi uses the mysql driver.
      'driver' => $url['scheme'] == 'mysqli' ? 'mysql' : $url['scheme'],
      // Remove the leading slash to get the database name.
      'database' => substr(urldecode($url['path']), 1),
      'username' => urldecode($url['user']),
      'password' => isset($url['pass']) ? urldecode($url['pass']) : '',
      'host' => urldecode($url['host']),
      'port' => isset($url['port']) ? urldecode($url['port']) : '',
    );
  }
  return $databases;
}

/**
 * Perform one update and store the results which will later be displayed on
 * the finished page.
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
 * If an exception is thrown, the current and all later updates for this module
 * will be aborted. The schema version will not be updated in this case, and all
 * the aborted updates will continue to appear on update.php as updates that
 * have not yet been run.
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
 * @param $context
 *   The batch context array
 */
function update_do_one($module, $number, &$context) {
  // If updates for this module have been aborted
  // in a previous step, go no further.
  if (!empty($context['results'][$module]['#abort'])) {
    return;
  }

  if (!isset($context['log'])) {
    $context['log'] = variable_get('update_log_queries', 0);
  }

  $ret = array();
  $function = $module . '_update_' . $number;
  if (function_exists($function)) {
    try {
      if ($context['log']) {
        Database::startLog($function);
      }
      $ret['results']['query'] = $function($context['sandbox']);
      $ret['results']['success'] = TRUE;

      // @TODO Remove this block after all updates have been converted to
      // return only strings.
      if (is_array($ret['results']['query'])) {
        $ret = $ret['results']['query'];
      }
    }
    // @TODO We may want to do different error handling for different exception
    // types, but for now we'll just print the message.
    catch (Exception $e) {
      $ret['#abort'] = array('success' => FALSE, 'query' => $e->getMessage());
    }

    if ($context['log']) {
      $ret['queries'] = Database::getLog($function);
    }
  }

  // @TODO Remove this block after all updates have been converted to return
  // only strings.
  if (isset($ret['#finished'])) {
    $context['finished'] = $ret['#finished'];
    unset($ret['#finished']);
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
    $context['results'][$module]['#abort'] = TRUE;
  }

  // Record the schema update if it was completed successfully.
  if ($context['finished'] == 1 && empty($context['results'][$module]['#abort'])) {
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
 *   An array of all the modules and which update to start at.
 * @param $redirect
 *   Path to redirect to when the batch has finished processing.
 * @param $url
 *   URL of the batch processing page (should only be used for separate
 *   scripts like update.php).
 * @param $batch
 *   Optional parameters to pass into the batch API.
 */
function update_batch($start, $redirect = NULL, $url = NULL, $batch = array()) {
  // During the update, bring the site offline so that schema changes do not
  // affect visiting users.
  $_SESSION['maintenance_mode'] = variable_get('maintenance_mode', FALSE);
  if ($_SESSION['maintenance_mode'] == FALSE) {
    variable_set('maintenance_mode', TRUE);
  }

  $operations = array();
  // Set the installed version so updates start at the correct place.
  foreach ($start as $module => $version) {
    drupal_set_installed_schema_version($module, $version - 1);
    $updates = drupal_get_schema_versions($module);
    $max_version = max($updates);
    if ($version <= $max_version) {
      foreach ($updates as $update) {
        if ($update >= $version) {
          $operations[] = array('update_do_one', array($module, $update));
        }
      }
    }
  }
  $batch['operations'] = $operations;
  $batch += array(
    'title' => 'Updating',
    'init_message' => 'Starting updates',
    'error_message' => 'An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.',
    'finished' => 'update_finished',
    'file' => 'includes/update.inc',
  );
  batch_set($batch);
  batch_process($redirect, $url);
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
    $pending = array();
    $updates = drupal_get_schema_versions($module);
    // Skip incompatible module updates, otherwise test schema versions.
    if (!update_check_incompatibility($module) && $updates !== FALSE && $schema_version >= 0) {
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

