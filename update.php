<?php
/*
** USAGE:
**
** - Point your browser to "http://www.site.com/update.php" and follow
**   the instructions.
**
** - If you are not logged in as administrator, you will need to modify the
**   statement below. Change the 1 into a 0 to disable the access check.
**   After finishing the upgrade, open this file and change the 0 back into
**   a 1!
*/

// Disable access checking?
$access_check = 1;

if (!get_cfg_var("safe_mode")) {
  set_time_limit(180);
}

// Define the various updates in an array("date : comment" => "function");
$mysql_updates = array(
  "2002-06-22: first update since Drupal 4.0.0 release" => "update_32",
  "2002-07-07" => "update_33",
  "2002-07-31" => "update_34",
  "2002-08-10" => "update_35",
  "2002-08-16" => "update_36",
  "2002-08-19" => "update_37",
  "2002-08-26" => "update_38",
  "2002-09-15" => "update_39",
  "2002-09-17" => "update_40",
  "2002-10-13" => "update_41",
  "2002-10-17" => "update_42",
  "2002-10-26" => "update_43",
  "2002-11-08" => "update_44",
  "2002-11-20" => "update_45",
  "2002-12-10: first update since Drupal 4.1.0 release" => "update_46",
  "2002-12-29" => "update_47",
  "2003-01-03" => "update_48",
  "2003-01-05" => "update_49",
  "2003-01-15" => "update_50",
  "2003-04-19" => "update_51",
  "2003-04-20" => "update_52",
  "2003-05-18" => "update_53",
  "2003-05-24" => "update_54",
  "2003-05-31" => "update_55",
  "2003-06-04" => "update_56",
  "2003-06-08" => "update_57",
  "2003-06-08: first update since Drupal 4.2.0 release" => "update_58",
  "2003-08-05" => "update_59",
  "2003-08-15" => "update_60",
  "2003-08-20" => "update_61",
  "2003-08-27" => "update_62",
  "2003-09-09" => "update_63",
  "2003-09-10" => "update_64",
  "2003-09-29" => "update_65",
  "2003-09-30" => "update_66",
  "2003-10-11" => "update_67",
  "2003-10-20" => "update_68",
  "2003-10-22" => "update_69",
  "2003-10-27" => "update_70"
);

function update_32() {
  update_sql("ALTER TABLE users ADD index (sid(4))");
  update_sql("ALTER TABLE users ADD index (timestamp)");
  update_sql("ALTER TABLE users ADD UNIQUE KEY name (name)");
}

function update_33() {
  $result = db_query("SELECT * FROM variable WHERE value NOT LIKE 's:%;'");
  // NOTE: the "WHERE"-part of the query above avoids variables to get serialized twice.
  while ($variable = db_fetch_object($result)) {
    variable_set($variable->name, $variable->value);
  }
}

function update_34() {
  update_sql("ALTER TABLE feed MODIFY refresh int(10) NOT NULL default '0'");
  update_sql("ALTER TABLE feed MODIFY timestamp int (10) NOT NULL default '0'");
  update_sql("ALTER TABLE users CHANGE session session TEXT");
}

function update_35() {
  update_sql("ALTER TABLE poll_choices ADD INDEX (nid)");
}

function update_36() {
  update_sql("ALTER TABLE rating CHANGE old previous int(6) NOT NULL default '0'");
  update_sql("ALTER TABLE rating CHANGE new current int(6) NOT NULL default '0'");
}

function update_37() {

  update_sql("DROP TABLE IF EXISTS sequences");

  update_sql("CREATE TABLE sequences (
    name VARCHAR(255) NOT NULL PRIMARY KEY,
    id INT UNSIGNED NOT NULL
  ) TYPE=MyISAM");

  if ($max = db_result(db_query("SELECT MAX(nid) FROM node"))) {
    update_sql("REPLACE INTO sequences VALUES ('node', $max)");
  }

  if ($max = db_result(db_query("SELECT MAX(cid) FROM comments"))) {
    update_sql("REPLACE INTO sequences VALUES ('comments', $max)");
  }
  // NOTE: move the comments bit down as soon as we switched to use the new comment module!

  if ($max = db_result(db_query("SELECT MAX(tid) FROM term_data"))) {
    update_sql("REPLACE INTO sequences VALUES ('term_data', $max)");
  }
}

function update_38() {
  update_sql("ALTER TABLE watchdog CHANGE message message text NOT NULL default ''");
}

function update_39() {
  update_sql("DROP TABLE moderate");

  update_sql("ALTER TABLE comments ADD score MEDIUMINT NOT NULL");
  update_sql("ALTER TABLE comments ADD status TINYINT UNSIGNED NOT NULL");
  update_sql("ALTER TABLE comments ADD users MEDIUMTEXT");

  update_sql("CREATE TABLE moderation_votes (
    mid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vote VARCHAR(255),
    weight TINYINT NOT NULL
  )");

  update_sql("CREATE TABLE moderation_roles (
    rid INT UNSIGNED NOT NULL,
    mid INT UNSIGNED NOT NULL,
    value TINYINT NOT NULL
  )");

  update_sql("ALTER TABLE moderation_roles ADD INDEX (rid)");
  update_sql("ALTER TABLE moderation_roles ADD INDEX (mid)");

  update_sql("CREATE TABLE moderation_filters (
    fid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    filter VARCHAR(255) NOT NULL,
    minimum SMALLINT NOT NULL
  )");

  update_sql("DELETE FROM moderation_votes");
  update_sql("INSERT INTO moderation_votes VALUES (1, '+1', 0)");
  update_sql("INSERT INTO moderation_votes VALUES (2, '-1', 1)");

  update_sql("DELETE FROM moderation_roles");
  update_sql("INSERT INTO moderation_roles VALUES (2, 1, 1)");
  update_sql("INSERT INTO moderation_roles VALUES (2, 2, -1)");

  update_sql("CREATE TABLE forum (
    nid int unsigned not null primary key,
    icon varchar(255) not null,
    shadow int unsigned not null
  )");
}

function update_40() {
  if ($max = db_result(db_query("SELECT MAX(cid) FROM comments"))) {
    update_sql("REPLACE INTO sequences VALUES ('comments', $max)");
  }
}

function update_41() {
  update_sql("CREATE TABLE statistics (
    nid int(11) NOT NULL,
    totalcount bigint UNSIGNED DEFAULT '0' NOT NULL,
    daycount mediumint UNSIGNED DEFAULT '0' NOT NULL,
    timestamp int(11) UNSIGNED DEFAULT '0' NOT NULL,
    PRIMARY KEY (nid),
    INDEX (totalcount),
    INDEX (daycount),
    INDEX (timestamp)
  )");

  update_sql("CREATE TABLE accesslog (
    nid int(11) UNSIGNED DEFAULT '0',
    url varchar(255),
    hostname varchar(128),
    uid int(10) UNSIGNED DEFAULT '0',
    timestamp int(11) UNSIGNED NOT NULL
  )");
}

function update_42() {
  update_sql("DROP TABLE modules");
  update_sql("DROP TABLE layout");
  update_sql("DROP TABLE referrer");
}

function update_43() {
  update_sql("ALTER TABLE blocks DROP remove");
  update_sql("ALTER TABLE blocks DROP name");
  update_sql("UPDATE boxes SET type = 0 WHERE type = 1");
  update_sql("UPDATE boxes SET type = 1 WHERE type = 2");
}

function update_44() {
  update_sql("UPDATE system SET filename = CONCAT('modules/', filename) WHERE type = 'module'");
}

function update_45() {
  update_sql("ALTER TABLE page ADD description varchar(128) NOT NULL default ''");
}

function update_46() {
  update_sql("ALTER TABLE cache ADD created int(11) NOT NULL default '0'");
}

function update_47() {
  if ($max = db_result(db_query("SELECT MAX(vid) FROM vocabulary"))) {
    update_sql("REPLACE INTO sequences VALUES ('vocabulary', $max)");
  }
}

function update_48() {
  update_sql("ALTER TABLE watchdog ADD link varchar(255) DEFAULT '' NULL");
}

function update_49() {
  /*
  ** Make sure the admin module is added to the system table or the
  ** admin menus won't show up.
  */

  update_sql("DELETE FROM system WHERE name = 'admin';");
  update_sql("INSERT INTO system VALUES ('modules/admin.module','admin','module','',1)");
}

function update_50() {
  update_sql("ALTER TABLE forum ADD tid INT UNSIGNED NOT NULL");
  $result = db_queryd("SELECT n.nid, t.tid FROM node n, term_node t WHERE n.nid = t.nid AND type = 'forum'");
  while ($node = db_fetch_object($result)) {
    db_queryd("UPDATE forum SET tid = %d WHERE nid = %d", $node->tid, $node->nid);
  }
  update_sql("ALTER TABLE forum ADD INDEX (tid)");
}

function update_51() {
  update_sql("ALTER TABLE blocks CHANGE delta delta varchar(32) NOT NULL default '0'");
}

function update_52() {
  update_sql("UPDATE sequences SET name = 'comments_cid' WHERE name = 'comments';");
  update_sql("UPDATE sequences SET name = 'node_nid' WHERE name = 'node';");

  update_sql("DELETE FROM sequences WHERE name = 'import'");
  update_sql("DELETE FROM sequences WHERE name = 'bundle_bid'");  // in case we would run this entry twice
  update_sql("DELETE FROM sequences WHERE name = 'feed_fid'");    // in case we would run this entry twice

  $bundles = db_result(db_query("SELECT MAX(bid) FROM bundle;"));
  update_sql("INSERT INTO sequences (name, id) VALUES ('bundle_bid', '$bundles')");

  $feeds = db_result(db_query("SELECT MAX(fid) FROM feed;"));
  update_sql("INSERT INTO sequences (name, id) VALUES ('feed_fid', '$feeds')");

  update_sql("UPDATE sequences SET name = 'vocabulary_vid' WHERE name = 'vocabulary';");

  update_sql("UPDATE sequences SET name = 'term_data_tid' WHERE name = 'term_data'");
}

function update_53() {
  update_sql("CREATE INDEX book_parent ON book(parent);");
}

function update_54() {
  update_sql("ALTER TABLE locales CHANGE string string BLOB DEFAULT '' NOT NULL");
}

function update_55() {
  update_sql("ALTER TABLE site ADD checked INT(11) NOT NULL;");
  update_sql("ALTER TABLE site CHANGE timestamp changed INT(11) NOT NULL;");
}

function update_56() {
  update_sql("ALTER TABLE vocabulary CHANGE types nodes TEXT DEFAULT '' NOT NULL");
}

function update_57() {
  update_sql("DELETE FROM variable WHERE name = 'site_charset'");
}

function update_58() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("ALTER TABLE {node} ADD path varchar(250) NULL");
    update_sql("ALTER TABLE {node} ALTER COLUMN path SET DEFAULT ''");
  }
  else {
    update_sql("ALTER TABLE {node} ADD path varchar(250) NULL default ''");
  }
}

function update_59() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("ALTER TABLE {comments} ADD thread VARCHAR(255)");
    update_sql("ALTER TABLE {comments} ALTER COLUMN thread SET NOT NULL");
  }
  else {
    update_sql("ALTER TABLE {comments} ADD thread VARCHAR(255) NOT NULL");
  }

  $result = db_query("SELECT DISTINCT(nid) FROM {comments} WHERE thread = ''");

  while ($node = db_fetch_object($result)) {
    $result2 = db_query("SELECT cid, pid FROM {comments} where nid = '%d' ORDER BY timestamp", $node->nid);
    $comments = array();
    while ($comment = db_fetch_object($result2)) {
      $comments[$comment->cid] = $comment;
    }

    $structure = array();
    $structure = _update_thread_structure($comments, 0, -1, $structure);

    foreach ($structure as $cid => $thread) {
      $new_parts = array();
      foreach(explode(".", $thread) as $part) {
        if ($part > 9) {
          $start = substr($part, 0, strlen($part) - 1);
          $end = substr($part, -1, 1);

          $new_parts[] = str_repeat("9", $start).$end;
        }
        else {
          $new_parts[] = $part;
        }
      }
      $thread = implode(".", $new_parts);

      db_query("UPDATE {comments} SET thread = '%s' WHERE cid = '%d'", $thread."/", $comments[$cid]->cid);
    }
  }
}

function _update_thread_structure($comments, $pid, $depth, $structure) {
  $depth++;

  foreach ($comments as $key => $comment) {
    if ($comment->pid == $pid) {
      if ($structure[$comment->pid]) {
        $structure[$comment->cid] = $structure[$comment->pid]."."._update_next_thread($structure, $structure[$comment->pid]);
      }
      else {
        $structure[$comment->cid] = _update_next_thread($structure, "");
      }

      $structure = _update_thread_structure($comments, $comment->cid, $depth, $structure);
    }
  }

  return $structure;
}

function update_60() {
  update_sql("ALTER TABLE {forum} DROP icon");
}

function update_61() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("CREATE TABLE {sessions} (
      uid integer NOT NULL,
      sid varchar(32) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp integer NOT NULL default '0',
      session text,
      PRIMARY KEY (sid)
     );");

    update_sql("ALTER TABLE {users} DROP session;");
    update_sql("ALTER TABLE {users} DROP hostname;");
    update_sql("ALTER TABLE {users} DROP sid;");

  }
  else {
    update_sql("CREATE TABLE IF NOT EXISTS {sessions} (
      uid int(10) unsigned NOT NULL,
      sid varchar(32) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp int(11) NOT NULL default '0',
      session text,
      KEY uid (uid),
      KEY sid (sid(4)),
      KEY timestamp (timestamp)
    )");

    update_sql("ALTER TABLE {users} DROP session;");
    update_sql("ALTER TABLE {users} DROP hostname;");
    update_sql("ALTER TABLE {users} DROP sid;");
  }
}

function update_62() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("CREATE INDEX accesslog_timestamp ON {accesslog} (timestamp)");

    update_sql("DROP INDEX node_type_idx");
    update_sql("DROP INDEX node_title_idx");
    update_sql("DROP INDEX node_promote_idx");

    update_sql("CREATE INDEX node_type ON {node} (type)");
    update_sql("CREATE INDEX node_title_type ON {node} (title,type)");
    update_sql("CREATE INDEX node_moderate ON {node} (moderate)");
    update_sql("CREATE INDEX node_path ON {node} (path)");
    update_sql("CREATE INDEX node_promote_status ON {node} (promote, status)");

  }
  else {
    update_sql("ALTER TABLE {accesslog} ADD INDEX accesslog_timestamp (timestamp)");

    update_sql("ALTER TABLE {node} DROP INDEX type");
    update_sql("ALTER TABLE {node} DROP INDEX title");
    update_sql("ALTER TABLE {node} DROP INDEX promote");

    update_sql("ALTER TABLE {node} ADD INDEX node_type (type(4))");
    update_sql("ALTER TABLE {node} ADD INDEX node_title_type (title,type(4))");
    update_sql("ALTER TABLE {node} ADD INDEX node_moderate (moderate)");
    update_sql("ALTER TABLE {node} ADD INDEX node_path (path(5))");
    update_sql("ALTER TABLE {node} ADD INDEX node_promote_status (promote, status)");
  }
}

function _update_next_thread($structure, $parent) {
  do {
    $val++;
    if ($parent) {
      $thread = "$parent.$val";
    }
    else {
      $thread = $val;
    }

  } while (array_search($thread, $structure));

  return $val;
}

function update_63() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("INSERT INTO {users} (uid, name, mail, timestamp) VALUES ('0', '', '', '". time() ."')");
  }
  else {
    update_sql("ALTER TABLE {users} CHANGE uid uid int(10) unsigned NOT NULL default '0'");
    update_sql("INSERT INTO {users} (uid, name, mail, timestamp) VALUES ('0', '', '', '". time() ."')");
    $users = db_result(db_query("SELECT MAX(uid) FROM {users};"));
    update_sql("INSERT INTO {sequences} (name, id) VALUES ('users_uid', '$users')");
  }
}

function update_64() {
  update_sql("UPDATE {users} SET rid = 1 WHERE uid = 0");
}

function update_65() {
  // PostgreSQL-only update.
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("CREATE FUNCTION \"rand\"() RETURNS float AS '
      BEGIN
        RETURN random();
      END;' LANGUAGE 'plpgsql'");
  }
}

function update_66() {
  if ($GLOBALS["db_type"] == "pgsql") {
    update_sql("CREATE TABLE {path} (
      pid serial,
      src varchar(128) NOT NULL default '',
      dst varchar(128) NOT NULL default '',
      PRIMARY KEY  (pid)
    )");
    update_sql("CREATE INDEX path_src_idx ON {path}(src)");
    update_sql("CREATE INDEX path_dst_idx ON {path}(dst)");
    $result = db_query("SELECT nid, path FROM {node} WHERE path != ''");
    while ($node = db_fetch_object($result)) {
      update_sql("INSERT INTO {path} (src, dst) VALUES ('node/view/$node->nid', '". check_query($node->path) ."')");
    }
  }
  else {
    update_sql("CREATE TABLE {path} (
      pid int(10) unsigned NOT NULL auto_increment,
      src varchar(128) NOT NULL default '',
      dst varchar(128) NOT NULL default '',
      PRIMARY KEY  (pid),
      UNIQUE KEY src (src),
      UNIQUE KEY dst (dst)
    )");
    // Migrate the existing paths:
    $result = db_query("SELECT nid, path FROM {node} WHERE path != ''");
    while ($node = db_fetch_object($result)) {
      update_sql("INSERT INTO {path} (src, dst) VALUES ('node/view/$node->nid', '". check_query($node->path) ."')");
    }

    update_sql("ALTER TABLE {node} DROP path");
  }
}

function update_67() {
  if ($GLOBALS["db_type"] == "pgsql") {
    // Taking no action.  PostgreSQL is not always capable of dropping columns.
  }
  else {
    update_sql("ALTER TABLE users DROP homepage");
  }
}

function update_68() {
  if ($GLOBALS["db_type"] == "pgsql") {
    // Unneccesary. The PostgreSQL port was already using a sequence.
  }
  else {
    $max = db_result(db_query("SELECT MAX(aid) FROM {access};"));
    update_sql("INSERT INTO {sequences} (name, id) VALUES ('access_aid', '$max')");
    update_sql("ALTER TABLE access CHANGE aid aid tinyint(10) NOT NULL ");
  }
}

function update_69() {
  if ($GLOBALS["db_type"] == "pgsql") {
    /* Rename the statistics table to node_counter */
    update_sql("ALTER TABLE {statistics} RENAME TO {node_counter}");
    update_sql("DROP INDEX  {statistics}_totalcount_idx");
    update_sql("DROP INDEX  {statistics}_daycount_idx");
    update_sql("DROP INDEX  {statistics}_timestamp_idx");
    update_sql("CREATE INDEX {node_counter}_totalcount_idx ON {node_counter}(totalcount)");
    update_sql("CREATE INDEX {node_counter}_daycount_idx ON {node_counter}(daycount)");
    update_sql("CREATE INDEX {node_counter}_timestamp_idx ON {node_counter}(timestamp)");

    /* Rename the path table to url_alias */
    update_sql("ALTER TABLE {path} RENAME TO {url_alias}");
    update_sql("ALTER TABLE {path}_pid_seq RENAME TO {url_alias}_pid_seq");
  }
  else {
    update_sql("ALTER TABLE {statistics} RENAME TO {node_counter}");
    update_sql("ALTER TABLE {path} RENAME TO {url_alias}");
    update_sql("UPDATE {sequences} SET name = '{url_alias}_pid' WHERE name = '{path}_pid'");
  }

  update_sql("UPDATE {users} SET name = '' WHERE uid = 0;");
}

function update_70() {
  update_sql("ALTER TABLE {variable} CHANGE name name varchar(48) NOT NULL");
}

/*
** System functions
*/

function update_sql($sql) {
  $edit = $_POST["edit"];
  print nl2br(htmlentities($sql)) ." ";
  $result = db_query($sql);
  if ($result) {
    print "<div style=\"color: green;\">OK</div>\n";
    return 1;
  }
  else {
    print "<div style=\"color: red;\">FAILED</div>\n";
    return 0;
  }
}

function update_data($start) {
  global $mysql_updates;
  $mysql_updates = array_slice($mysql_updates, ($start-- ? $start : 0));
  foreach ($mysql_updates as $date => $func) {
    print "<b>$date</b><br />\n<pre>\n";
    $func();
    variable_set("update_start", $date);
    print "</pre>\n";
  }
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
  $output .= "<div id=\"logo\"><a href=\"http://drupal.org/\"><img src=\"misc/druplicon-small.gif\" alt=\"Druplicon - Drupal logo\" title=\"Druplicon - Drupal logo\" /></a></div>";
  $output .= "<div id=\"update\"><h1>$title</h1>";
  return $output;
}

function update_page_footer() {
  return "</div></body></html>";
}

function update_page() {
  global $user, $mysql_updates;

  $edit = $_POST["edit"];

  switch ($_POST["op"]) {
    case "Update":
      // make sure we have updates to run.
      print update_page_header("Drupal database update");
      print "<b>&raquo; <a href=\"index.php\">main page</a></b><br />\n";
      print "<b>&raquo; <a href=\"index.php?q=admin\">administration pages</a></b><br />\n";
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
      $start = variable_get("update_start", 0);
      $dates[] = "All";
      $i = 1;
      foreach ($mysql_updates as $date => $sql) {
        $dates[$i++] = $date;
        if ($date == $start) {
          $selected = $i;
        }
      }
      $dates[$i] = "No updates available";

      // make update form and output it.
      $form .= form_select("Perform updates from", "start", (isset($selected) ? $selected : -1), $dates, "This defaults to the first available update since the last update you peformed.");
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
  print "<li>Use this script to <b>upgrade an existing Drupal installation</b>.  You don't need this script when installing Drupal from scratch.</li>";
  print "<li>Before doing anything, backup your database. This process will change your database and its values, and some things might get lost.</li>\n";
  print "<li>Update your Drupal sources, check the notes below and <a href=\"update.php?op=update\">run the database upgrade script</a>.  Don't upgrade your database twice as it may cause problems.</p></li>\n";
  print "<li>Go through the various administration pages to change the existing and new settings to your liking.</li>\n";
  print "</ol>";
  print "Notes:";
  print "<ol>";
  print " <li>If you upgrade from Drupal 4.2.0, you have to create the <code>sessions</code> table manually before upgrading.  After you created the table, you'll want to log in and immediately continue the upgrade.  To create the <code>sessions</code> table, issue the following SQL command (MySQL specific example):<pre>CREATE TABLE sessions (
  uid int(10) unsigned NOT NULL,
  sid varchar(32) NOT NULL default '',
  hostname varchar(128) NOT NULL default '',
  timestamp int(11) NOT NULL default '0',
  session text,
  KEY uid (uid),
  KEY sid (sid(4)),
  KEY timestamp (timestamp));</pre></li>";
  print "</ol>";
  print update_page_footer();
}

if (isset($_GET["op"])) {
  include_once "includes/common.inc";

  // Access check:
  if (($access_check == 0) || ($user->uid == 1)) {
    update_page();
  }
  else {
    print update_page_header("Access denied");
    print "Access denied.  You are not authorized to access to this page.  Please log in as the user with user ID #1. If you cannot log-in, you will have to edit <code>update.php</code> to by-pass this access check; in that case, open <code>update.php</code> in a text editor and follow the instructions at the top.";
    print update_page_footer();
  }
}
else {
  update_info();
}
?>
