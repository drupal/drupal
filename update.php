<?php
// $Id: update.php,v 1.236 2007/10/20 21:57:49 goba Exp $

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

  $ret[] = update_sql("ALTER TABLE {". $table ."} ADD $column $type");
  if (!empty($default)) {
    $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $column SET $default");
  }
  if (!empty($not_null)) {
    if (!empty($default)) {
      $ret[] = update_sql("UPDATE {". $table ."} SET $column = $default_val");
    }
    $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $column SET NOT NULL");
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

  $ret[] = update_sql("ALTER TABLE {". $table ."} RENAME $column TO ". $column ."_old");
  $ret[] = update_sql("ALTER TABLE {". $table ."} ADD $column_new $type");
  $ret[] = update_sql("UPDATE {". $table ."} SET $column_new = ". $column ."_old");
  if ($default) { $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $column_new SET $default"); }
  if ($not_null) { $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $column_new SET NOT NULL"); }
  $ret[] = update_sql("ALTER TABLE {". $table ."} DROP ". $column ."_old");
}

/**
 * If the schema version for Drupal core is stored in the variables table
 * (4.6.x and earlier) move it to the schema_version column of the system
 * table.
 *
 * This function may be removed when update 156 is removed, which is the last
 * update in the 4.6 to 4.7 migration.
 */
function update_fix_schema_version() {
  if ($update_start = variable_get('update_start', FALSE)) {
    // Some updates were made to the 4.6 branch and 4.7 branch. This sets
    // temporary variables to prevent the updates from being executed twice and
    // throwing errors.
    switch ($update_start) {
      case '2005-04-14':
        variable_set('update_132_done', TRUE);
        break;

      case '2005-05-06':
        variable_set('update_132_done', TRUE);
        variable_set('update_135_done', TRUE);
        break;

      case '2005-05-07':
        variable_set('update_132_done', TRUE);
        variable_set('update_135_done', TRUE);
        variable_set('update_137_done', TRUE);
        break;

    }
    // The schema_version column (added below) was changed during 4.7beta.
    // Update_170 is only for those beta users.
    variable_set('update_170_done', TRUE);

    $sql_updates = array(
      '2004-10-31: first update since Drupal 4.5.0 release' => 110,
      '2004-11-07' => 111, '2004-11-15' => 112, '2004-11-28' => 113,
      '2004-12-05' => 114, '2005-01-07' => 115, '2005-01-14' => 116,
      '2005-01-18' => 117, '2005-01-19' => 118, '2005-01-20' => 119,
      '2005-01-25' => 120, '2005-01-26' => 121, '2005-01-27' => 122,
      '2005-01-28' => 123, '2005-02-11' => 124, '2005-02-23' => 125,
      '2005-03-03' => 126, '2005-03-18' => 127, '2005-03-21' => 128,
      // The following three updates were made on the 4.6 branch
      '2005-04-14' => 128, '2005-05-06' => 128, '2005-05-07' => 128,
      '2005-04-08: first update since Drupal 4.6.0 release' => 129,
      '2005-04-10' => 130, '2005-04-11' => 131, '2005-04-14' => 132,
      '2005-04-24' => 133, '2005-04-30' => 134, '2005-05-06' => 135,
      '2005-05-08' => 136, '2005-05-09' => 137, '2005-05-10' => 138,
      '2005-05-11' => 139, '2005-05-12' => 140, '2005-05-22' => 141,
      '2005-07-29' => 142, '2005-07-30' => 143, '2005-08-08' => 144,
      '2005-08-15' => 145, '2005-08-25' => 146, '2005-09-07' => 147,
      '2005-09-18' => 148, '2005-09-27' => 149, '2005-10-15' => 150,
      '2005-10-23' => 151, '2005-10-28' => 152, '2005-11-03' => 153,
      '2005-11-14' => 154, '2005-11-27' => 155, '2005-12-03' => 156,
    );

    // Add schema version column
    switch ($GLOBALS['db_type']) {
      case 'pgsql':
        $ret = array();
        db_add_column($ret, 'system', 'schema_version', 'smallint', array('not null' => TRUE, 'default' => -1));
        break;

      case 'mysql':
      case 'mysqli':
        db_query('ALTER TABLE {system} ADD schema_version smallint(3) not null default -1');
        break;
    }
    // Set all enabled (contrib) modules to schema version 0 (installed)
    db_query('UPDATE {system} SET schema_version = 0 WHERE status = 1');

    // Set schema version for core
    drupal_set_installed_schema_version('system', $sql_updates[$update_start]);
    variable_del('update_start');
  }
}

/**
 * System update 130 changes the sessions table, which breaks the update
 * script's ability to use session variables. This changes the table
 * appropriately.
 *
 * This code, including the 'update_sessions_fixed' variable, may be removed
 * when update 130 is removed. It is part of the Drupal 4.6 to 4.7 migration.
 */
function update_fix_sessions() {
  $ret = array();

  if (drupal_get_installed_schema_version('system') < 130 && !variable_get('update_sessions_fixed', FALSE)) {
    if ($GLOBALS['db_type'] == 'mysql') {
      db_query("ALTER TABLE {sessions} ADD cache int(11) NOT NULL default '0' AFTER timestamp");
    }
    elseif ($GLOBALS['db_type'] == 'pgsql') {
      db_add_column($ret, 'sessions', 'cache', 'int', array('default' => 0, 'not null' => TRUE));
    }

    variable_set('update_sessions_fixed', TRUE);
  }
}

/**
 * System update 115 changes the watchdog table, which breaks the update
 * script's ability to use logging. This changes the table appropriately.
 *
 * This code, including the 'update_watchdog_115_fixed' variable, may be removed
 * when update 115 is removed. It is part of the Drupal 4.5 to 4.7 migration.
 */
function update_fix_watchdog_115() {
  if (drupal_get_installed_schema_version('system') < 115 && !variable_get('update_watchdog_115_fixed', FALSE)) {
    if ($GLOBALS['db_type'] == 'mysql') {
      $ret[] = update_sql("ALTER TABLE {watchdog} ADD severity tinyint(3) unsigned NOT NULL default '0'");
    }
    else if ($GLOBALS['db_type'] == 'pgsql') {
      $ret[] = update_sql('ALTER TABLE {watchdog} ADD severity smallint');
      $ret[] = update_sql('UPDATE {watchdog} SET severity = 0');
      $ret[] = update_sql('ALTER TABLE {watchdog} ALTER COLUMN severity SET NOT NULL');
      $ret[] = update_sql('ALTER TABLE {watchdog} ALTER COLUMN severity SET DEFAULT 0');
    }

    variable_set('update_watchdog_115_fixed', TRUE);
  }
}

/**
 * System update 142 changes the watchdog table, which breaks the update
 * script's ability to use logging. This changes the table appropriately.
 *
 * This code, including the 'update_watchdog_fixed' variable, may be removed
 * when update 142 is removed. It is part of the Drupal 4.6 to 4.7 migration.
 */
function update_fix_watchdog() {
  if (drupal_get_installed_schema_version('system') < 142 && !variable_get('update_watchdog_fixed', FALSE)) {
    switch ($GLOBALS['db_type']) {
      case 'pgsql':
        $ret = array();
        db_add_column($ret, 'watchdog', 'referer', 'varchar(128)', array('not null' => TRUE, 'default' => "''"));
        break;
      case 'mysql':
      case 'mysqli':
        db_query("ALTER TABLE {watchdog} ADD COLUMN referer varchar(128) NOT NULL");
        break;
    }

    variable_set('update_watchdog_fixed', TRUE);
  }
}

/**
 * Perform one update and store the results which will later be displayed on
 * the finished page.
 *
 * @param $module
 *   The module whose update will be run.
 * @param $number
 *   The update number to run.
 * @param $context
 *   The batch context array
 */
function update_do_one($module, $number, &$context) {
  $function = $module .'_update_'. $number;
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
  $context['results'][$module][$number] = array_merge($context['results'][$module][$number], $ret);;

  if ($context['finished'] == 1) {
    // Update the installed version
    drupal_set_installed_schema_version($module, $number);
  }

  $context['message'] = t('Updating @module module', array('@module' => $module));
}

function update_selection_page() {
  $output = '<p>The version of Drupal you are updating from has been automatically detected. You can select a different version, but you should not need to.</p>';
  $output .= '<p>Click Update to start the update process.</p>';

  drupal_set_title('Drupal database update');
  $output .= drupal_get_form('update_script_selection_form');

  update_task_list('select');

  return $output;
}

function update_script_selection_form() {
  $form = array();
  $form['start'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#title' => 'Select versions',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  // Ensure system.module's updates appear first
  $form['start']['system'] = array();

  foreach (module_list() as $module) {
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      $updates = drupal_map_assoc($updates);
      $updates[] = 'No updates available';
      $default = drupal_get_installed_schema_version($module);
      foreach (array_keys($updates) as $update) {
        if ($update > $default) {
          $default = $update;
          break;
        }
      }

      $form['start'][$module] = array(
        '#type' => 'select',
        '#title' => $module .' module',
        '#default_value' => $default,
        '#options' => $updates,
      );
    }
  }

  $form['has_js'] = array(
    '#type' => 'hidden',
    '#default_value' => FALSE,
    '#attributes' => array('id' => 'edit-has_js'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Update',
  );
  return $form;
}

function update_batch() {
  global $base_url;

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
  batch_process($base_url .'/update.php?op=results', $base_url .'/update.php');
}

function update_finished($success, $results, $operations) {
  // clear the caches in case the data has been updated.
  cache_clear_all('*', 'cache', TRUE);
  cache_clear_all('*', 'cache_page', TRUE);
  cache_clear_all('*', 'cache_filter', TRUE);
  drupal_clear_css_cache();
  drupal_clear_js_cache();

  $_SESSION['update_results'] = $results;
  $_SESSION['update_success'] = $success;
  $_SESSION['updates_remaining'] = $operations;
}

function update_results_page() {
  drupal_set_title('Drupal database update');
  // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
  $links[] = '<a href="'. base_path() .'">Main page</a>';
  $links[] = '<a href="'. base_path() .'?q=admin">Administration pages</a>';

  update_task_list();
  // Report end result
  if (module_exists('dblog')) {
    $log_message = ' All errors have been <a href="'. base_path() .'?q=admin/reports/dblog">logged</a>.';
  }
  else {
    $log_message = ' All errors have been logged.';
  }

  if ($_SESSION['update_success']) {
    $output = '<p>Updates were attempted. If you see no failures below, you may proceed happily to the <a href="'. base_path() .'?q=admin">administration pages</a>. Otherwise, you may need to update your database manually.'. $log_message .'</p>';
  }
  else {
    list($module, $version) = array_pop(reset($_SESSION['updates_remaining']));
    $output = '<p class="error">The update process was aborted prematurely while running <strong>update #'. $version .' in '. $module .'.module</strong>.'. $log_message;
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
      $output .= '<h3>'. $module .' module</h3>';
      foreach ($updates as $number => $queries) {
        $output .= '<h4>Update #'. $number .'</h4>';
        $output .= '<ul>';
        foreach ($queries as $query) {
          if ($query['success']) {
            $output .= '<li class="success">'. $query['query'] .'</li>';
          }
          else {
            $output .= '<li class="failure"><strong>Failed:</strong> '. $query['query'] .'</li>';
          }
        }
        if (!count($queries)) {
          $output .= '<li class="none">No queries</li>';
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
  update_task_list('info');
  drupal_set_title('Drupal database update');
  $output = '<p>Use this utility to update your database whenever a new release of Drupal or a module is installed.</p><p>For more detailed information, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>';
  $output .= "<ol>\n";
  $output .= "<li><strong>Back up your database</strong>. This process will change your database values and in case of emergency you may need to revert to a backup.</li>\n";
  $output .= "<li><strong>Back up your code</strong>. Hint: when backing up module code, do not leave that backup in the 'modules' or 'sites/*/modules' directories as this may confuse Drupal's auto-discovery mechanism.</li>\n";
  $output .= '<li>Put your site into <a href="'. base_path() .'?q=admin/settings/site-maintenance">maintenance mode</a>.</li>'."\n";
  $output .= "<li>Install your new files in the appropriate location, as described in the handbook.</li>\n";
  $output .= "</ol>\n";
  $output .= "<p>When you have performed the steps above, you may proceed.</p>\n";
  $output .= '<form method="post" action="update.php?op=selection"><input type="submit" value="Continue" /></form>';
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

// This code may be removed later. It is part of the Drupal 4.5 to 4.8 migration.
function update_fix_system_table() {
  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
  $core_modules = array('aggregator', 'archive', 'block', 'blog', 'blogapi', 'book', 'comment', 'contact', 'drupal', 'filter', 'forum', 'help', 'legacy', 'locale', 'menu', 'node', 'page', 'path', 'ping', 'poll', 'profile', 'search', 'statistics', 'story', 'system', 'taxonomy', 'throttle', 'tracker', 'upload', 'user', 'watchdog');
  foreach ($core_modules as $module) {
    $old_path = "modules/$module.module";
    $new_path = "modules/$module/$module.module";
    db_query("UPDATE {system} SET filename = '%s' WHERE filename = '%s'", $new_path, $old_path);
  }
  $row = db_fetch_object(db_query_range('SELECT * FROM {system}', 0, 1));
  if (!isset($row->weight)) {
    $ret = array();
    switch ($GLOBALS['db_type']) {
      case 'pgsql':
        db_add_column($ret, 'system', 'weight', 'smallint', array('not null' => TRUE, 'default' => 0));
        $ret[] = update_sql('CREATE INDEX {system}_weight_idx ON {system} (weight)');
        break;
      case 'mysql':
      case 'mysqli':
        $ret[] = update_sql("ALTER TABLE {system} ADD weight tinyint(2) default '0' NOT NULL, ADD KEY (weight)");
        break;
    }
  }
}

// This code may be removed later. It is part of the Drupal 4.6 to 4.7 migration.
function update_fix_access_table() {
  if (variable_get('update_access_fixed', FALSE)) {
    return;
  }

  switch ($GLOBALS['db_type']) {
    // Only for MySQL 4.1+
    case 'mysqli':
      break;
    case 'mysql':
      if (version_compare(mysql_get_server_info($GLOBALS['active_db']), '4.1.0', '<')) {
        return;
      }
      break;
    case 'pgsql':
      return;
  }

  // Convert access table to UTF-8 if needed.
  $result = db_fetch_array(db_query('SHOW CREATE TABLE {access}'));
  if (!preg_match('/utf8/i', array_pop($result))) {
    update_convert_table_utf8('access');
  }

  // Don't run again
  variable_set('update_access_fixed', TRUE);
}

/**
 * Convert a single MySQL table to UTF-8.
 *
 * We change all text columns to their corresponding binary type,
 * then back to text, but with a UTF-8 character set.
 * See: http://dev.mysql.com/doc/refman/4.1/en/charset-conversion.html
 */
function update_convert_table_utf8($table) {
  $ret = array();
  $types = array('char' => 'binary',
                 'varchar' => 'varbinary',
                 'tinytext' => 'tinyblob',
                 'text' => 'blob',
                 'mediumtext' => 'mediumblob',
                 'longtext' => 'longblob');

  // Get next table in list
  $convert_to_binary = array();
  $convert_to_utf8 = array();

  // Set table default charset
  $ret[] = update_sql('ALTER TABLE {'. $table .'} DEFAULT CHARACTER SET utf8');

  // Find out which columns need converting and build SQL statements
  $result = db_query('SHOW FULL COLUMNS FROM {'. $table .'}');
  while ($column = db_fetch_array($result)) {
    list($type) = explode('(', $column['Type']);
    if (isset($types[$type])) {
      $names = 'CHANGE `'. $column['Field'] .'` `'. $column['Field'] .'` ';
      $attributes = ' DEFAULT '. ($column['Default'] == 'NULL' ? 'NULL ' :
                     "'". db_escape_string($column['Default']) ."' ") .
                    ($column['Null'] == 'YES' ? 'NULL' : 'NOT NULL');

      $convert_to_binary[] = $names . preg_replace('/'. $type .'/i', $types[$type], $column['Type']) . $attributes;
      $convert_to_utf8[] = $names . $column['Type'] .' CHARACTER SET utf8'. $attributes;
    }
  }

  if (count($convert_to_binary)) {
    // Convert text columns to binary
    $ret[] = update_sql('ALTER TABLE {'. $table .'} '. implode(', ', $convert_to_binary));
    // Convert binary columns to UTF-8
    $ret[] = update_sql('ALTER TABLE {'. $table .'} '. implode(', ', $convert_to_utf8));
  }
  return $ret;
}

/**
 * Create tables for the split cache.
 *
 * This is part of the Drupal 4.7.x to 5.x migration.
 */
function update_create_cache_tables() {

  // If cache_filter exists, update is not necessary
  if (db_table_exists('cache_filter')) {
    return;
  }

  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("CREATE TABLE {cache_filter} (
        cid varchar(255) NOT NULL default '',
        data longblob,
        expire int NOT NULL default '0',
        created int NOT NULL default '0',
        headers text,
        PRIMARY KEY (cid),
        INDEX expire (expire)
      ) /*!40100 DEFAULT CHARACTER SET UTF8 */ ");
      $ret[] = update_sql("CREATE TABLE {cache_menu} (
        cid varchar(255) NOT NULL default '',
        data longblob,
        expire int NOT NULL default '0',
        created int NOT NULL default '0',
        headers text,
        PRIMARY KEY (cid),
        INDEX expire (expire)
      ) /*!40100 DEFAULT CHARACTER SET UTF8 */ ");
      $ret[] = update_sql("CREATE TABLE {cache_page} (
        cid varchar(255) BINARY NOT NULL default '',
        data longblob,
        expire int NOT NULL default '0',
         created int NOT NULL default '0',
        headers text,
        PRIMARY KEY (cid),
        INDEX expire (expire)
      ) /*!40100 DEFAULT CHARACTER SET UTF8 */ ");
      break;
    case 'pgsql':
      $ret[] = update_sql("CREATE TABLE {cache_filter} (
        cid varchar(255) NOT NULL default '',
        data bytea,
        expire int NOT NULL default '0',
        created int NOT NULL default '0',
        headers text,
        PRIMARY KEY (cid)
     )");
     $ret[] = update_sql("CREATE TABLE {cache_menu} (
       cid varchar(255) NOT NULL default '',
       data bytea,
       expire int NOT NULL default '0',
       created int NOT NULL default '0',
       headers text,
       PRIMARY KEY (cid)
     )");
     $ret[] = update_sql("CREATE TABLE {cache_page} (
       cid varchar(255) NOT NULL default '',
       data bytea,
       expire int NOT NULL default '0',
       created int NOT NULL default '0',
       headers text,
       PRIMARY KEY (cid)
     )");
     $ret[] = update_sql("CREATE INDEX {cache_filter}_expire_idx ON {cache_filter} (expire)");
     $ret[] = update_sql("CREATE INDEX {cache_menu}_expire_idx ON {cache_menu} (expire)");
     $ret[] = update_sql("CREATE INDEX {cache_page}_expire_idx ON {cache_page} (expire)");
     break;
  }
  return $ret;
}

/**
 * Create the batch table.
 *
 * This is part of the Drupal 5.x to 6.x migration.
 */
function update_create_batch_table() {

  // If batch table exists, update is not necessary
  if (db_table_exists('batch')) {
    return;
  }

  $schema['batch'] = array(
    'fields' => array(
      'bid'       => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE),
      'token'     => array('type' => 'varchar', 'length' => 64, 'not null' => TRUE),
      'timestamp' => array('type' => 'int', 'not null' => TRUE),
      'batch'     => array('type' => 'text', 'not null' => FALSE, 'size' => 'big')
    ),
    'primary key' => array('bid'),
    'indexes' => array('token' => array('token')),
  );

  $ret = array();
  db_create_table($ret, 'batch', $schema['batch']);
  return $ret;
}

/**
 * Disable anything in the {system} table that is not compatible with the
 * current version of Drupal core.
 */
function update_fix_compatibility() {
  $ret = array();
  $incompatible = array();
  $themes = system_theme_data();
  $modules = module_rebuild_cache();
  $query = db_query("SELECT name, type, status FROM {system} WHERE status = 1 AND type IN ('module','theme')");
  while ($result = db_fetch_object($query)) {
    $name = $result->name;
    $file = array();
    if ($result->type == 'module' && isset($modules[$name])) {
      $file = $modules[$name];
    }
    else if ($result->type == 'theme' && isset($themes[$name])) {
      $file = $themes[$name];
    }
    if (!isset($file)
        || !isset($file->info['core'])
        || $file->info['core'] != DRUPAL_CORE_COMPATIBILITY
        || version_compare(phpversion(), $file->info['php']) < 0) {
      $incompatible[] = $name;
    }
  }
  if (!empty($incompatible)) {
    $ret[] = update_sql("UPDATE {system} SET status = 0 WHERE name IN ('". implode("','", $incompatible) ."')");
  }
  return $ret;
}

/**
 * Perform Drupal 5.x to 6.x updates that are required for update.php
 * to function properly.
 *
 * This function runs when update.php is run the first time for 6.x,
 * even before updates are selected or performed.  It is important
 * that if updates are not ultimately performed that no changes are
 * made which make it impossible to continue using the prior version.
 * Just adding columns is safe.  However, renaming the
 * system.description column to owner is not.  Therefore, we add the
 * system.owner column and leave it to system_update_6008() to copy
 * the data from description and remove description. The same for
 * renaming locales_target.locale to locales_target.language, which
 * will be finished by locale_update_6002().
 */
function update_fix_d6_requirements() {
  $ret = array();

  if (drupal_get_installed_schema_version('system') < 6000 && !variable_get('update_d6_requirements', FALSE)) {
    $spec = array('type' => 'int', 'size' => 'small', 'default' => 0, 'not null' => TRUE);
    db_add_field($ret, 'cache', 'serialized', $spec);
    db_add_field($ret, 'cache_filter', 'serialized', $spec);
    db_add_field($ret, 'cache_page', 'serialized', $spec);
    db_add_field($ret, 'cache_menu', 'serialized', $spec);

    db_add_field($ret, 'system', 'info', array('type' => 'text'));
    db_add_field($ret, 'system', 'owner', array('type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''));
    if (db_table_exists('locales_target')) {
      db_add_field($ret, 'locales_target', 'language', array('type' => 'varchar', 'length' => 12, 'not null' => TRUE, 'default' => ''));
    }
    if (db_table_exists('locales_source')) {
      db_add_field($ret, 'locales_source', 'textgroup', array('type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => 'default'));
      db_add_field($ret, 'locales_source', 'version', array('type' => 'varchar', 'length' => 20, 'not null' => TRUE, 'default' => 'none'));
    }
    variable_set('update_d6_requirements', TRUE);

    // Create the cache_block table. See system_update_6027() for more details.
    $schema['cache_block'] = array(
      'fields' => array(
        'cid'        => array('type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''),
        'data'       => array('type' => 'blob', 'not null' => FALSE, 'size' => 'big'),
        'expire'     => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
        'created'    => array('type' => 'int', 'not null' => TRUE, 'default' => 0),
        'headers'    => array('type' => 'text', 'not null' => FALSE),
        'serialized' => array('type' => 'int', 'size' => 'small', 'not null' => TRUE, 'default' => 0)
      ),
      'indexes' => array('expire' => array('expire')),
      'primary key' => array('cid'),
    );
    db_create_table($ret, 'cache_block', $schema['cache_block']);
  }

  return $ret;
}

/**
 * Add the update task list to the current page.
 */
function update_task_list($active = NULL) {
  // Default list of tasks.
  $tasks = array(
    'info' => 'Overview',
    'select' => 'Select updates',
    'run' => 'Run updates',
    'finished' => 'Review log',
  );

  drupal_set_content('left', theme_task_list($tasks, $active));
}

// Some unavoidable errors happen because the database is not yet up-to-date.
// Our custom error handler is not yet installed, so we just suppress them.
ini_set('display_errors', FALSE);

include_once './includes/bootstrap.inc';
update_fix_system_table();

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_maintenance_theme();

// This must happen *after* drupal_bootstrap(), since it calls
// variable_(get|set), which only works after a full bootstrap.
update_fix_access_table();
update_create_cache_tables();
update_create_batch_table();

// Turn error reporting back on. From now on, only fatal errors (which are
// not passed through the error handler) will cause a message to be printed.
ini_set('display_errors', TRUE);

// Access check:
if (!empty($update_free_access) || $user->uid == 1) {

  include_once './includes/install.inc';
  include_once './includes/batch.inc';
  drupal_load_updates();

  update_fix_schema_version();
  update_fix_watchdog_115();
  update_fix_watchdog();
  update_fix_sessions();
  update_fix_d6_requirements();
  update_fix_compatibility();

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  switch ($op) {
    // update.php ops
    case '':
      $output = update_info_page();
      break;

    case 'selection':
      $output = update_selection_page();
      break;

    case 'Update':
      update_batch();
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
  print theme('maintenance_page', $output);
}
