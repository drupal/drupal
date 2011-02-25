<?php

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

// Disable access checking?
$access_check = TRUE;

if (!ini_get("safe_mode")) {
  set_time_limit(180);
}

include_once "database/updates.inc";

function update_data($start) {
  global $sql_updates;
  $sql_updates = array_slice($sql_updates, ($start-- ? $start : 0));
  foreach ($sql_updates as $date => $func) {
    print "<strong>$date</strong><br />\n<pre>\n";
    $ret = $func();
    foreach ($ret as $return) {
      print $return[1];
      print $return[2];
    }
    variable_set("update_start", $date);
    print "</pre>\n";
  }
  db_query('DELETE FROM {cache}');
}

function update_page_header($title) {
  $output = "<html><head><title>$title</title>";
  $output .= <<<EOF
      <link rel="stylesheet" type="text/css" media="print" href="misc/print.css" />
      <style type="text/css" title="layout" media="Screen">
        @import url("misc/drupal.css");
      </style>
EOF;
  $output .= "</head><body>";
  $output .= "<div id=\"logo\"><a href=\"http://drupal.org/\"><img src=\"misc/druplicon-small.png\" alt=\"Druplicon - Drupal logo\" title=\"Druplicon - Drupal logo\" /></a></div>";
  $output .= "<div id=\"update\"><h1>$title</h1>";
  return $output;
}

function update_page_footer() {
  return "</div></body></html>";
}

function update_page() {
  global $user, $sql_updates;

  if (isset($_POST['edit'])) {
    $edit = $_POST['edit'];
  }
  if (isset($_POST['op'])) {
    $op = $_POST['op'];
  }

  switch ($op) {
    case "Update":
      // make sure we have updates to run.
      print update_page_header("Drupal database update");
      $links[] = "<a href=\"index.php\">main page</a>";
      $links[] = "<a href=\"index.php?q=admin\">administration pages</a>";
      print theme("item_list", $links);
        // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
      if ($edit["start"] == -1) {
        print "No updates to perform.";
      }
      else {
        update_data($edit["start"]);
      }
      print "<br />Updates were attempted. If you see no failures above, you may proceed happily to the <a href=\"index.php?q=admin\">administration pages</a>.";
      print " Otherwise, you may need to update your database manually.";
      print update_page_footer();
      break;
    default:
      // NOTE: We need the following five lines in order to fix a bug with
      //       database.mysql (issue #15337).  We should be able to remove
      //       this work around in the future.
      $result = db_query("SELECT * FROM {variable} WHERE name = 'update_start' AND value LIKE '%;\"'");
      if ($variable = db_fetch_object($result)) {
        $variable->value = unserialize(substr($variable->value, 0, -2) .'";');
        variable_set('update_start', $variable->value);
      }

      $start = variable_get("update_start", 0);
      $dates[] = "All";
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
      print update_page_header("Drupal database update");
      print form($form);
      print update_page_footer();
      break;
  }
}

function update_info() {
  print update_page_header("Drupal database update");
  print "<ol>\n";
  print "<li>Use this script to <strong>upgrade an existing Drupal installation</strong>.  You don't need this script when installing Drupal from scratch.</li>";
  print "<li>Before doing anything, backup your database. This process will change your database and its values, and some things might get lost.</li>\n";
  print "<li>Update your Drupal sources, check the notes below and <a href=\"update.php?op=update\">run the database upgrade script</a>.  Don't upgrade your database twice as it may cause problems.</p></li>\n";
  print "<li>Go through the various administration pages to change the existing and new settings to your liking.</li>\n";
  print "</ol>";
  print "Notes:";
  print "<ol>";
  print " <li>If you <strong>upgrade from Drupal 4.4.x</strong>, you will need to create the <code>users_roles</code> and <code>locales_meta</code> tables manually before upgrading. To create these tables, issue the following SQL commands:

  <p>MySQL specific example:
  <pre>
  CREATE TABLE users_roles (
    uid int(10) unsigned NOT NULL default '0',
    rid int(10) unsigned NOT NULL default '0',
    PRIMARY KEY (uid, rid)
  );
  CREATE TABLE locales_meta (
    locale varchar(12) NOT NULL default '',
    name varchar(64) NOT NULL default '',
    enabled int(2) NOT NULL default '0',
    isdefault int(2) NOT NULL default '0',
    plurals int(1) NOT NULL default '0',
    formula varchar(128) NOT NULL default '',
    PRIMARY KEY  (locale)
  );
  </pre>
  </p>

  <p>PostgreSQL specific example:
  <pre>
  CREATE TABLE users_roles (
    uid integer NOT NULL default '0',
    rid integer NOT NULL default '0',
    PRIMARY KEY (uid, rid)
  );
  CREATE TABLE locales_meta (
    locale varchar(12) NOT NULL default '',
    name varchar(64) NOT NULL default '',
    enabled int4 NOT NULL default '0',
    isdefault int4 NOT NULL default '0',
    plurals int4 NOT NULL default '0',
    formula varchar(128) NOT NULL default '',
    PRIMARY KEY  (locale)
  );
  </pre>
  </p>
  </li>";
  print " <li>If you <strong>upgrade from Drupal 4.3.x</strong>, you will need to add the <code>bootstrap</code> and <code>throttle</code> fields to the <code>system</code> table manually before upgrading. To add the required fields, issue the following SQL commands:

  <p>MySQL specific example:
  <pre>
  ALTER TABLE system ADD throttle tinyint(1) NOT NULL DEFAULT '0';
  ALTER TABLE system ADD bootstrap int(2);
  </pre>
  </p>

  <p>PostgreSQL specific example:
  <pre>
  ALTER TABLE system ADD throttle smallint;
  ALTER TABLE system ALTER COLUMN throttle SET DEFAULT '0';
  UPDATE system SET throttle = 0;
  ALTER TABLE system ALTER COLUMN throttle SET NOT NULL;
  ALTER TABLE system ADD bootstrap integer;
  </pre>
  </p>
  </li>";
  print " <li>If you <strong>upgrade from Drupal 4.2.0</strong>, you will need to create the <code>sessions</code> table manually before upgrading.  After creating the table, you will want to log in and immediately continue the upgrade.  To create the <code>sessions</code> table, issue the following SQL command:

  <p>MySQL specific example:
  <pre>
  CREATE TABLE sessions (
  uid int(10) unsigned NOT NULL,
  sid varchar(32) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp int(11) NOT NULL default '0',
  session text,
  KEY uid (uid),
  KEY sid (sid(4)),
  KEY timestamp (timestamp));
  </pre>
  </p>
  </li>";
  print "</ol>";
  print update_page_footer();
}

if (isset($_GET["op"])) {
  include_once "includes/bootstrap.inc";
  include_once "includes/common.inc";

  // Protect against cross site request forgeries
  drupal_check_token();

  // Access check:
  if (($access_check == 0) || ($user->uid == 1)) {
    update_page();
  }
  else {
    print update_page_header("Access denied");
    print "<p>Access denied.  You are not authorized to access this page.  Please log in as the admin user (the first user you created). If you cannot log in, you will have to edit <code>update.php</code> to bypass this access check.  To do this:</p>";
    print "<ol>";
    print " <li>With a text editor find the update.php file on your system. It should be in the main Drupal directory that you installed all the files into.</li>";
    print " <li>There is a line near top of update.php that says <code>\$access_check = TRUE;</code>. Change it to <code>\$access_check = FALSE;</code>.</li>";
    print " <li>As soon as the script is done, you must change the update.php script back to its original form to <code>\$access_check = TRUE;</code>.</li>";
    print " <li>To avoid having this problem in future, remember to log in to your website as the admin user (the user you first created) before you backup your database at the beginning of the update process.</li>";
    print "</ol>";

    print update_page_footer();
  }
}
else {
  update_info();
}
?>
