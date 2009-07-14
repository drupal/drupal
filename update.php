<?php
// $Id$

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * @file
 * Administrative page for handling updates from one Drupal version to another.
 *
 * Point your browser to "http://www.example.com/update.php" and follow the
 * instructions.
 *
 * If you are not logged in as administrator, you will need to modify the access
 * check statement inside your settings.php file. After finishing the upgrade,
 * be sure to open settings.php again, and change it back to its original state!
 */

/**
 * Global flag to identify update.php run, and so avoid various unwanted
 * operations, such as hook_init() and hook_exit() invokes, css/js preprocessing
 * and translation, and solve some theming issues. This flag is checked on several
 * places in Drupal code (not just update.php).
 */
define('MAINTENANCE_MODE', 'update');

/**
 * Add a column to a database using syntax appropriate for PostgreSQL.
 * Save result of SQL commands in $ret array.
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
 *   Array to which results will be added.
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

function update_selection_page() {
  drupal_set_title('Drupal database update');
  $output = drupal_render(drupal_get_form('update_script_selection_form'));

  update_task_list('select');

  return $output;
}

function update_script_selection_form() {
  $form = array();
  $count = 0;
  $form['start'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#collapsed' => TRUE,
    '#collapsible' => TRUE,
  );

  // Ensure system.module's updates appear first
  $form['start']['system'] = array();

  $modules = drupal_get_installed_schema_version(NULL, FALSE, TRUE);
  foreach ($modules as $module => $schema_version) {
    $pending = array();
    $updates = drupal_get_schema_versions($module);
    // Skip incompatible module updates completely, otherwise test schema versions.
    if (!update_check_incompatibility($module) && $updates !== FALSE && $schema_version >= 0) {
      // module_invoke returns NULL for nonexisting hooks, so if no updates
      // are removed, it will == 0.
      $last_removed = module_invoke($module, 'update_last_removed');
      if ($schema_version < $last_removed) {
        $form['start'][$module] = array(
          '#title' => $module,
          '#item'  => '<em>' . $module . '</em> module can not be updated. Its schema version is ' . $schema_version . '. Updates up to and including ' . $last_removed . ' have been removed in this release. In order to update <em>' . $module . '</em> module, you will first <a href="http://drupal.org/upgrade">need to upgrade</a> to the last version in which these updates were available.',
          '#prefix' => '<div class="warning">',
          '#suffix' => '</div>',
        );
        continue;
      }
      $updates = drupal_map_assoc($updates);
      foreach (array_keys($updates) as $update) {
        if ($update > $schema_version) {
          // The description for an update comes from its Doxygen.
          $func = new ReflectionFunction($module . '_update_' . $update);
          $description = str_replace(array("\n", '*', '/'), '', $func->getDocComment());
          $pending[] = "$update - $description";
          if (!isset($default)) {
            $default = $update;
          }
        }
      }
      if (!empty($pending)) {
        if (!isset($default)) {
          $default = $schema_version;
        }
        $form['start'][$module] = array(
          '#type' => 'hidden',
          '#value' => $default,
        );
        $form['start'][$module . '_updates'] = array(
          '#markup' => theme('item_list', $pending, $module . ' module'),
        );
      }
    }
    unset($default);
    $count = $count + count($pending);
  }

  if (empty($count)) {
    drupal_set_message(t('No pending updates.'));
    unset($form);
    $form['links'] = array(
      '#markup' => theme('item_list', update_helpful_links()),
    );
  }
  else {
    $form['help'] = array(
      '#markup' => '<p>The version of Drupal you are updating from has been automatically detected.</p>',
      '#weight' => -5,
    );
    $form['start']['#title'] = strtr('!num pending updates', array('!num' => $count));
    $form['has_js'] = array(
      '#type' => 'hidden',
      '#default_value' => FALSE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Apply pending updates',
    );
  }
  return $form;
}

function update_batch() {
  global $base_url;

  // During the update, bring the site offline so that schema changes do not
  // affect visiting users.
  $_SESSION['site_offline'] = variable_get('site_offline', FALSE);
  if ($_SESSION['site_offline'] == FALSE) {
    variable_set('site_offline', TRUE);
  }

  $operations = array();
  // Set the installed version so updates start at the correct place.
  foreach ($_POST['start'] as $module => $version) {
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
  $batch = array(
    'operations' => $operations,
    'title' => 'Updating',
    'init_message' => 'Starting updates',
    'error_message' => 'An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.',
    'finished' => 'update_finished',
  );
  batch_set($batch);
  batch_process($base_url . '/update.php?op=results', $base_url . '/update.php');
}

function update_finished($success, $results, $operations) {
  // clear the caches in case the data has been updated.
  drupal_flush_all_caches();

  $_SESSION['update_results'] = $results;
  $_SESSION['update_success'] = $success;
  $_SESSION['updates_remaining'] = $operations;

  // Now that the update is done, we can put the site back online if it was
  // previously turned off.
  if (isset($_SESSION['site_offline']) && $_SESSION['site_offline'] == FALSE) {
    variable_set('site_offline', FALSE);
    unset($_SESSION['site_offline']);
  }
}

function update_helpful_links() {
  // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
  $links[] = '<a href="' . base_path() . '">Front page</a>';
  $links[] = '<a href="' . base_path() . '?q=admin">Administration pages</a>';
  return $links;
}

function update_results_page() {
  drupal_set_title('Drupal database update');
  $links = update_helpful_links();

  update_task_list();
  // Report end result
  if (module_exists('dblog')) {
    $log_message = ' All errors have been <a href="' . base_path() . '?q=admin/reports/dblog">logged</a>.';
  }
  else {
    $log_message = ' All errors have been logged.';
  }

  if ($_SESSION['update_success']) {
    $output = '<p>Updates were attempted. If you see no failures below, you may proceed happily to the <a href="' . base_path() . '?q=admin">administration pages</a>. Otherwise, you may need to update your database manually.' . $log_message . '</p>';
  }
  else {
    list($module, $version) = array_pop(reset($_SESSION['updates_remaining']));
    $output = '<p class="error">The update process was aborted prematurely while running <strong>update #' . $version . ' in ' . $module . '.module</strong>.' . $log_message;
    if (module_exists('dblog')) {
      $output .= ' You may need to check the <code>watchdog</code> database table manually.';
    }
    $output .= '</p>';
  }

  if (!empty($GLOBALS['update_free_access'])) {
    $output .= "<p><strong>Reminder: don't forget to set the <code>\$update_free_access</code> value in your <code>settings.php</code> file back to <code>FALSE</code>.</strong></p>";
  }

  $output .= theme('item_list', $links);

  // Output a list of queries executed
  if (!empty($_SESSION['update_results'])) {
    $output .= '<div id="update-results">';
    $output .= '<h2>The following queries were executed</h2>';
    foreach ($_SESSION['update_results'] as $module => $updates) {
      $output .= '<h3>' . $module . ' module</h3>';
      foreach ($updates as $number => $queries) {
        if ($number != '#abort') {
          $output .= '<h4>Update #' . $number . '</h4>';
          $output .= '<ul>';
          foreach ($queries as $query) {
            if ($query['success']) {
              $output .= '<li class="success">' . $query['query'] . '</li>';
            }
            else {
              $output .= '<li class="failure"><strong>Failed:</strong> ' . $query['query'] . '</li>';
            }
          }
          if (!count($queries)) {
            $output .= '<li class="none">No queries</li>';
          }
        }
        $output .= '</ul>';
      }
    }
    $output .= '</div>';
  }
  unset($_SESSION['update_results']);
  unset($_SESSION['update_success']);

  return $output;
}

function update_info_page() {
  // Change query-strings on css/js files to enforce reload for all users.
  _drupal_flush_css_js();
  // Flush the cache of all data for the update status module.
  if (db_table_exists('cache_update')) {
    cache_clear_all('*', 'cache_update', TRUE);
  }

  update_task_list('info');
  drupal_set_title('Drupal database update');
  $token = drupal_get_token('update');
  $output = '<p>Use this utility to update your database whenever a new release of Drupal or a module is installed.</p><p>For more detailed information, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>';
  $output .= "<ol>\n";
  $output .= "<li><strong>Back up your database</strong>. This process will change your database values and in case of emergency you may need to revert to a backup.</li>\n";
  $output .= "<li><strong>Back up your code</strong>. Hint: when backing up module code, do not leave that backup in the 'modules' or 'sites/*/modules' directories as this may confuse Drupal's auto-discovery mechanism.</li>\n";
  $output .= '<li>Put your site into <a href="' . base_path() . '?q=admin/settings/maintenance-mode">maintenance mode</a>.</li>' . "\n";
  $output .= "<li>Install your new files in the appropriate location, as described in the handbook.</li>\n";
  $output .= "</ol>\n";
  $output .= "<p>When you have performed the steps above, you may proceed.</p>\n";
  $output .= '<form method="post" action="update.php?op=selection&amp;token=' . $token . '"><p><input type="submit" value="Continue" /></p></form>';
  $output .= "\n";
  return $output;
}

function update_access_denied_page() {
  drupal_set_title('Access denied');
  return '<p>Access denied. You are not authorized to access this page. Please log in as the admin user (the first user you created). If you cannot log in, you will have to edit <code>settings.php</code> to bypass this access check. To do this:</p>
<ol>
 <li>With a text editor find the settings.php file on your system. From the main Drupal directory that you installed all the files into, go to <code>sites/your_site_name</code> if such directory exists, or else to <code>sites/default</code> which applies otherwise.</li>
 <li>There is a line inside your settings.php file that says <code>$update_free_access = FALSE;</code>. Change it to <code>$update_free_access = TRUE;</code>.</li>
 <li>As soon as the update.php script is done, you must change the settings.php file back to its original form with <code>$update_free_access = FALSE;</code>.</li>
 <li>To avoid having this problem in future, remember to log in to your website as the admin user (the user you first created) before you backup your database at the beginning of the update process.</li>
</ol>';
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
 * Add the update task list to the current page.
 */
function update_task_list($active = NULL) {
  // Default list of tasks.
  $tasks = array(
    'requirements' => 'Verify requirements',
    'info' => 'Overview',
    'select' => 'Review updates',
    'run' => 'Run updates',
    'finished' => 'Review log',
  );

  drupal_add_region_content('left', theme('task_list', $tasks, $active));
}

/**
 * Returns (and optionally stores) extra requirements that only apply during
 * particular parts of the update.php process.
 */
function update_extra_requirements($requirements = NULL) {
  static $extra_requirements = array();
  if (isset($requirements)) {
    $extra_requirements += $requirements;
  }
  return $extra_requirements;
}

/**
 * Check update requirements and report any errors.
 */
function update_check_requirements() {
  // Check the system module and update.php requirements only.
  $requirements = module_invoke('system', 'requirements', 'update');
  $requirements += update_extra_requirements();
  $severity = drupal_requirements_severity($requirements);

  // If there are issues, report them.
  if ($severity == REQUIREMENT_ERROR) {
    update_task_list('requirements');
    drupal_set_title('Requirements problem');
    $status_report = theme('status_report', $requirements);
    $status_report .= 'Please check the error messages and <a href="' . request_uri() . '">try again</a>.';
    print theme('update_page', $status_report);
    exit();
  }
}

// Some unavoidable errors happen because the database is not yet up-to-date.
// Our custom error handler is not yet installed, so we just suppress them.
ini_set('display_errors', FALSE);

// We prepare a minimal bootstrap for the update requirements check to avoid
// reaching the PHP memory limit.
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
update_prepare_d7_bootstrap();

// Determine if the current user has access to run update.php.
drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
$update_access_allowed = !empty($update_free_access) || $user->uid == 1;

// Only allow the requirements check to proceed if the current user has access
// to run updates (since it may expose sensitive information about the site's
// configuration).
$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
if (empty($op) && $update_access_allowed) {
  require_once DRUPAL_ROOT . '/includes/install.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/modules/system/system.install';

  // Load module basics.
  include_once DRUPAL_ROOT . '/includes/module.inc';
  $module_list['system']['filename'] = 'modules/system/system.module';
  $module_list['filter']['filename'] = 'modules/filter/filter.module';
  module_list(TRUE, FALSE, $module_list);
  drupal_load('module', 'system');
  drupal_load('module', 'filter');

  // Set up $language, since the installer components require it.
  drupal_language_initialize();

  // Set up theme system for the maintenance page.
  drupal_maintenance_theme();

  // Check the update requirements for Drupal.
  update_check_requirements();

  // Redirect to the update information page if all requirements were met.
  install_goto('update.php?op=info');
}

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_maintenance_theme();

// Turn error reporting back on. From now on, only fatal errors (which are
// not passed through the error handler) will cause a message to be printed.
ini_set('display_errors', TRUE);

// Only proceed with updates if the user is allowed to run them.
if ($update_access_allowed) {

  include_once DRUPAL_ROOT . '/includes/install.inc';
  include_once DRUPAL_ROOT . '/includes/batch.inc';
  drupal_load_updates();

  update_fix_d7_requirements();
  update_fix_compatibility();

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  switch ($op) {
    // update.php ops

    case 'selection':
      if (isset($_GET['token']) && $_GET['token'] == drupal_get_token('update')) {
        $output = update_selection_page();
        break;
      }

    case 'Apply pending updates':
      if (isset($_GET['token']) && $_GET['token'] == drupal_get_token('update')) {
        update_batch();
        break;
      }

    case 'info':
      $output = update_info_page();
      break;

    case 'results':
      $output = update_results_page();
      break;

    // Regular batch ops : defer to batch processing API
    default:
      update_task_list('run');
      $output = _batch_page();
      break;
  }
}
else {
  $output = update_access_denied_page();
}
if (isset($output) && $output) {
  // We defer the display of messages until all updates are done.
  $progress_page = ($batch = batch_get()) && isset($batch['running']);
  print theme('update_page', $output, !$progress_page);
}
