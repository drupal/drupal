<?php

// Define the various updates in an array("date : comment" => "function");
$sql_updates = array(
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
  "2003-10-27" => "update_70",
  "2003-11-17: first update since Drupal 4.3.0 release" => "update_71",
  "2003-11-27" => "update_72",
  "2003-12-03" => "update_73",
  "2003-12-06" => "update_74",
  "2004-01-06" => "update_75",
  "2004-01-11" => "update_76",
  "2004-01-13" => "update_77",
  "2004-02-03" => "update_78",
  "2004-02-21" => "update_79"
);

function update_32() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE users ADD index (sid(4))");
  $ret[] = update_sql("ALTER TABLE users ADD index (timestamp)");
  $ret[] = update_sql("ALTER TABLE users ADD UNIQUE KEY name (name)");
  return $ret;
}

function update_33() {
  $ret = array();
  $result = db_query("SELECT * FROM variable WHERE value NOT LIKE 's:%;'");
  // NOTE: the "WHERE"-part of the query above avoids variables to get serialized twice.
  while ($variable = db_fetch_object($result)) {
    variable_set($variable->name, $variable->value);
  }
  return $ret;
}

function update_34() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE feed MODIFY refresh int(10) NOT NULL default '0'");
  $ret[] = update_sql("ALTER TABLE feed MODIFY timestamp int (10) NOT NULL default '0'");
  $ret[] = update_sql("ALTER TABLE users CHANGE session session TEXT");
  return $ret;
}

function update_35() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE poll_choices ADD INDEX (nid)");
  return $ret;
}

function update_36() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE rating CHANGE old previous int(6) NOT NULL default '0'");
  $ret[] = update_sql("ALTER TABLE rating CHANGE new current int(6) NOT NULL default '0'");
  return $ret;
}

function update_37() {
  $ret = array();

  $ret[] = update_sql("DROP TABLE IF EXISTS sequences");

  $ret[] = update_sql("CREATE TABLE sequences (
    name VARCHAR(255) NOT NULL PRIMARY KEY,
    id INT UNSIGNED NOT NULL
  ) TYPE=MyISAM");

  if ($max = db_result(db_query("SELECT MAX(nid) FROM node"))) {
    $ret[] = update_sql("REPLACE INTO sequences VALUES ('node', $max)");
  }

  if ($max = db_result(db_query("SELECT MAX(cid) FROM comments"))) {
    $ret[] = update_sql("REPLACE INTO sequences VALUES ('comments', $max)");
  }
  // NOTE: move the comments bit down as soon as we switched to use the new comment module!

  if ($max = db_result(db_query("SELECT MAX(tid) FROM term_data"))) {
    $ret[] = update_sql("REPLACE INTO sequences VALUES ('term_data', $max)");
  }
  return $ret;
}

function update_38() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE watchdog CHANGE message message text NOT NULL default ''");
  return $ret;
}

function update_39() {
  $ret = array();
  $ret[] = update_sql("DROP TABLE moderate");

  $ret[] = update_sql("ALTER TABLE comments ADD score MEDIUMINT NOT NULL");
  $ret[] = update_sql("ALTER TABLE comments ADD status TINYINT UNSIGNED NOT NULL");
  $ret[] = update_sql("ALTER TABLE comments ADD users MEDIUMTEXT");

  $ret[] = update_sql("CREATE TABLE moderation_votes (
    mid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vote VARCHAR(255),
    weight TINYINT NOT NULL
  )");

  $ret[] = update_sql("CREATE TABLE moderation_roles (
    rid INT UNSIGNED NOT NULL,
    mid INT UNSIGNED NOT NULL,
    value TINYINT NOT NULL
  )");

  $ret[] = update_sql("ALTER TABLE moderation_roles ADD INDEX (rid)");
  $ret[] = update_sql("ALTER TABLE moderation_roles ADD INDEX (mid)");

  $ret[] = update_sql("CREATE TABLE moderation_filters (
    fid INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    filter VARCHAR(255) NOT NULL,
    minimum SMALLINT NOT NULL
  )");

  $ret[] = update_sql("DELETE FROM moderation_votes");
  $ret[] = update_sql("INSERT INTO moderation_votes VALUES (1, '+1', 0)");
  $ret[] = update_sql("INSERT INTO moderation_votes VALUES (2, '-1', 1)");

  $ret[] = update_sql("DELETE FROM moderation_roles");
  $ret[] = update_sql("INSERT INTO moderation_roles VALUES (2, 1, 1)");
  $ret[] = update_sql("INSERT INTO moderation_roles VALUES (2, 2, -1)");

  $ret[] = update_sql("CREATE TABLE forum (
    nid int unsigned not null primary key,
    icon varchar(255) not null,
    shadow int unsigned not null
  )");
  return $ret;
}

function update_40() {
  $ret = array();
  if ($max = db_result(db_query("SELECT MAX(cid) FROM comments"))) {
    $ret[] = update_sql("REPLACE INTO sequences VALUES ('comments', $max)");
  }
  return $ret;
}

function update_41() {
  $ret = array();
  $ret[] = update_sql("CREATE TABLE statistics (
    nid int(11) NOT NULL,
    totalcount bigint UNSIGNED DEFAULT '0' NOT NULL,
    daycount mediumint UNSIGNED DEFAULT '0' NOT NULL,
    timestamp int(11) UNSIGNED DEFAULT '0' NOT NULL,
    PRIMARY KEY (nid),
    INDEX (totalcount),
    INDEX (daycount),
    INDEX (timestamp)
  )");

  $ret[] = update_sql("CREATE TABLE accesslog (
    nid int(11) UNSIGNED DEFAULT '0',
    url varchar(255),
    hostname varchar(128),
    uid int(10) UNSIGNED DEFAULT '0',
    timestamp int(11) UNSIGNED NOT NULL
  )");
  return $ret;
}

function update_42() {
  $ret = array();
  $ret[] = update_sql("DROP TABLE modules");
  $ret[] = update_sql("DROP TABLE layout");
  $ret[] = update_sql("DROP TABLE referrer");
  return $ret;
}

function update_43() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE blocks DROP remove");
  $ret[] = update_sql("ALTER TABLE blocks DROP name");
  $ret[] = update_sql("UPDATE boxes SET type = 0 WHERE type = 1");
  $ret[] = update_sql("UPDATE boxes SET type = 1 WHERE type = 2");
  return $ret;
}

function update_44() {
  $ret = array();
  $ret[] = update_sql("UPDATE system SET filename = CONCAT('modules/', filename) WHERE type = 'module'");
  return $ret;
}

function update_45() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE page ADD description varchar(128) NOT NULL default ''");
  return $ret;
}

function update_46() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE cache ADD created integer");
  }
  else {
    $ret[] = update_sql("ALTER TABLE cache ADD created int(11) NOT NULL default '0'");
  }
  return $ret;
}

function update_47() {
  $ret = array();
  if ($max = db_result(db_query("SELECT MAX(vid) FROM vocabulary"))) {
    $ret[] = update_sql("REPLACE INTO sequences VALUES ('vocabulary', $max)");
  }
  return $ret;
}

function update_48() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE watchdog ADD link varchar(255) DEFAULT '' NULL");
  return $ret;
}

function update_49() {
  $ret = array();
  /*
  ** Make sure the admin module is added to the system table or the
  ** admin menus won't show up.
  */

  $ret[] = update_sql("DELETE FROM system WHERE name = 'admin';");
  $ret[] = update_sql("INSERT INTO system VALUES ('modules/admin.module','admin','module','',1)");
  return $ret;
}

function update_50() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE forum ADD tid INT UNSIGNED NOT NULL");
  $result = db_queryd("SELECT n.nid, t.tid FROM node n, term_node t WHERE n.nid = t.nid AND type = 'forum'");
  while ($node = db_fetch_object($result)) {
    db_queryd("UPDATE forum SET tid = %d WHERE nid = %d", $node->tid, $node->nid);
  }
  $ret[] = update_sql("ALTER TABLE forum ADD INDEX (tid)");
  return $ret;
}

function update_51() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE blocks CHANGE delta delta varchar(32) NOT NULL default '0'");
  return $ret;
}

function update_52() {
  $ret = array();
  $ret[] = update_sql("UPDATE sequences SET name = 'comments_cid' WHERE name = 'comments';");
  $ret[] = update_sql("UPDATE sequences SET name = 'node_nid' WHERE name = 'node';");

  $ret[] = update_sql("DELETE FROM sequences WHERE name = 'import'");
  $ret[] = update_sql("DELETE FROM sequences WHERE name = 'bundle_bid'");  // in case we would run this entry twice
  $ret[] = update_sql("DELETE FROM sequences WHERE name = 'feed_fid'");    // in case we would run this entry twice

  $bundles = db_result(db_query("SELECT MAX(bid) FROM bundle;"));
  $ret[] = update_sql("INSERT INTO sequences (name, id) VALUES ('bundle_bid', '$bundles')");

  $feeds = db_result(db_query("SELECT MAX(fid) FROM feed;"));
  $ret[] = update_sql("INSERT INTO sequences (name, id) VALUES ('feed_fid', '$feeds')");

  $ret[] = update_sql("UPDATE sequences SET name = 'vocabulary_vid' WHERE name = 'vocabulary';");

  $ret[] = update_sql("UPDATE sequences SET name = 'term_data_tid' WHERE name = 'term_data'");
  return $ret;
}

function update_53() {
  $ret = array();
  $ret[] = update_sql("CREATE INDEX book_parent ON book(parent);");
  return $ret;
}

function update_54() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE locales CHANGE string string BLOB DEFAULT '' NOT NULL");
  return $ret;
}

function update_55() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE site ADD checked INT(11) NOT NULL;");
  $ret[] = update_sql("ALTER TABLE site CHANGE timestamp changed INT(11) NOT NULL;");
  return $ret;
}

function update_56() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE vocabulary CHANGE types nodes TEXT DEFAULT '' NOT NULL");
  return $ret;
}

function update_57() {
  $ret = array();
  $ret[] = update_sql("DELETE FROM variable WHERE name = 'site_charset'");
  return $ret;
}

function update_58() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {node} ADD path varchar(250) NULL");
    $ret[] = update_sql("ALTER TABLE {node} ALTER COLUMN path SET DEFAULT ''");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {node} ADD path varchar(250) NULL default ''");
  }
  return $ret;
}

function update_59() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {comments} ADD thread VARCHAR(255)");
    $ret[] = update_sql("ALTER TABLE {comments} ALTER COLUMN thread SET NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {comments} ADD thread VARCHAR(255) NOT NULL");
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
  return $ret;
}

function _update_thread_structure($comments, $pid, $depth, $structure) {
  $ret = array();
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
  return $ret;
}

function update_60() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {forum} DROP icon");
  return $ret;
}

function update_61() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("CREATE TABLE {sessions} (
      uid integer NOT NULL,
      sid varchar(32) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp integer NOT NULL default '0',
      session text,
      PRIMARY KEY (sid)
     );");

    $ret[] = update_sql("ALTER TABLE {users} DROP session;");
    $ret[] = update_sql("ALTER TABLE {users} DROP hostname;");
    $ret[] = update_sql("ALTER TABLE {users} DROP sid;");

  }
  else {
    $ret[] = update_sql("CREATE TABLE IF NOT EXISTS {sessions} (
      uid int(10) unsigned NOT NULL,
      sid varchar(32) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp int(11) NOT NULL default '0',
      session text,
      KEY uid (uid),
      KEY sid (sid(4)),
      KEY timestamp (timestamp)
    )");

    $ret[] = update_sql("ALTER TABLE {users} DROP session;");
    $ret[] = update_sql("ALTER TABLE {users} DROP hostname;");
    $ret[] = update_sql("ALTER TABLE {users} DROP sid;");
  }
  return $ret;
}

function update_62() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("CREATE INDEX accesslog_timestamp ON {accesslog} (timestamp)");

    $ret[] = update_sql("DROP INDEX node_type_idx");
    $ret[] = update_sql("DROP INDEX node_title_idx");
    $ret[] = update_sql("DROP INDEX node_promote_idx");

    $ret[] = update_sql("CREATE INDEX node_type ON {node} (type)");
    $ret[] = update_sql("CREATE INDEX node_title_type ON {node} (title,type)");
    $ret[] = update_sql("CREATE INDEX node_moderate ON {node} (moderate)");
    $ret[] = update_sql("CREATE INDEX node_path ON {node} (path)");
    $ret[] = update_sql("CREATE INDEX node_promote_status ON {node} (promote, status)");

  }
  else {
    $ret[] = update_sql("ALTER TABLE {accesslog} ADD INDEX accesslog_timestamp (timestamp)");

    $ret[] = update_sql("ALTER TABLE {node} DROP INDEX type");
    $ret[] = update_sql("ALTER TABLE {node} DROP INDEX title");
    $ret[] = update_sql("ALTER TABLE {node} DROP INDEX promote");

    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_type (type(4))");
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_title_type (title,type(4))");
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_moderate (moderate)");
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_path (path(5))");
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_promote_status (promote, status)");
  }
  return $ret;
}

function _update_next_thread($structure, $parent) {
  $ret = array();
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
  return $ret;
}

function update_63() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("INSERT INTO {users} (uid, name, mail, timestamp) VALUES ('0', '', '', '". time() ."')");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {users} CHANGE uid uid int(10) unsigned NOT NULL default '0'");
    $ret[] = update_sql("INSERT INTO {users} (uid, name, mail, timestamp) VALUES ('0', '', '', '". time() ."')");
    $users = db_result(db_query("SELECT MAX(uid) FROM {users};"));
    $ret[] = update_sql("INSERT INTO {sequences} (name, id) VALUES ('users_uid', '$users')");
  }
  return $ret;
}

function update_64() {
  $ret = array();
  $ret[] = update_sql("UPDATE {users} SET rid = 1 WHERE uid = 0");
  return $ret;
}

function update_65() {
  $ret = array();
  // PostgreSQL-only update.
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("CREATE FUNCTION \"rand\"() RETURNS float AS '
      BEGIN
        RETURN random();
      END;' LANGUAGE 'plpgsql'");
  }
  return $ret;
}

function update_66() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("CREATE TABLE {path} (
      pid serial,
      src varchar(128) NOT NULL default '',
      dst varchar(128) NOT NULL default '',
      PRIMARY KEY  (pid)
    )");
    $ret[] = update_sql("CREATE INDEX path_src_idx ON {path}(src)");
    $ret[] = update_sql("CREATE INDEX path_dst_idx ON {path}(dst)");
    $result = db_query("SELECT nid, path FROM {node} WHERE path != ''");
    while ($node = db_fetch_object($result)) {
      $ret[] = update_sql("INSERT INTO {path} (src, dst) VALUES ('node/view/$node->nid', '". check_query($node->path) ."')");
    }
  }
  else {
    $ret[] = update_sql("CREATE TABLE {path} (
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
      $ret[] = update_sql("INSERT INTO {path} (src, dst) VALUES ('node/view/$node->nid', '". check_query($node->path) ."')");
    }

    $ret[] = update_sql("ALTER TABLE {node} DROP path");
  }
  return $ret;
}

function update_67() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    // Taking no action.  PostgreSQL is not always capable of dropping columns.
  }
  else {
    $ret[] = update_sql("ALTER TABLE {users} DROP homepage");
  }
  return $ret;
}

function update_68() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    // Unneccesary. The PostgreSQL port was already using a sequence.
  }
  else {
    $max = db_result(db_query("SELECT MAX(aid) FROM {access};"));
    $ret[] = update_sql("INSERT INTO {sequences} (name, id) VALUES ('access_aid', '$max')");
    $ret[] = update_sql("ALTER TABLE {access} CHANGE aid aid tinyint(10) NOT NULL ");
  }
  return $ret;
}

function update_69() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    /* Rename the statistics table to node_counter */
    $ret[] = update_sql("ALTER TABLE {statistics} RENAME TO {node_counter}");
    $ret[] = update_sql("DROP INDEX  {statistics}_totalcount_idx");
    $ret[] = update_sql("DROP INDEX  {statistics}_daycount_idx");
    $ret[] = update_sql("DROP INDEX  {statistics}_timestamp_idx");
    $ret[] = update_sql("CREATE INDEX {node_counter}_totalcount_idx ON {node_counter}(totalcount)");
    $ret[] = update_sql("CREATE INDEX {node_counter}_daycount_idx ON {node_counter}(daycount)");
    $ret[] = update_sql("CREATE INDEX {node_counter}_timestamp_idx ON {node_counter}(timestamp)");

    /* Rename the path table to url_alias */
    $ret[] = update_sql("ALTER TABLE {path} RENAME TO {url_alias}");
    $ret[] = update_sql("ALTER TABLE {path}_pid_seq RENAME TO {url_alias}_pid_seq");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {statistics} RENAME TO {node_counter}");
    $ret[] = update_sql("ALTER TABLE {path} RENAME TO {url_alias}");
    $ret[] = update_sql("UPDATE {sequences} SET name = '{url_alias}_pid' WHERE name = '{path}_pid'");
  }

  $ret[] = update_sql("UPDATE {users} SET name = '' WHERE uid = 0;");
  return $ret;
}

function update_70() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {variable} CHANGE name name varchar(48) NOT NULL");
  return $ret;
}

function update_71() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {system} ADD bootstrap integer");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {system} ADD bootstrap int(2)");
  }
  return $ret;
}

function update_72() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {blocks} ADD throttle smallint");
    $ret[] = update_sql("ALTER TABLE {blocks} ALTER COLUMN throttle SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {blocks} SET throttle = 0");
    $ret[] = update_sql("ALTER TABLE {blocks} ALTER COLUMN throttle SET NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {blocks} ADD throttle tinyint(1) NOT NULL DEFAULT '0'");
  }
  return $ret;
}

function update_73() {
  $ret = array();
  /* MySQL only update */
  if ($GLOBALS["db_type"] == "mysql") {
    $ret[] = update_sql("ALTER TABLE {book} CHANGE log log longtext");
    $ret[] = update_sql("ALTER TABLE {boxes} CHANGE body body longtext");
    $ret[] = update_sql("ALTER TABLE {cache} CHANGE data data longtext");
    $ret[] = update_sql("ALTER TABLE {comments} CHANGE comment comment longtext");
    $ret[] = update_sql("ALTER TABLE {comments} CHANGE users users longtext");
    $ret[] = update_sql("ALTER TABLE {directory} CHANGE slogan slogan longtext");
    $ret[] = update_sql("ALTER TABLE {directory} CHANGE mission mission longtext");
    $ret[] = update_sql("ALTER TABLE {feed} CHANGE description description longtext");
    $ret[] = update_sql("ALTER TABLE {item} CHANGE description description longtext");
    $ret[] = update_sql("ALTER TABLE {node} CHANGE users users longtext");
    $ret[] = update_sql("ALTER TABLE {node} CHANGE teaser teaser longtext");
    $ret[] = update_sql("ALTER TABLE {node} CHANGE body body longtext");
    $ret[] = update_sql("ALTER TABLE {node} CHANGE revisions revisions longtext");
    $ret[] = update_sql("ALTER TABLE {permission} CHANGE perm perm longtext");
    $ret[] = update_sql("ALTER TABLE {poll} CHANGE voters voters longtext");
    $ret[] = update_sql("ALTER TABLE {sessions} CHANGE session session longtext");
    $ret[] = update_sql("ALTER TABLE {term_data} CHANGE description description longtext");
    $ret[] = update_sql("ALTER TABLE {users} CHANGE data data longtext");
    $ret[] = update_sql("ALTER TABLE {variable} CHANGE value value longtext");
    $ret[] = update_sql("ALTER TABLE {vocabulary} CHANGE description description longtext");
    $ret[] = update_sql("ALTER TABLE {vocabulary} CHANGE nodes nodes longtext");
    $ret[] = update_sql("ALTER TABLE {watchdog} CHANGE message message longtext");
  }
  return $ret;
}

function update_74() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {system} ADD throttle smallint");
    $ret[] = update_sql("ALTER TABLE {system} ALTER COLUMN throttle SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {system} SET throttle = 0");
    $ret[] = update_sql("ALTER TABLE {system} ALTER COLUMN throttle SET NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {system} ADD throttle tinyint(1) NOT NULL DEFAULT '0'");
  }
  return $ret;
}

function update_75() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {feed} ADD etag text");
    $ret[] = update_sql("ALTER TABLE {feed} ALTER COLUMN etag SET DEFAULT ''");
    $ret[] = update_sql("ALTER TABLE {feed} ALTER COLUMN etag SET NOT NULL");

    $ret[] = update_sql("ALTER TABLE {feed} ADD modified integer");
    $ret[] = update_sql("ALTER TABLE {feed} ALTER COLUMN modified SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {feed} SET modified = 0");
    $ret[] = update_sql("ALTER TABLE {feed} ALTER COLUMN modified SET NOT NULL");

    $ret[] = update_sql("ALTER TABLE {feed} RENAME timestamp TO checked");

    $ret[] = update_sql("UPDATE {blocks} SET module = 'aggregator' WHERE module = 'import'");
    $ret[] = update_sql("UPDATE {system} SET filename = 'modules/aggregator.module', name = 'aggregator' WHERE filename = 'modules/import.module'");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {feed} ADD etag varchar(255) NOT NULL DEFAULT ''");
    $ret[] = update_sql("ALTER TABLE {feed} ADD modified int(10) NOT NULL DEFAULT 0");
    $ret[] = update_sql("ALTER TABLE {feed} CHANGE timestamp checked int(10) NOT NULL DEFAULT 0");
    $ret[] = update_sql("UPDATE {blocks} SET module = 'aggregator' WHERE module = 'import'");
    $ret[] = update_sql("UPDATE {system} SET filename = 'modules/aggregator.module', name = 'aggregator' WHERE filename = 'modules/import.module'");
  }
  return $ret;
}

function update_76() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    $ret[] = update_sql("ALTER TABLE {feed} ADD image text");
  } else {
    $ret[] = update_sql("ALTER TABLE {feed} ADD image longtext");
  }
  return $ret;
}

function update_77() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {cache} ADD headers text");
  return $ret;
}

function update_78() {
  $ret = array();
  if ($GLOBALS["db_type"] == "mysql") {
    $ret[] = update_sql("CREATE TABLE {filters} (
      module varchar(64) NOT NULL default '',
      weight tinyint(2) DEFAULT '0' NOT NULL,
      KEY weight (weight)
    )");
  }
  else {
    $ret[] = update_sql("CREATE TABLE {filters} (
      module varchar(64) NOT NULL DEFAULT '',
      weight smallint NOT NULL DEFAULT 0
      );");
    $ret[] = update_sql("CREATE INDEX {filters_weight} ON {filters} (weight);");
  }
  return $ret;
}

function update_79() {
  $ret = array();
  // Works for both mysql and postgresql
  $ret[] = update_sql("ALTER TABLE {node} DROP attributes");
  $ret[] = update_sql("ALTER TABLE {comments} DROP link");
  return $ret;
}

function update_sql($sql) {
  $edit = $_POST["edit"];
  $result = db_query($sql);
  if ($result) {
    return array('1', nl2br(htmlentities($sql)) ." ", "<div style=\"color: green;\">OK</div>\n");
  }
  else {
    return array('0', nl2br(htmlentities($sql)) ." ", "<div style=\"color: red;\">FAILED</div>\n");
  }
}

?>
