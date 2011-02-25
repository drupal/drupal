<?php

/**
 * @file
 * Administrative page for handling updates from one Drupal version to another.
 *
 * Point your browser to "http://www.example.com/update.php" and follow the
 * instructions.
 *
 * If you are not logged in as administrator, you will need to modify the access
 * check statement below. Change the TRUE to a FALSE to disable the access
 * check. After finishing the upgrade, be sure to open this file and change the
 * FALSE back to a TRUE!
 */

// Enforce access checking?
$access_check = TRUE;


function update_sql($sql) {
  $result = db_query($sql);
  return array('success' => $result !== FALSE, 'query' => check_plain($sql));
}

/**
 * Add a column to a database using syntax appropriate for PostgreSQL.
 * Save result of SQL commands in $ret array.
 *
 * Note: when you add a column with NOT NULL and you are not sure if there are
 * already rows in the table, you MUST also add DEFAULT. Otherwise PostgreSQL won't
 * work when the table is not empty. If NOT NULL and DEFAULT are set the
 * PostgreSQL version will set values of the added column in old rows to the
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
 *     default  => NULL|FALSE|value (with or without '', it won't be added)
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
  if ($default) { $ret[] = update_sql("ALTER TABLE {". $table ."} ALTER $column SET $default"); }
  if ($not_null) {
    if ($default) { $ret[] = update_sql("UPDATE {". $table ."} SET $column = $default_val"); }
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
 *
 * @return
 *   TRUE if the update was finished. Otherwise, FALSE.
 */
function update_data($module, $number) {
  $ret = module_invoke($module, 'update_'. $number);
  // Assume the update finished unless the update results indicate otherwise.
  $finished = 1;
  if (isset($ret['#finished'])) {
    $finished = $ret['#finished'];
    unset($ret['#finished']);
  }

  // Save the query and results for display by update_finished_page().
  if (!isset($_SESSION['update_results'])) {
    $_SESSION['update_results'] = array();
  }
  if (!isset($_SESSION['update_results'][$module])) {
    $_SESSION['update_results'][$module] = array();
  }
  if (!isset($_SESSION['update_results'][$module][$number])) {
    $_SESSION['update_results'][$module][$number] = array();
  }
  $_SESSION['update_results'][$module][$number] = array_merge($_SESSION['update_results'][$module][$number], $ret);

  if ($finished == 1) {
    // Update the installed version
    drupal_set_installed_schema_version($module, $number);
  }

  return $finished;
}

function update_selection_page() {
  $output = '<p>The version of Drupal you are updating from has been automatically detected. You can select a different version, but you should not need to.</p>';
  $output .= '<p>Click Update to start the update process.</p>';

  $form = array();
  $form['start'] = array(
    '#tree' => TRUE,
    '#type' => 'fieldset',
    '#title' => 'Select versions',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  foreach (module_list() as $module) {
    $updates = drupal_get_schema_versions($module);
    if ($updates !== FALSE) {
      $updates = drupal_map_assoc($updates);
      $updates[] = 'No updates available';

      $form['start'][$module] = array(
        '#type' => 'select',
        '#title' => $module . ' module',
        '#default_value' => array_search(drupal_get_installed_schema_version($module), $updates) + 1,
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

  drupal_set_title('Drupal database update');
  // Prevent browser from using cached drupal.js or update.js
  drupal_add_js('misc/update.js', TRUE);
  $output .= drupal_get_form('update_script_selection_form', $form);

  return $output;
}

function update_update_page() {
  // Set the installed version so updates start at the correct place.
  $_SESSION['update_remaining'] = array();
  foreach ($_POST['edit']['start'] as $module => $version) {
    drupal_set_installed_schema_version($module, $version - 1);
    $max_version = max(drupal_get_schema_versions($module));
    if ($version <= $max_version) {
      foreach (range($version, $max_version) as $update) {
        $_SESSION['update_remaining'][] = array('module' => $module, 'version' => $update);
      }
    }
  }
  // Keep track of total number of updates
  $_SESSION['update_total'] = count($_SESSION['update_remaining']);

  if ($_POST['edit']['has_js']) {
    return update_progress_page();
  }
  else {
    return update_progress_page_nojs();
  }
}

function update_progress_page() {
  // Prevent browser from using cached drupal.js or update.js
  drupal_add_js('misc/progress.js', TRUE);
  drupal_add_js('misc/update.js', TRUE);

  drupal_set_title('Updating');
  $output = '<div id="progress"></div>';
  $output .= '<p id="wait">Please wait while your site is being updated.</p>';
  return $output;
}

/**
 * Perform updates for one second or until finished.
 *
 * @return
 *   An array indicating the status after doing updates. The first element is
 *   the overall percentage finished. The second element is a status message.
 */
function update_do_updates() {
  while (($update = reset($_SESSION['update_remaining']))) {
    $update_finished = update_data($update['module'], $update['version']);
    if ($update_finished == 1) {
      // Dequeue the completed update.
      unset($_SESSION['update_remaining'][key($_SESSION['update_remaining'])]);
      $update_finished = 0; // Make sure this step isn't counted double
    }
    if (timer_read('page') > 1000) {
      break;
    }
  }

  if ($_SESSION['update_total']) {
    $percentage = floor(($_SESSION['update_total'] - count($_SESSION['update_remaining']) + $update_finished) / $_SESSION['update_total'] * 100);
  }
  else {
    $percentage = 100;
  }

  // When no updates remain, clear the cache.
  if (!isset($update['module'])) {
    db_query('DELETE FROM {cache}');
  }

  return array($percentage, isset($update['module']) ? 'Updating '. $update['module'] .' module' : 'Updating complete');
}

/**
 * Perform updates for the JS version and return progress.
 */
function update_do_update_page() {
  global $conf;

  // HTTP Post required
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    drupal_set_message('HTTP Post is required.', 'error');
    drupal_set_title('Error');
    return '';
  }

  // Error handling: if PHP dies, the output will fail to parse as JSON, and
  // the Javascript will tell the user to continue to the op=error page.
  list($percentage, $message) = update_do_updates();
  print drupal_to_js(array('status' => TRUE, 'percentage' => $percentage, 'message' => $message));
}

/**
 * Perform updates for the non-JS version and return the status page.
 */
function update_progress_page_nojs() {
  drupal_set_title('Updating');

  $new_op = 'do_update_nojs';
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Error handling: if PHP dies, it will output whatever is in the output
    // buffer, followed by the error message.
    ob_start();
    $fallback = '<p class="error">An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference. Please continue to the <a href="update.php?op=error">update summary</a>.</p><p class="error">';
    print theme('maintenance_page', $fallback, FALSE, TRUE);

    list($percentage, $message) = update_do_updates();
    if ($percentage == 100) {
      $new_op = 'finished';
    }

    // Updates successful; remove fallback
    ob_end_clean();
  }
  else {
    // This is the first page so return some output immediately.
    $percentage = 0;
    $message = 'Starting updates';
  }

  drupal_set_html_head('<meta http-equiv="Refresh" content="0; URL=update.php?op='. $new_op .'">');
  $output = theme('progress_bar', $percentage, $message);
  $output .= '<p>Updating your site will take a few seconds.</p>';

  // Note: do not output drupal_set_message()s until the summary page.
  print theme('maintenance_page', $output, FALSE);
  return NULL;
}

function update_finished_page($success) {
  drupal_set_title('Drupal database update');
  // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
  $links[] = '<a href="'. base_path() .'">main page</a>';
  $links[] = '<a href="'. base_path() .'?q=admin">administration pages</a>';

  // Report end result
  if ($success) {
    $output = '<p>Updates were attempted. If you see no failures below, you may proceed happily to the <a href="index.php?q=admin">administration pages</a>. Otherwise, you may need to update your database manually. All errors have been <a href="index.php?q=admin/logs">logged</a>.</p>';
  }
  else {
    $update = reset($_SESSION['update_remaining']);
    $output = '<p class="error">The update process was aborted prematurely while running <strong>update #'. $update['version'] .' in '. $update['module'] .'.module</strong>. All other errors have been <a href="index.php?q=admin/logs">logged</a>. You may need to check the <code>watchdog</code> database table manually.</p>';
  }

  if ($GLOBALS['access_check'] == FALSE) {
    $output .= "<p><strong>Reminder: don't forget to set the <code>\$access_check</code> value at the top of <code>update.php</code> back to <code>TRUE</code>.</strong></p>";
  }

  $output .= theme('item_list', $links);

  // Output a list of queries executed
  if ($_SESSION['update_results']) {
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
    unset($_SESSION['update_results']);
  }

  return $output;
}

function update_info_page() {
  drupal_set_title('Drupal database update');
  $output = "<ol>\n";
  $output .= "<li>Use this script to <strong>upgrade an existing Drupal installation</strong>. You don't need this script when installing Drupal from scratch.</li>";
  $output .= "<li>Before doing anything, backup your database. This process will change your database and its values, and some things might get lost.</li>\n";
  $output .= "<li>Update your Drupal sources, check the notes below and <a href=\"update.php?op=selection\">run the database upgrade script</a>. Don't upgrade your database twice as it may cause problems.</li>\n";
  $output .= "<li>Go through the various administration pages to change the existing and new settings to your liking.</li>\n";
  $output .= "</ol>";
  $output .= '<p>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>';
  return $output;
}

function update_access_denied_page() {
  drupal_set_title('Access denied');
  return '<p>Access denied. You are not authorized to access this page. Please log in as the admin user (the first user you created). If you cannot log in, you will have to edit <code>update.php</code> to bypass this access check. To do this:</p>
<ol>
 <li>With a text editor find the update.php file on your system. It should be in the main Drupal directory that you installed all the files into.</li>
 <li>There is a line near top of update.php that says <code>$access_check = TRUE;</code>. Change it to <code>$access_check = FALSE;</code>.</li>
 <li>As soon as the script is done, you must change the update.php script back to its original form to <code>$access_check = TRUE;</code>.</li>
 <li>To avoid having this problem in future, remember to log in to your website as the admin user (the user you first created) before you backup your database at the beginning of the update process.</li>
</ol>';
}

// This code may be removed later.  It is part of the Drupal 4.5 to 4.7 migration.
function update_fix_system_table() {
  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
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

// This code may be removed later.  It is part of the Drupal 4.6 to 4.7 migration.
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

// Some unavoidable errors happen because the database is not yet up-to-date.
// Our custom error handler is not yet installed, so we just suppress them.
ini_set('display_errors', FALSE);

include_once './includes/bootstrap.inc';
update_fix_system_table();
update_fix_access_table();

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_maintenance_theme();

// Turn error reporting back on. From now on, only fatal errors (which are
// not passed through the error handler) will cause a message to be printed.
ini_set('display_errors', TRUE);

// Access check:
if (($access_check == FALSE) || ($user->uid == 1)) {

  include_once './includes/install.inc';

  update_fix_schema_version();
  update_fix_watchdog_115();
  update_fix_watchdog();
  update_fix_sessions();

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  switch ($op) {
    case 'Update':
      // Check for a valid form token to protect against cross site request forgeries.
      if (drupal_valid_token($_REQUEST['edit']['form_token'], 'update_script_selection_form', TRUE)) {
        $output = update_update_page();
      }
      else {
        form_set_error('form_token', t('Validation error, please try again.  If this error persists, please contact the site administrator.'));
        $output = update_selection_page();
      }
      break;

    case 'finished':
      $output = update_finished_page(true);
      break;

    case 'error':
      $output = update_finished_page(false);
      break;

    case 'do_update':
      $output = update_do_update_page();
      break;

    case 'do_update_nojs':
      $output = update_progress_page_nojs();
      break;

    case 'selection':
      $output = update_selection_page();
      break;

    default:
      $output = update_info_page();
      break;
  }
}
else {
  $output = update_access_denied_page();
}

if (isset($output)) {
  print theme('maintenance_page', $output);
}
