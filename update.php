<?php
// $Id: update.php,v 1.155 2005/08/29 18:48:25 dries Exp $

/**
 * @file
 * Administrative page for handling updates from one Drupal version to another.
 *
 * Point your browser to "http://www.site.com/update.php" and follow the
 * instructions.
 *
 * If you are not logged in as administrator, you will need to modify the access
 * check statement below. Change the TRUE into a FALSE to disable the access
 * check. After finishing the upgrade, be sure to open this file and change the
 * FALSE back into a TRUE!
 */

// Enforce access checking?
$access_check = TRUE;

if (!ini_get("safe_mode")) {
  set_time_limit(180);
}

include_once './database/updates.inc';

function update_data($start) {
  global $sql_updates;
  $output = '';
  $sql_updates = array_slice($sql_updates, ($start-- ? $start : 0));
  foreach ($sql_updates as $date => $func) {
    $output .= '<h3 class="update">'. $date .'</h3><pre class="update">';
    $ret = $func();
    foreach ($ret as $return) {
      $output .= $return[1];
    }
    variable_set("update_start", $date);
    $output .= "</pre>\n";
  }
  db_query('DELETE FROM {cache}');
  return $output;
}

function update_selection_page() {
  global $sql_updates;

  $start = variable_get("update_start", 0);
  $i = 1;
  foreach ($sql_updates as $date => $sql) {
    $dates[$i++] = $date;
    if ($date == $start) {
      $selected = $i;
    }
  }
  $dates[$i] = "No updates available";

  // make update form and output it.
  $form = form_select("Perform updates from", "start", (isset($selected) ? $selected : -1), $dates, "This defaults to the first available update since the last update you performed.");
  $form .= form_submit("Update");

  drupal_set_title('Drupal database update');
  return form($form);
}

function update_do_updates() {
  $edit = $_POST['edit'];
  drupal_set_title('Drupal database update');
  // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
  $links[] = "<a href=\"index.php\">main page</a>";
  $links[] = "<a href=\"index.php?q=admin\">administration pages</a>";
  $output = theme('item_list', $links);
  $output .= update_data($edit['start']);
  $output .= '<p>Updates were attempted. If you see no failures above, you may proceed happily to the <a href="index.php?q=admin">administration pages</a>. Otherwise, you may need to update your database manually.</p>';
  if ($GLOBALS['access_check'] == FALSE) {
    $output .= "<p><strong>Reminder: don't forget to set the <code>\$access_check</code> value at the top of <code>update.php</code> back to <code>TRUE</code>.</strong>";
  }
  return $output;
}

function update_info_page() {
  drupal_set_title('Drupal database update');
  $output = "<ol>\n";
  $output .= "<li>Use this script to <strong>upgrade an existing Drupal installation</strong>. You don't need this script when installing Drupal from scratch.</li>";
  $output .= "<li>Before doing anything, backup your database. This process will change your database and its values, and some things might get lost.</li>\n";
  $output .= "<li>Update your Drupal sources, check the notes below and <a href=\"update.php?op=update\">run the database upgrade script</a>. Don't upgrade your database twice as it may cause problems.</li>\n";
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

include_once './includes/bootstrap.inc';
drupal_maintenance_theme();
if (isset($_GET["op"])) {
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

  // Access check:
  if (($access_check == 0) || ($user->uid == 1)) {
    $op = isset($_POST['op']) ? $_POST['op'] : '';
    switch ($op) {
      case 'Update':
        $output = update_do_updates();
        break;

      default:
        $output = update_selection_page();
        break;
    }
  }
  else {
    $output = update_access_denied_page();
  }
}
else {
  $output = update_info_page();
}

if (isset($output)) {
  print theme('maintenance_page', $output);
}

