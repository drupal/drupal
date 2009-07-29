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
  $query = db_query("SELECT name, type, status FROM {system} WHERE status = 1 AND type IN ('module','theme')");
  while ($result = db_fetch_object($query)) {
    if (update_check_incompatibility($result->name, $result->type)) {
      $incompatible[] = $result->name;
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
    variable_set('update_d7_requirements', TRUE);

    // Add column for locale context.
    if (db_table_exists('locales_source')) {
      db_add_field($ret, 'locales_source', 'context', array('type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => '', 'description' => 'The context this string applies to.'));
    }
  }

  return $ret;
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
 * An update function can force the current and all later updates for this
 * module to abort by returning a $ret array with an element like:
 * $ret['#abort'] = array('success' => FALSE, 'query' => 'What went wrong');
 * The schema version will not be updated in this case, and all the
 * aborted updates will continue to appear on update.php as updates that
 * have not yet been run.
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

  $function = $module . '_update_' . $number;
  if (function_exists($function)) {
    $ret = $function($context['sandbox']);
  }

  if (isset($ret['#finished'])) {
    $context['finished'] = $ret['#finished'];
    unset($ret['#finished']);
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

