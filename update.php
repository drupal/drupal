<?php
include_once "includes/common.inc";

/*
** USAGE:
**
** - Point your browser to "http://www.site.com/update.php" and follow
**   the instructions.
**
** NOTES:
**
** - If you upgrade from Drupal 3.00, you have to update your watchdog
**   table manually before executing/starting this script.  Simply do:
**
**   sql> ALTER TABLE watchdog CHANGE user uid int(10) DEFAULT '0' NOT NULL;
**   sql> ALTER TABLE watchdog CHANGE id wid int(5) DEFAULT '0' NOT NULL auto_increment;
**
**   You'll also have to by-pass the access check near the bottom such
**   that you can gain access to the form: search for "user_access()".
*/

if (!get_cfg_var("safe_mode")) {
  set_time_limit(180);
}

// Define the various updates in an array("date : comment" => "function");
$mysql_updates = array(
  "2001-10-10" => "update_1",
  "2001-10-12 : Pearification" => "update_2",
  "2001-10-14" => "update_3",
  "2001-10-16" => "update_4",
  "2001-10-17" => "update_5",
  "2001-10-22" => "update_6",
  "2001-11-01" => "update_7",
  "2001-11-02" => "update_8",
  "2001-11-04" => "update_9",
  "2001-11-17: distributed authentication" => "update_10",
  "2001-12-01" => "update_11",
  "2001-12-06" => "update_12",
  "2001-12-09" => "update_13",
  "2001-12-16" => "update_14",
  "2001-12-24" => "update_15",
  "2001-12-30" => "update_16",
);

// Update functions
function update_1() {
  update_sql("ALTER TABLE users RENAME AS user;");
  update_sql("ALTER TABLE user DROP INDEX real_email;");
  update_sql("ALTER TABLE user DROP fake_email;");
  update_sql("ALTER TABLE user DROP nodes;");
  update_sql("ALTER TABLE user DROP bio;");
  update_sql("ALTER TABLE user DROP hash;");
  update_sql("ALTER TABLE user ADD session varchar(32) DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE user ADD jabber varchar(128) DEFAULT '' NULL;");
  update_sql("ALTER TABLE user ADD drupal varchar(128) DEFAULT '' NULL;");
  update_sql("ALTER TABLE user ADD init varchar(64) DEFAULT '' NULL;");
  update_sql("ALTER TABLE user CHANGE passwd pass varchar(24) DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE user CHANGE real_email mail varchar(64) DEFAULT '' NULL;");
  update_sql("ALTER TABLE user CHANGE last_access timestamp int(11) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE user CHANGE last_host hostname varchar(128) DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE user CHANGE id uid int(10) unsigned DEFAULT '0' NOT NULL auto_increment;");
  update_sql("ALTER TABLE user CHANGE url homepage varchar(128) DEFAULT '' NOT NULL;");
  update_sql("UPDATE user SET status = 1 WHERE status = 2;");
  update_sql("UPDATE user SET name = userid;");
  update_sql("ALTER TABLE user DROP userid;");
  update_sql("UPDATE user SET init = mail;");
  update_sql("DROP TABLE access;");
  update_sql("CREATE TABLE access (aid tinyint(10) DEFAULT '0' NOT NULL auto_increment, mask varchar(255) DEFAULT '' NOT NULL, type varchar(255) DEFAULT '' NOT NULL, status tinyint(2) DEFAULT '0' NOT NULL, UNIQUE mask (mask), PRIMARY KEY (aid));");
  update_sql("CREATE TABLE moderate (cid int(10) DEFAULT '0' NOT NULL, nid int(10) DEFAULT '0' NOT NULL, uid int(10) DEFAULT '0' NOT NULL, score int(2) DEFAULT '0' NOT NULL, timestamp int(11) DEFAULT '0' NOT NULL, INDEX (cid), INDEX (nid) );");
  update_sql("ALTER TABLE comments DROP score;");
  update_sql("ALTER TABLE comments DROP votes;");
  update_sql("ALTER TABLE comments DROP users;");
}

function update_2() {
  update_sql("ALTER TABLE user RENAME AS users;");
  update_sql("ALTER TABLE users CHANGE pass pass varchar(32) DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE watchdog CHANGE user userid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE rating CHANGE user userid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE layout CHANGE user userid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE blocks CHANGE offset delta tinyint(2) DEFAULT '0' NOT NULL;");
  foreach (module_list() as $name) {
    if (module_hook($name, "node", "name")) {
      $output .= "$name ...";
      db_query("DROP TABLE IF EXISTS ". $name ."_seq");
      db_query("CREATE TABLE ". $name ."_seq (id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))");
      $result = db_query("SELECT MAX(". ($name == "node" ? "nid" : "lid") .") FROM $name", 1);
      $count = $result ? db_result($result, 0) : 0;
      db_query("INSERT INTO ". $name ."_seq (id) VALUES ('$count')");
      $output .= "done ($count)<br />";
    }
  }
  print $output;
}

function update_3() {
  update_sql("ALTER TABLE watchdog CHANGE id wid int(5) DEFAULT '0' NOT NULL auto_increment;");
  update_sql("ALTER TABLE locales CHANGE id lid int(10) DEFAULT '0' NOT NULL auto_increment;");
  update_sql("ALTER TABLE watchdog CHANGE userid uid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE layout CHANGE userid uid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE rating CHANGE userid uid int(10) DEFAULT '0' NOT NULL;");
}

function update_4() {
  print 'remove the "auto_increment"s\:n';
  update_sql("ALTER TABLE story CHANGE nid nid int(10) unsigned DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE blog CHANGE nid nid int(10) unsigned DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE page CHANGE nid nid int(10) unsigned DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE forum CHANGE nid nid int(10) unsigned DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE book CHANGE nid nid int(10) unsigned DEFAULT '0' NOT NULL;");

  print 'drop the "lid"s:\n';
  update_sql("ALTER TABLE story DROP lid;");
  update_sql("ALTER TABLE blog DROP lid;");
  update_sql("ALTER TABLE page DROP lid;");
  update_sql("ALTER TABLE forum DROP lid;");
  update_sql("ALTER TABLE book DROP lid;");

  print 'rename "author" to "uid":\n';
  update_sql("ALTER TABLE comments CHANGE author uid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node CHANGE author uid int(10) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node DROP KEY author;");
  update_sql("ALTER TABLE node ADD KEY uid (uid);");

  print 'resize some "id"s:\n';
  update_sql("ALTER TABLE feed CHANGE fid fid int(10) NOT NULL auto_increment;");
  update_sql("ALTER TABLE bundle CHANGE bid bid int(10) NOT NULL auto_increment;");
  update_sql("ALTER TABLE item CHANGE iid iid int(10) NOT NULL auto_increment;");
  update_sql("ALTER TABLE item CHANGE fid fid int(10) NOT NULL;");
  update_sql("ALTER TABLE comments CHANGE cid cid int(10) NOT NULL auto_increment;");
  update_sql("ALTER TABLE comments CHANGE pid pid int(10) NOT NULL;");
  update_sql("ALTER TABLE comments CHANGE lid lid int(10) NOT NULL;");
}

function update_5() {
  print 'add primary keys:\n';
  update_sql("ALTER TABLE story ADD PRIMARY KEY nid (nid);");
  update_sql("ALTER TABLE blog ADD PRIMARY KEY nid (nid);");
  update_sql("ALTER TABLE page ADD PRIMARY KEY nid (nid);");
  update_sql("ALTER TABLE forum ADD PRIMARY KEY nid (nid);");
  update_sql("ALTER TABLE book ADD PRIMARY KEY nid (nid);");

}

function update_6() {
  print 'add new field to blocks:\n';
  update_sql("ALTER TABLE blocks ADD path varchar(255) NOT NULL DEFAULT '';");
}

function update_7() {
  print "updating the story table:\n";
  update_sql("UPDATE story SET body = CONCAT(abstract, '\n\n', body)");
  update_sql("ALTER TABLE story DROP abstract");

  print 'rename the body fields:\n';
  update_sql("ALTER TABLE story CHANGE body body_old TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE page CHANGE body body_old TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE blog CHANGE body body_old TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE forum CHANGE body body_old TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE book CHANGE body body_old TEXT DEFAULT '' NOT NULL;");

  print 'update the node table:\n';
  update_sql("ALTER TABLE node DROP lid;");
  update_sql("ALTER TABLE node ADD teaser TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE node ADD body TEXT DEFAULT '' NOT NULL;");
  update_sql("ALTER TABLE node ADD changed int(11) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node CHANGE timestamp created int(11) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node CHANGE comment comment int(2) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node CHANGE promote promote int(2) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node CHANGE moderate moderate int(2) DEFAULT '0' NOT NULL;");
  update_sql("ALTER TABLE node DROP timestamp_posted;");
  update_sql("ALTER TABLE node DROP timestamp_queued;");
  update_sql("ALTER TABLE node DROP timestamp_hidden;");
  update_sql("UPDATE node SET status = 0 WHERE status = 1;");
  update_sql("UPDATE node SET status = 0 WHERE status = 2;");
  update_sql("UPDATE node SET status = 1 WHERE status = 3;");

  $result = db_query("SELECT nid FROM node");

  while ($object = db_fetch_object($result)) {

    $node = node_load(array("nid" => $object->nid));

    $body = db_result(db_query("SELECT body_old FROM $node->type WHERE nid = $node->nid"), 0);

    switch ($node->type) {
      case "forum":
      case "story":
      case "book":
      case "page":
      case "blog":
        node_save($node, array("nid", "body" => $body, "teaser" => node_teaser($body)));
        print "updated node $node->nid '$node->title' ($node->type)<br />";
        break;
      default:
        print "unknown node $node->nid '$node->title' ($node->type)<br />";
    }

    unset($node);
    unset($body);
  }

  update_sql("ALTER TABLE book DROP section;");
  update_sql("ALTER TABLE users CHANGE session sid varchar(32) DEFAULT '' NOT NULL;");
}

function update_8() {
  update_sql("ALTER TABLE node ADD revisions TEXT DEFAULT '' NOT NULL;");
}

function update_9() {
  update_sql("ALTER TABLE book ADD revision int(2) DEFAULT '1' NOT NULL;");
  update_sql("ALTER TABLE book DROP log;");
  update_sql("ALTER TABLE book DROP pid;");

  // remove book pages that used to be 'expired':
  $result = db_query("SELECT n.nid, n.title FROM node n WHERE n.type = 'book' AND n.status = 0");
  while ($node = db_fetch_object($result)) {
    print "removing node $node->nid '$node->title' (dumped node)<br />";
    db_query("DELETE FROM node WHERE nid = '$node->nid'");
    db_query("DELETE FROM book WHERE nid = '$node->nid'");
    db_query("DELETE FROM comments WHERE lid = '$node->nid'");
  }
}

function update_10() {
  // create a new table:
  update_sql("CREATE TABLE authmap (
      aid int(10) unsigned DEFAULT '0' NOT NULL auto_increment,
      uid int(10) DEFAULT '' NOT NULL,
      authname varchar(128) DEFAULT '' NOT NULL,
      module varchar(128) DEFAULT '' NOT NULL,
      UNIQUE authname (authname),
      PRIMARY KEY (aid)
    );");

  // populate the new table:
  $result = db_query("SELECT uid, name, jabber, drupal FROM users WHERE jabber != '' || drupal != ''");
  while ($user = db_fetch_object($result)) {
    if ($user->jabber) {
      update_sql("INSERT INTO authmap (uid, authname, module) VALUES ('$user->uid', '$user->jabber', 'jabber')");
    }
    if ($user->drupal) {
      update_sql("INSERT INTO authmap (uid, authname, module) VALUES ('$user->uid', '$user->drupal', 'drupal')");
    }
  }

  // remove the old user-table leftovers:
  update_sql("DELETE FROM variable WHERE name = 'user_jabber'");
  update_sql("DELETE FROM variable WHERE name = 'user_drupal'");
  update_sql("ALTER TABLE users DROP drupal");
  update_sql("ALTER TABLE users DROP jabber");

  // remove the old node-table leftovers:
  update_sql("ALTER TABLE forum DROP body_old;");
  update_sql("ALTER TABLE story DROP body_old;");
  update_sql("ALTER TABLE book DROP body_old;");
  update_sql("ALTER TABLE page DROP body_old;");
  update_sql("ALTER TABLE blog DROP body_old;");
}

function update_11() {
  update_sql("ALTER TABLE users ADD session TEXT;");
}

function update_12() {
  update_sql("ALTER TABLE book DROP revision;");
  update_sql("ALTER TABLE book ADD format tinyint(2) DEFAULT '0';");
}

function update_13() {
  update_sql("ALTER TABLE referer RENAME AS referrer;");
  update_sql("DROP TABLE blog;");
  update_sql("DROP TABLE story;");
  update_sql("DROP TABLE forum;");
}

function update_14() {
  update_sql("CREATE TABLE directory (
    link varchar(255) DEFAULT '' NOT NULL,
    name varchar(128) DEFAULT '' NOT NULL,
    mail varchar(128) DEFAULT '' NOT NULL,
    slogan text NOT NULL,
    mission text NOT NULL,
    timestamp int(11) DEFAULT '0' NOT NULL,
    PRIMARY KEY (link)
  );");
}

function update_15() {
  update_sql("ALTER TABLE feed DROP uncache;");
}

function update_16() {
  update_sql("ALTER TABLE comments CHANGE lid nid int(10) NOT NULL;");
}

// System functions
function update_sql($sql) {
  global $edit;
  print nl2br(check_output($sql)) ." ";
  $result = db_query($sql);
  if ($result) {
    print "<font color=\"green\">OK</font>\n";
    return 1;
  }
  else {
    print "<font color=\"red\">FAILED</font>\n";
    if ($edit["bail"]) {
      die("Fatal error. Bailing");
    }
    return 0;
  }
}

function update_data($start) {
  global $mysql_updates;
  $mysql_updates = array_slice($mysql_updates, ($start-- ? $start : 0));
  foreach ($mysql_updates as $date=>$func) {
    print "<b>$date</b><br />\n<pre>\n";
    $func();
    variable_set("update_start", $date);
    print "</pre>\n";
  }
}

function update_page() {
  global $op, $edit, $user, $mysql_updates;

  switch ($op) {
    case "Update":
      // make sure we have updates to run.
      if ($edit["start"] == -1) {
        print "No updates to perform.";
      }
      else {
        update_data($edit["start"]);
      }
      break;
    default:
      $start = variable_get("update_start", 0);
      $dates[] = "All";
      $i = 1;
      foreach ($mysql_updates as $date=>$sql) {
        $dates[$i++] = $date;
        if ($date == $start) {
          $selected = $i;
        }
      }
      $dates[$i] = "None";

      // make update form and output it.
      $form .= form_select("Perform updates since", "start", (isset($selected) ? $selected : -1), $dates);
      $form .= form_select("Bail on errors", "bail", 0, array("Disabled", "Enabled"), "Don't forget to backup your database before performing an update.");
      $form .= form_submit("Update");
      print form($form);
      break;
  }

}

print "<html><h1>Drupal update</h1>";
if (user_access(NULL)) {
  update_page();
}
else {
  print message_access();
}

print "</html>";

?>