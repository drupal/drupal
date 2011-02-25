<?php

/**
 * @file
 * All incremental database updates performed between Drupal releases.
 */

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
  "2004-02-21" => "update_79",
  "2004-03-11: first update since Drupal 4.4.0 release" => "update_80",
  "2004-02-20" => "update_81",
  "2004-02-27" => "update_82",
  "2004-04-15" => "update_83",
  "2004-04-21" => "update_84",
  "2004-04-27" => "update_85",
  "2004-05-10" => "update_86",
  "2004-05-18" => "update_87",
  "2004-06-11" => "update_88",
  "2004-06-18" => "update_89",
  "2004-06-27" => "update_90",
  "2004-06-30" => "update_91",
  "2004-07-07" => "update_92",
  "2004-07-11" => "update_93",
  "2004-07-22" => "update_94",
  "2004-07-30" => "update_95",
  "2004-08-04" => "update_96",
  "2004-08-06" => "update_97",
  "2004-08-07" => "update_98",
  "2004-08-09" => "update_99",
  "2004-08-10" => "update_100",
  "2004-08-11" => "update_101",
  "2004-08-12" => "update_102",
  "2004-08-17" => "update_103",
  "2004-08-19" => "update_104",
  "2004-09-14" => "update_105",
  "2004-09-15" => "update_106",
  "2004-09-17" => "update_107",
  "2004-10-16" => "update_108",
  "2004-10-18" => "update_109",
  "2004-10-31: first update since Drupal 4.5.0 release" => "update_110",
  "2004-11-07" => "update_111",
  "2004-11-15" => "update_112",
  "2004-11-28" => "update_113",
  "2004-12-05" => "update_114",
  "2005-01-07" => "update_115",
  "2005-01-14" => "update_116",
  "2005-01-18" => "update_117",
  "2005-01-19" => "update_118",
  "2005-01-20" => "update_119",
  "2005-01-25" => "update_120",
  "2005-01-26" => "update_121",
  "2005-01-27" => "update_122",
  "2005-01-28" => "update_123",
  "2005-02-11" => "update_124",
  "2005-02-23" => "update_125",
  "2005-03-03" => "update_126",
  "2005-03-18" => "update_127",
  "2005-03-21" => "update_128",
  "2005-04-14" => "update_129",
  "2005-05-06" => "update_130",
  "2005-05-07" => "update_131",
  "2006-12-19" => "update_132",
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
    $result2 = db_query("SELECT cid, pid FROM {comments} WHERE nid = '%d' ORDER BY timestamp", $node->nid);
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
      KEY module (module)
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

function update_80() {
  if ($GLOBALS['db_type'] == 'mysql') {
    // Add a 'created' field to the users table:
    $ret[] = update_sql('ALTER TABLE {users} ADD created INT(11) NOT NULL');
    $ret[] = update_sql('ALTER TABLE {users} CHANGE timestamp changed INT(11) NOT NULL');

    // Add some indices to speed up the update process:
    $ret[] = update_sql('ALTER TABLE {comments} ADD index (timestamp)');
    $ret[] = update_sql('ALTER TABLE {node} ADD index (created)');

    // Assign everyone a created timestamp to begin with:
    $ret[] = update_sql("UPDATE {users} SET created = changed WHERE created = ''");

  }
  else {
    // Add a 'created' field to the users table:
    $ret[] = update_sql('ALTER TABLE {users} ADD created INTEGER');
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN created SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {users} SET created = 0");
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN created SET NOT NULL");

    $ret[] = update_sql('ALTER TABLE {users} RENAME timestamp TO changed');

    $ret[] = update_sql("UPDATE {users} SET created = changed WHERE created = 0");
  }

  // Print a status message:"
  print '<p>Note: this might take a while ...</p>';

  // Try updating the user records using the comment table:
  $result = db_query('SELECT DISTINCT(u.uid) FROM {comments} c LEFT JOIN {users} u ON c.uid = u.uid WHERE c.timestamp < u.created');
  while ($account = db_fetch_object($result)) {
    // Retrieve the proper timestamp:
    $timestamp = db_result(db_query('SELECT MIN(timestamp) FROM {comments} WHERE uid = %d', $account->uid));

    // Update this user record as well as older records with an older timestamp:
    db_query('UPDATE {users} SET created = %d WHERE created > %d AND uid <= %d', $timestamp, $timestamp, $account->uid);
  }

  // Try updating the user records using the node table:
  $result = db_query('SELECT DISTINCT(u.uid) FROM {node} n LEFT JOIN {users} u ON n.uid = u.uid WHERE n.created < u.created');
  while ($account = db_fetch_object($result)) {
    // Retrieve the proper timestamp:
    $timestamp = db_result(db_query('SELECT MIN(created) FROM {node} WHERE uid = %d', $account->uid));

    // Update this user record as well as older records with an older timestamp:
    db_query('UPDATE {users} SET created = %d WHERE created > %d AND uid <= %d', $timestamp, $timestamp, $account->uid);
  }

  if ($GLOBALS['db_type'] == 'mysql') {
    // Add profile module related tables:
    $ret[] = update_sql("CREATE TABLE {profile_fields} (
      fid int(10) NOT NULL auto_increment,
      title varchar(255) default NULL,
      name varchar(128) default NULL,
      explanation TEXT default NULL,
      category varchar(255) default NULL,
      type varchar(128) default NULL,
      weight tinyint(1) DEFAULT '0' NOT NULL,
      overview tinyint(1) DEFAULT '0' NOT NULL,
      options text,
      KEY category (category),
      UNIQUE KEY name (name),
      PRIMARY KEY (fid)
     );");

    $ret[] = update_sql("CREATE TABLE {profile_values} (
      fid int(11) unsigned default '0',
      uid int(11) unsigned default '0',
      value text,
      KEY uid (uid),
      KEY fid (fid)
     );");
    $ret[] = update_sql("ALTER TABLE {users} ADD picture varchar(255) NOT NULL DEFAULT ''");
  }
  else {

    $ret[] = update_sql("CREATE TABLE profile_fields (
      fid serial,
      title varchar(255) default NULL,
      name varchar(128) default NULL,
      explanation TEXT default NULL,
      category varchar(255) default NULL,
      type varchar(128) default NULL,
      weight smallint DEFAULT '0' NOT NULL,
      overview smallint DEFAULT '0' NOT NULL,
      options text,
      UNIQUE (name),
      PRIMARY KEY (fid)
    )");
    $ret[] = update_sql("CREATE INDEX profile_fields_category ON profile_fields (category)");

    $ret[] = update_sql("CREATE TABLE profile_values (
      fid integer default '0',
      uid integer default '0',
      value text
    )");
    $ret[] = update_sql("CREATE INDEX profile_values_uid ON profile_values (uid)");
    $ret[] = update_sql("CREATE INDEX profile_values_fid ON profile_values (fid)");

    $ret[] = update_sql("ALTER TABLE {users} ADD picture varchar(255)");
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN picture SET DEFAULT ''");
    $ret[] = update_sql("UPDATE {users} SET picture = ''");
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN picture SET NOT NULL");

  }

  // Migrate the old profile data to the new scheme:
  $fields = array(
    array("Name", "realname", "textfield", NULL, 0),
    array("Address", "address", "textfield", NULL, 0),
    array("City", "city", "textfield", NULL, 0),
    array("State, province or region", "state", "textfield", NULL, 0),
    array("Zip or postal code", "zip", "textfield", NULL, 0),
    array("Country", "country", "textfield", NULL, 1),
    array("Gender", "gender", "selection", "male\nfemale", 1),
    array("Job title", "job", "textfield", NULL, 0),
    array("ICQ messenger ID", "icq", "textfield", NULL, 0),
    array("MSN messenger ID", "msn", "textfield", NULL, 0),
    array("Yahoo messenger ID", "yahoo", "textfield", NULL, 0),
    array("AIM messenger ID", "aim", "textfield", NULL, 0),
    array("URL of homepage", "homepage", "url", NULL, 1),
    array("Biography", "biography", "textarea", NULL, 0),
    array("Interests", "interests", "textarea", NULL, 0),
    array("Public key", "publickey", "textarea", NULL, 0),
    array("Birthday", "birthday", "date", NULL, 0)
  );

  // Remove existing data (debug mode):
  db_query('DELETE FROM {profile_fields}');
  db_query('DELETE FROM {profile_values}');

  foreach ($fields as $field) {
    db_query("INSERT INTO {profile_fields} (title, name, type, category, options, overview) VALUES ('%s', '%s', '%s', 'Personal information', '%s', %d)", $field[0], $field[1], $field[2], $field[3], $field[4]);
  }

  $result = db_query("SELECT uid FROM {users} WHERE uid > 0");
  while ($account = db_fetch_object($result)) {
    // Load the user record:
    $account = user_load(array('uid' => $account->uid));
    $edit = array();

    // Modify the user record:
    foreach ($fields as $field) {
      $old = "profile_". $field[1];
      $new = $field[1];
      if ($account->$old) {
        $edit[$new] = $account->$old;
      }
      // Force deletion of old field
      $edit[$old] = NULL;
    }

    // Birthday format change:
    if ($edit['birthday']) {
      $edit['birthday'] = array('day' => $edit['birthday'], 'month' => $account->profile_birthmonth, 'year' => $account->profile_birthyear);
      // Force deletion of old field
      $edit['profile_birthmonth'] = NULL;
      $edit['profile_birthyear'] = NULL;
    }

    // Gender specific changes:
    if ($edit['gender'] == 'f') $edit['gender'] = 'female';
    if ($edit['gender'] == 'm') $edit['gender'] = 'male';

    // Avatar specific changes:
    if ($account->profile_avatar) {
      $edit['picture'] = $account->profile_avatar;
      // Force deletion of old field
      $edit['profile_avatar'] = NULL;
    }

    // Save the update record:
    user_save($account, $edit, 'Personal information');
  }

  // This variable is needed to distinguish between 4.5-RC sites which ran a faulty
  // update_80() and 4.5-final sites. See update_108.
  variable_set('update_80_fix', true);

  return $ret;
}

function update_81() {
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {profile_fields} ADD page varchar(255) default NULL');
  }
  else {
    $ret[] = update_sql('ALTER TABLE {profile_fields} ADD page varchar(255)');
    $ret[] = update_sql('ALTER TABLE {profile_fields} ALTER COLUMN page SET default NULL');
  }
  $ret[] = update_sql("UPDATE {profile_fields} SET type = 'url' WHERE name = 'homepage'");
  return $ret;
}

function update_82() {
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {profile_fields} ADD required tinyint(1) DEFAULT '0' NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {profile_fields} ADD required smallint");
    $ret[] = update_sql("ALTER TABLE {profile_fields} ALTER COLUMN required SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {profile_fields} SET required = '0'");
    $ret[] = update_sql("ALTER TABLE {profile_fields} ALTER COLUMN required SET NOT NULL");
  }
  return $ret;
}

function update_83() {
  $ret = array();
  if ($GLOBALS["db_type"] == "mysql") {
    $ret[] = update_sql("CREATE TABLE {menu} (
      mid int(10) unsigned NOT NULL default '0',
      pid int(10) unsigned NOT NULL default '0',
      path varchar(255) NOT NULL default '',
      title varchar(255) NOT NULL default '',
      weight tinyint(4) NOT NULL default '0',
      visibility int(1) unsigned NOT NULL default '0',
      status int(1) unsigned NOT NULL default '0',
      PRIMARY KEY  (mid)
     );");
  }
  else {
    $ret[] = update_sql("CREATE TABLE {menu} (
  mid serial,
  pid integer NOT NULL default '0',
  path varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  weight smallint NOT NULL default '0',
  visibility smallint NOT NULL default '0',
  status smallint NOT NULL default '0',
  PRIMARY KEY  (mid)
);");

  }
  db_next_id('{menu}_mid');
  return $ret;
}

function update_84() {
  $ret = array();
  if ($GLOBALS["db_type"] == "mysql") {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD help VARCHAR(255) NOT NULL DEFAULT '' AFTER description;");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD help VARCHAR(255)");
    $ret[] = update_sql("ALTER TABLE {vocabulary} ALTER help SET DEFAULT ''");
    $ret[] = update_sql("UPDATE {vocabulary} SET help = ''");
    $ret[] = update_sql("ALTER TABLE {vocabulary} ALTER help SET NOT NULL");
  }
  return $ret;
}

function update_85() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {bundle} RENAME TO {aggregator_category}");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} DROP attributes");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} CHANGE bid cid int(10) NOT NULL auto_increment");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ADD description longtext NOT NULL");
    $ret[] = update_sql("UPDATE {sequences} SET name = '{aggregator_category}_cid' WHERE name = '{bundle}_bid'");
    $ret[] = update_sql("ALTER TABLE {feed} RENAME TO {aggregator_feed}");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} DROP attributes");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} ADD block tinyint(2) NOT NULL");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ADD block tinyint(2) NOT NULL");
    $ret[] = update_sql("UPDATE {sequences} SET name = '{aggregator_feed}_fid' WHERE name = '{feed}_fid'");
    $ret[] = update_sql("ALTER TABLE {item} RENAME TO {aggregator_item}");
    $ret[] = update_sql("ALTER TABLE {aggregator_item} DROP attributes");
    $max = db_result(db_query_range("SELECT iid FROM {aggregator_item} ORDER BY iid DESC", 0, 1));
    if ($max) {
      $ret[] = update_sql("INSERT INTO {sequences} (name, id) VALUES ('{aggregator_item}_iid', $max)");
    }
    $ret[] = update_sql("CREATE TABLE {aggregator_category_feed} (
      fid int(10) NOT NULL,
      cid int(10) NOT NULL,
      PRIMARY KEY (fid, cid)
     )");
    $ret[] = update_sql("CREATE TABLE {aggregator_category_item} (
      iid int(10) NOT NULL,
      cid int(10) NOT NULL,
      PRIMARY KEY (iid, cid)
     )");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} MODIFY description longtext NULL");
    $ret[] = update_sql("ALTER TABLE {aggregator_item} MODIFY description longtext NULL");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} MODIFY description longtext NULL");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} MODIFY image longtext NULL");

  }
  else {
    $ret[] = update_sql("ALTER TABLE {bundle} RENAME TO {aggregator_category}");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} DROP attributes");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} RENAME bid TO cid");
    $lastvalue = db_result(db_query('SELECT last_value from {bundle}_bid_seq'));
    $ret[] = update_sql("CREATE SEQUENCE {aggregator_category}_cid_seq START $lastvalue MINVALUE 0");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ALTER cid SET DEFAULT nextval('public.{aggregator_category}_cid_seq'::text)");

    $ret[] = update_sql("ALTER TABLE {aggregator_category} ADD description text");
    $ret[] = update_sql("UPDATE {aggregator_category} SET description = ''");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ALTER COLUMN description SET NOT NULL");

    $ret[] = update_sql("ALTER TABLE {feed} RENAME TO {aggregator_feed}");
    $lastvalue = db_result(db_query('SELECT last_value from {feed}_fid_seq'));
    $ret[] = update_sql("CREATE SEQUENCE {aggregator_feed}_fid_seq START $lastvalue MINVALUE 0");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} ALTER fid SET DEFAULT nextval('public.{aggregator_feed}_fid_seq'::text)");

    $ret[] = update_sql("ALTER TABLE {aggregator_feed} DROP attributes");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} ADD block smallint");
    $ret[] = update_sql("UPDATE {aggregator_feed} SET block = 0");
    $ret[] = update_sql("ALTER TABLE {aggregator_feed} ALTER COLUMN block SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ADD block smallint");
    $ret[] = update_sql("UPDATE {aggregator_category} SET block = 0");
    $ret[] = update_sql("ALTER TABLE {aggregator_category} ALTER COLUMN block SET NOT NULL");

    $ret[] = update_sql("ALTER TABLE {item} RENAME TO {aggregator_item}");
    $lastvalue = db_result(db_query('SELECT last_value from {item}_iid_seq'));
    $ret[] = update_sql("CREATE SEQUENCE {aggregator_item}_iid_seq START $lastvalue MINVALUE 0");
    $ret[] = update_sql("ALTER TABLE {aggregator_item} ALTER iid SET DEFAULT nextval('public.{aggregator_item}_iid_seq'::text)");

    $ret[] = update_sql("ALTER TABLE {aggregator_item} DROP attributes");
    $ret[] = update_sql("CREATE TABLE {aggregator_category_feed} (
      fid integer NOT NULL default '0',
      cid integer NOT NULL default '0',
      PRIMARY KEY  (fid,cid)
    );");
    $ret[] = update_sql("CREATE TABLE {aggregator_category_item} (
      iid integer NOT NULL default '0',
      cid integer NOT NULL default '0',
      PRIMARY KEY  (iid,cid)
     )");
  }
  return $ret;
}

function update_86() {
  $ret = array();
  $ret[] = update_sql("INSERT INTO {users_roles} (uid, rid) SELECT uid, rid FROM {users}");
  // TODO: should we verify the insert above worked before dropping rid?
  if ($GLOBALS['db_type'] == 'mysql') {
    //only the most recent versions of postgres support dropping columns
    $ret[] = update_sql("ALTER TABLE {users} DROP rid");
  }
  return $ret;
}

function update_87() {
  // Works for both postgres and mysql
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {comments} ADD name varchar(60) DEFAULT NULL");
  $ret[] = update_sql("ALTER TABLE {comments} ADD mail varchar(64) DEFAULT NULL");
  $ret[] = update_sql("ALTER TABLE {comments} ADD homepage varchar(255) DEFAULT NULL");
  return $ret;
}

function update_88() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {menu} DROP status");
    $ret[] = update_sql("ALTER TABLE {menu} DROP visibility");
    $ret[] = update_sql("ALTER TABLE {menu} ADD type INT(2) UNSIGNED DEFAULT '0' NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {menu} ADD type smallint");
    $ret[] = update_sql("ALTER TABLE {menu} ALTER COLUMN type SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {menu} SET type = '0'");
    $ret[] = update_sql("ALTER TABLE {menu} ALTER COLUMN type SET NOT NULL");
  }
  $ret[] = update_sql("DELETE FROM {menu}");
  return $ret;
}

function update_89() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {node} CHANGE static sticky INT(2) DEFAULT '0' NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {node} RENAME static TO sticky;");
  }

  // Change the node settings, so that it uses node_sticky_$type instead of node_static_$type
  $result = db_query("SELECT * FROM {variable} WHERE name LIKE 'node_static_%'");
  while ($variable = db_fetch_object($result)) {
    //get the node type name, place it into $name_arr. The name can be found with $name_arr[2]
    $name_arr = explode("_", $variable->name, 3);
    $ret[] = update_sql("INSERT INTO {variable} (name,value) VALUES ('node_sticky_". $name_arr[2] ."','". $variable->value. "')");
  }
  $ret[] = db_query("DELETE FROM {variable} WHERE name LIKE 'node_static_%'");

  return $ret;
}

function update_90() {

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {profile_fields} CHANGE overview visibility INT(1) UNSIGNED DEFAULT '0' NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {profile_fields} RENAME overview TO visibility");
  }
  $ret[] = update_sql("UPDATE {profile_fields} SET visibility = 2 WHERE visibility = 1");
  $ret[] = update_sql("UPDATE {profile_fields} SET visibility = 1 WHERE visibility = 0");
  return $ret;
}

function update_91() {
  $ret = array();
  if ($GLOBALS["db_type"] == "pgsql") {
    // node_created was created implicitly somewhere else
    $ret[] = update_sql("CREATE INDEX node_changed ON {node} (changed)");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_created (created)");
    $ret[] = update_sql("ALTER TABLE {node} ADD INDEX node_changed (changed)");
  }
  return $ret;
}

function update_92() {
  $ret = array();
  $ret[] = update_sql("DELETE FROM {cache}");
  return $ret;
}

function update_93() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('DROP INDEX url_alias_src_idx');
  }
  else {
    $ret[] = update_sql('ALTER TABLE {url_alias} DROP INDEX src');
  }
  $ret[] = update_sql("INSERT INTO {url_alias} (src, dst) VALUES ('node/feed', 'rss.xml')");
  return $ret;
}

function update_94() {
  /**
  * Postgres only update
  */
  $ret = array();
  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('DROP FUNCTION "greatest"(integer, integer)');
    $ret[] = update_sql("
      CREATE FUNCTION greatest(integer, integer) RETURNS integer AS '
        BEGIN
          IF $2 IS NULL THEN
            RETURN $1;
          END IF;
          IF $1 > $2 THEN
            RETURN $1;
          END IF;
          RETURN $2;
        END;
      ' LANGUAGE 'plpgsql';
    ");
  }
  return $ret;
}

function update_95() {
 $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {node_access} (
     nid int(10) unsigned NOT NULL default '0',
     gid int(10) unsigned NOT NULL default '0',
     realm varchar(255) NOT NULL default '',
     grant_view tinyint(1) unsigned NOT NULL default '0',
     grant_update tinyint(1) unsigned NOT NULL default '0',
     grant_delete tinyint(1) unsigned NOT NULL default '0',
     PRIMARY KEY  (nid,gid,realm)
     )");
  }
  else {
    $ret[] = update_sql("CREATE TABLE {node_access} (
    nid SERIAL,
    gid integer NOT NULL default '0',
    realm text NOT NULL default '',
    grant_view smallint NOT NULL default '0',
    grant_update smallint NOT NULL default '0',
    grant_delete smallint NOT NULL default '0',
    PRIMARY KEY  (nid,gid,realm)
    )");

    $ret[] = update_sql("CREATE FUNCTION \"concat\"(text, text) RETURNS text AS '
        BEGIN
          RETURN $1 || $2;
        END;
        ' LANGUAGE 'plpgsql';");
  }
  $ret[] = update_sql("INSERT INTO {node_access} VALUES (0, 0, 'all', 1, 0, 0);");
  return $ret;
}

function update_96() {
  $ret = array();

  $ret[] = update_sql('ALTER TABLE {accesslog} ADD path VARCHAR(255) DEFAULT NULL');
  $ret[] = update_sql('UPDATE {accesslog} SET path = CONCAT("node/", nid) WHERE nid != 0');
  $ret[] = update_sql('ALTER TABLE {accesslog} ADD title VARCHAR(255) DEFAULT NULL');
  /* Only supported by MySQL 4.0.4 and up
  $ret[] = update_sql('UPDATE {accesslog} a, {node} n SET a.title = n.title WHERE a.nid = n.nid');*/
  $ret[] = update_sql('ALTER TABLE {accesslog} DROP nid');

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {menu} ADD description varchar(255) DEFAULT '' NOT NULL");
  }
  else {
    $ret[] = update_sql("ALTER TABLE {menu} ADD description varchar(255)");
    $ret[] = update_sql("ALTER TABLE {menu} ALTER COLUMN description SET DEFAULT ''");
    $ret[] = update_sql("UPDATE {menu} SET description = ''");
    $ret[] = update_sql("ALTER TABLE {menu} ALTER COLUMN description SET NOT NULL");
  }

  return $ret;
}

function update_97() {
  /**
   * Works for both PostgreSQL and MySQL
   */
  $convert = array('node/view/' => 'node/', 'book/view/' => 'book/', 'user/view/' => 'user/');
  foreach ($convert as $from => $to) {
    $result = db_query("SELECT pid, src FROM {url_alias} WHERE src LIKE '%s%%'", $from);
    while ($alias = db_fetch_object($result)) {
      db_query("UPDATE {url_alias} SET src = '%s' WHERE pid = '%s'", str_replace($from, $to, $alias->src), $alias->pid);
    }
  }

  return array();
}

function update_98() {
  /**
   * Works for both PostgreSQL and MySQL
   */
  $result = db_query("SELECT pid, src FROM {url_alias} WHERE src LIKE 'taxonomy/%%'");
  while ($alias = db_fetch_object($result)) {
    list(, $page, $op, $terms) = explode('/', $alias->src);
    if ($page == 'feed' || $page == 'page') {
      switch ($op) {
        case 'or':
          $new = 'taxonomy/term/'. str_replace(',', '+', $terms);
          break;
        case 'and':
          $new = 'taxonomy/term/'. $terms;
          break;
      }
      if ($new) {
        if ($page == 'feed') {
          $new .= '/0/feed';
        }
        db_query("UPDATE {url_alias} SET src = '%s' WHERE pid = '%s'", $new, $alias->pid);
      }
    }
  }

  return array();
}

function update_99() {
  // Filter patch - Multiple input formats
  $ret = array();

  /*
  ** Load the list of PHP book and page nodes.
  */
  $php_nodes = array();
  $res = db_query("SELECT nid FROM {book} WHERE format = 1");
  while ($book = db_fetch_object($res)) {
    $php_nodes[] = $book->nid;
  }
  $res = db_query("SELECT nid FROM {page} WHERE format = 1");
  while ($page = db_fetch_object($res)) {
    $php_nodes[] = $page->nid;
  }

  /*
  ** Apply database changes
  */
  if ($GLOBALS['db_type'] == 'mysql') {
    // Filters table
    $ret[] = update_sql("ALTER TABLE {filters} ADD format int(4) NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {filters} ADD delta tinyint(2) NOT NULL default '0'");

    // Filter_formats table
    $ret[] = update_sql("CREATE TABLE {filter_formats} (
      format int(4) NOT NULL auto_increment,
      name varchar(255) NOT NULL default '',
      roles varchar(255) NOT NULL default '',
      cache tinyint(2) NOT NULL default '1',
      PRIMARY KEY  (format)
    )");

    // Store formats in nodes, comments and boxes
    $ret[] = update_sql("ALTER TABLE {boxes} CHANGE type format int(4) NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {comments} ADD format int(4) NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {node} ADD format int(4) NOT NULL default '0'");

    // Get rid of the old book/page type info
    $ret[] = update_sql("ALTER TABLE {book} DROP format");
    $ret[] = update_sql("ALTER TABLE {page} DROP format");
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $result = db_query("SELECT * FROM {filters}");
    if ($result) {
      while ($obj = db_fetch_object($result)) {
        $filters[] = $obj;
      }
    }

    $ret[] = update_sql("DROP TABLE {filters}");

    $ret[] = update_sql("CREATE TABLE {filters} (
      format integer NOT NULL DEFAULT '0',
      module varchar(64) NOT NULL DEFAULT '',
      delta smallint NOT NULL DEFAULT 1,
      weight smallint DEFAULT '0' NOT NULL
    )");

    $ret[] = update_sql("CREATE INDEX filters_module_idx ON filters(module)");
    if (is_array($filters)) {
      foreach ($filters as $filter) {
        db_query("INSERT INTO {filters} VALUES (%d, '%s', %d, %d)", $filter->format ? $filter->format : 0, $filter->module, $filter->delta ? $filter->delta : 1, $filter->weight);
      }
    }

    $ret[] = update_sql("CREATE TABLE {filter_formats} (
      format SERIAL,
      name varchar(255) NOT NULL default '',
      roles varchar(255) NOT NULL default '',
      cache smallint NOT NULL default '0',
      PRIMARY KEY (format)
    )");

    $ret[] = update_sql("ALTER TABLE {boxes} RENAME type TO format");

    $ret[] = update_sql("ALTER TABLE {comments} ADD format smallint");
    $ret[] = update_sql("ALTER TABLE {comments} ALTER COLUMN format SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {comments} SET format = '0'");
    $ret[] = update_sql("ALTER TABLE {comments} ALTER COLUMN format SET NOT NULL");

    $ret[] = update_sql("ALTER TABLE {node} ADD format smallint");
    $ret[] = update_sql("ALTER TABLE {node} ALTER COLUMN format SET DEFAULT '0'");
    $ret[] = update_sql("UPDATE {node} SET format = '0'");
    $ret[] = update_sql("ALTER TABLE {node} ALTER COLUMN format SET NOT NULL");


    /* Postgres usually can't drop columns
    $ret[] = update_sql("ALTER TABLE {book} DROP format");
    $ret[] = update_sql("ALTER TABLE {page} DROP format");
    */

  }

  // Initialize all nodes and comments to the legacy format (see below)
  $ret[] = update_sql("UPDATE {node} SET format = 1");
  $ret[] = update_sql("UPDATE {comments} SET format = 1");

  // Set format to PHP for the old PHP book/page nodes.
  if (count($php_nodes)) {
    $ret[] = update_sql("UPDATE {node} SET format = 2 WHERE nid IN (". implode(',', $php_nodes) .")");
  }

  // Boxes now use the filtering system as well.
  // Type 0 (HTML) maps to Format 3 (Full HTML).
  // Type 1 (PHP) maps to Format 2 (PHP).
  $ret[] = update_sql("UPDATE {boxes} SET format = 3 - format");

  /*
  ** Update PHP content to use <?php ?> tags.
  */
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("UPDATE {node} SET teaser = CONCAT('<?php ', teaser) WHERE format = 2");
    $ret[] = update_sql("UPDATE {node} SET body = CONCAT('<?php ', body) WHERE format = 2");
    $ret[] = update_sql("UPDATE {boxes} SET body = CONCAT('<?php ', body) WHERE format = 2");
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    // TODO: someone needs to verify if this works.
    $ret[] = update_sql("UPDATE {node} SET teaser = '<?php ' || teaser WHERE format = 2");
    $ret[] = update_sql("UPDATE {node} SET body = '<?php ' || body WHERE format = 2");
    $ret[] = update_sql("UPDATE {boxes} SET body = '<?php ' || body WHERE format = 2");
  }


  /*
  ** We now set up some input formats. One of these is a 'legacy' format which
  ** tries to preserve as much settings as possible from before the patch.
  ** The other two are 'PHP code' and 'Full HTML'.
  */

  // We pick an appropriate name for the legacy format.
  $old_html_filter = variable_get('filter_html', 0);
  if ($old_html_filter == FILTER_HTML_ESCAPE) {
    $default = 'Plain text';
  }
  else {
    $default = 'Filtered HTML';
  }
  // Make sure the legacy format is accessible to all roles
  $all_roles = array_keys(user_roles());
  $ret[] = update_sql("INSERT INTO {filter_formats} VALUES (". db_next_id("{filter_formats}_format") .",'$default',',". implode(',', $all_roles) .",',1)");

  // Determine which roles have the old 'create php content' permission.
  $res = db_query("SELECT rid FROM {permission} WHERE perm LIKE '%create php content%'");
  $php_roles = array();
  while ($role = db_fetch_object($res)) {
    $php_roles[] = $role->rid;
  }

  $ret[] = update_sql("INSERT INTO {filter_formats} VALUES (". db_next_id("{filter_formats}_format") .",'PHP code','". implode(',', $php_roles) .",',0)");

  // This is a 'Full HTML' format which allows all HTML without restrictions.
  $ret[] = update_sql("INSERT INTO {filter_formats} VALUES (". db_next_id("{filter_formats}_format") .",'Full HTML','',1)");

  // Set the default format to the legacy format
  variable_set('filter_default_format', 1);

  // Put the old filters into the legacy format
  $ret[] = update_sql("UPDATE {filters} SET format = 1");

  // Add filter.module's standard filters (these used to be hardcoded).
  if (!variable_get('rewrite_old_urls', 0)) {
    $ret[] = update_sql("DELETE FROM {filters} WHERE module = 'filter'");
  }
  else {
    $ret[] = update_sql("UPDATE {filters} SET delta = 2 WHERE module ='filter'");
  }
  if ($old_html_filter != 0) {
    $ret[] = update_sql("INSERT INTO {filters} (format, module, delta, weight) VALUES (1,'filter',0,0)"); // HTML tag/style filter
  }
  $ret[] = update_sql("INSERT INTO {filters} (format, module, delta, weight) VALUES (1,'filter',2,1)"); // Linebreak filter
  $ret[] = update_sql("INSERT INTO {filters} (format, module, delta, weight) VALUES (2,'filter',1,0)"); // PHP evaluator
  $ret[] = update_sql("INSERT INTO {filters} (format, module, delta, weight) VALUES (3,'filter',2,0)"); // Linebreak filter

  // Migrate the settings for all core/contrib filtering modules into the legacy
  // format.
  $migrate = array('filter_html', // filter.module
                   'allowed_html',
                   'filter_style',
                   'anyfilter_regexps', // anyfilter.module
                   'htmlcorrector_smartclose', // htmlcorrector.module
                   'htmlcorrector_xhtmlify',
                   'htmlcorrector_valueentities',
                   'project_filter', // project.module
                   'latex_filter_link' // latex.module
                   );

  foreach ($migrate as $variable) {
    $value = variable_get($variable, NULL);
    if ($value != NULL) {
      variable_set($variable .'_1', $value);
      variable_del($variable);
    }
  }

  return $ret;
}

function update_100() {

  $ret = array();
  if ($GLOBALS["db_type"] == "mysql") {
    $ret[] = update_sql("CREATE TABLE {locales_source} (
      lid int(11) NOT NULL auto_increment,
      location varchar(128) NOT NULL default '',
      source blob NOT NULL,
      PRIMARY KEY  (lid)
    )");
    $ret[] = update_sql("CREATE TABLE {locales_target} (
      lid int(11) NOT NULL default '0',
      translation blob NOT NULL,
      locale varchar(12) NOT NULL default '',
      plid int(11) NOT NULL default '0',
      plural int(1) NOT NULL default '0',
      KEY lid (lid),
      KEY lang (locale),
      KEY plid (plid),
      KEY plural (plural)
    )");
    $ret[] = update_sql("ALTER TABLE {users} CHANGE language language varchar(12) NOT NULL default ''");
  }
  else {
    $ret[] = update_sql("CREATE TABLE {locales_target} (
      lid int4 NOT NULL default '0',
      translation text DEFAULT '' NOT NULL,
      locale varchar(12) NOT NULL default '',
      plid int4 NOT NULL default '0',
      plural int4 NOT NULL default '0'
    )");
    $ret[] = update_sql("CREATE INDEX {locales_target}_lid ON {locales_target}(lid)");
    $ret[] = update_sql("CREATE INDEX {locales_target}_locale ON {locales_target}(locale)");
    $ret[] = update_sql("CREATE INDEX {locales_target}_plid ON {locales_target}(plid)");
    $ret[] = update_sql("CREATE INDEX {locales_target}_plural ON {locales_target}(plural)");

    $ret[] = update_sql("CREATE SEQUENCE {locales_source}_lid INCREMENT 1 START 0 MINVALUE 0");
    $ret[] = update_sql("CREATE TABLE {locales_source} (
      lid serial,
      location varchar(128) NOT NULL default '',
      source text NOT NULL,
      PRIMARY KEY  (lid)
    )");

    $ret[] = update_sql("ALTER TABLE {users} rename language to lang_archive");
    $ret[] = update_sql("ALTER TABLE {users} add language varchar(12)");
    $ret[] = update_sql("ALTER TABLE {users} ALTER language SET DEFAULT ''");
    $ret[] = update_sql("UPDATE {users} SET language = ''");
    $ret[] = update_sql("ALTER TABLE {users} ALTER language SET NOT NULL");
    $ret[] = update_sql("update {users} set language = lang_archive");

  }
  $ret[] = update_sql("INSERT INTO {locales_meta} (locale, name, enabled, isdefault) VALUES ('en', 'English', '1', '1')");

  return $ret;
}

function update_101() {
  /**
   * Works for both PostgreSQL and MySQL
   */
  include_once 'includes/locale.inc';
  // get the language columns
  $result = db_query('SELECT * FROM {locales} LIMIT 1');
  $fields = array();
  if (db_num_rows($result)) {
    $columns = array_keys(db_fetch_array($result));
    foreach ($columns as $field) {
      $fields[$field] = 1;
    }

    // but not the fixed fields
    unset($fields['lid'], $fields['location'], $fields['string']);

    // insert locales
    $list = _locale_get_iso639_list();
    foreach ($fields as $key => $value) {
      if (db_result(db_query("SELECT COUNT(lid) FROM {locales} WHERE $key != ''"))) {
        if (isset($list[$key])) {
          $name = $list[$key][0];
          if ($key == 'en') {
            $key = 'en-local';
          }
          db_query("INSERT INTO {locales_meta} (locale, name) VALUES ('%s', '%s')", $key, $name);
        }
        else {
          db_query("INSERT INTO {locales_meta} (locale, name) VALUES ('%s', '%s')", $key, $key);
        }
      }
    }

    // get all strings
    $result = db_query('SELECT * FROM {locales}');
    while ($entry = db_fetch_object($result)) {
      // insert string if at least one translation exists
      $test = 'return $entry->'. implode(' == "" && $entry->', array_keys($fields)) .' == "";';
      if (!eval($test)) {
        db_query("INSERT INTO {locales_source} (location, source) VALUES ('%s', '%s')", $entry->location, $entry->string);
        $lid = db_fetch_object(db_query("SELECT lid FROM {locales_source} WHERE location = '%s' AND source = '%s'", $entry->location, $entry->string));
        foreach ($fields as $key => $value) {
          // insert translation if non-empty
          if ($key == 'en') {
            $keynew = 'en-local';
          }
          else {
            $keynew = $key;
          }
          db_query("INSERT INTO {locales_target} (lid, translation, locale) VALUES (%d, '%s', '%s')", $lid->lid, $entry->$key, $keynew);
        }
      }
    }
  }

  $ret = array();
  $ret[] = update_sql("DROP TABLE {locales}");
  return $ret;
}

function update_102() {
  /**
   * Works for both PostgreSQL and MySQL
   */
  return array(update_sql("INSERT INTO {system} (filename, name, type, description, status, throttle, bootstrap) VALUES ('modules/legacy.module', 'legacy', 'module', '', 1, 0, 0)"));
}

function update_103() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {files} (
      fid int(10) unsigned NOT NULL default '0',
      nid int(10) unsigned NOT NULL default '0',
      filename varchar(255) NOT NULL default '',
      filepath varchar(255) NOT NULL default '',
      filemime varchar(255) NOT NULL default '',
      filesize int(10) unsigned NOT NULL default '0',
      list tinyint(1) unsigned NOT NULL default '0',
      PRIMARY KEY  (fid)
    )");
  }
  else {
    $ret[] = update_sql("CREATE TABLE {files} (
      fid serial,
      nid integer NOT NULL default '0',
      filename varchar(255) NOT NULL default '',
      filepath varchar(255) NOT NULL default '',
      filemime varchar(255) NOT NULL default '',
      filesize integer NOT NULL default '0',
      list smallint NOT NULL default '0',
      PRIMARY KEY  (fid)
    )");
  }
  return $ret;
}

function update_104() {
  /**
   * Works for both PostgreSQL and MySQL
   */
  $ret = array();
  if (variable_get('theme_default', 'xtemplate') == 'chameleon') {
    $ret[] = update_sql("DELETE FROM {system} WHERE name = 'chameleon'");
    $ret[] = update_sql("INSERT INTO {system} VALUES ('themes/chameleon/chameleon.theme','chameleon','theme','',1,0,0)");
    $ret[] = update_sql("INSERT INTO {system} VALUES ('themes/chameleon/marvin/style.css','marvin','theme','themes/chameleon/chameleon.theme',1,0,0)");
    if (variable_get("chameleon_stylesheet", "themes/chameleon/pure/chameleon.css") == "themes/chameleon/marvin/chameleon.css") {
      variable_set('theme_default', 'chameleon/marvin');
    }
   else {
      variable_set('theme_default', 'chameleon');
    }
  }
  elseif (variable_get('theme_default', 'xtemplate') == 'xtemplate') {
    $ret[] = update_sql("DELETE FROM {system} WHERE name = 'xtemplate'");
    $ret[] = update_sql("INSERT INTO {system} VALUES ('themes/bluemarine/xtemplate.xtmpl','bluemarine','theme','themes/engines/xtemplate/xtemplate.engine',1,0,0)");
    $ret[] = update_sql("INSERT INTO {system} VALUES ('themes/pushbutton/xtemplate.xtmpl','pushbutton','theme','themes/engines/xtemplate/xtemplate.engine',1,0,0)");
    $ret[] = update_sql("INSERT INTO {system} VALUES ('themes/engines/xtemplate/xtemplate.engine','xtemplate','theme_engine','',1,0,0)");
    if (variable_get('xtemplate_template', 'default') == 'pushbutton') {
      variable_set('theme_default', 'pushbutton');
    }
    else {
      variable_set('theme_default', 'bluemarine');
    }

    // Convert old xtemplate settings to new theme system
    $settings = array();
    $convert = array('xtemplate_primary_links'   => 'primary_links',
                     'xtemplate_secondary_links' => 'secondary_links',
                     'xtemplate_search_box'      => 'toggle_search',
                     'xtemplate_avatar_node'     => 'toggle_node_user_picture',
                     'xtemplate_avatar_comment'  => 'toggle_comment_user_picture');
    foreach ($convert as $from => $to) {
      if (($value = variable_get($from, NULL)) != NULL) {
        $settings[$to] = $value;
        variable_del($from);
      }
    }

    // Logo requires special treatment. Used to be an HTML tag, now it's a path to an image.
    if (($logo = variable_get('xtemplate_logo', NULL)) != NULL) {
      $match = array();
      // If logo was of the form <img src="..">, convert it.
      if (preg_match('@src=(?:["\']?)(.+?)(?:["\']?(?:>| ?/>))@i', $logo, $match)) {
        $settings['default_logo'] = 0;
        $settings['logo_path'] = $match[1];
      }
      variable_del('xtemplate_logo');
    }

    if (count($settings) > 0) {
      variable_set('theme_settings', $settings);
    }

    // These are not part of 'theme_settings'
    $convert = array('xtemplate_avatar_default' => 'user_picture_default',
                     'xtemplate_mission'        => 'site_mission');
    foreach ($convert as $from => $to) {
      if (($value = variable_get($from, NULL)) != NULL) {
        variable_set($to, $value);
        variable_del($from);
      }
    }
  }
  return $ret;
}

function update_105() {
  $ret = array();

  $shadowupdates = db_query("SELECT nid,tid FROM {forum} WHERE shadow=0");
  while ($shadowrecord = db_fetch_object($shadowupdates)) {
    db_query("DELETE FROM {term_node} WHERE nid = %d AND tid <> %d", $shadowrecord->nid, $shadowrecord->tid);
  }

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {forum} DROP shadow");
    $ret[] = update_sql('ALTER TABLE {node} ADD INDEX node_status_type (status, type, nid)');
  }
  else {
    // PostgreSQL is incapable of dropping columns in all but the latest versions.
    $ret[] = update_sql("CREATE INDEX {node}_status_type_idx ON {node} (status, type, nid)");

    $ret[] = update_sql("CREATE FUNCTION \"if\"(integer, text, text) RETURNS text AS '
      BEGIN
        IF $1 THEN
          RETURN $2;
        END IF;
        IF NOT $1 THEN
          RETURN $3;
        END IF;
      END;
      ' LANGUAGE 'plpgsql'");
  }

  return $ret;
}

function update_106() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {cache} ADD INDEX expire (expire)');
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('CREATE INDEX {cache}_expire_idx ON {cache}(expire)');
  }

  $ret[] = update_sql('DELETE FROM {cache}');
  return $ret;
}

function update_107() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {profile_fields} ADD register TINYINT(1) DEFAULT 0 NOT NULL AFTER required');
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {profile_fields} ADD register smallint');
  }

  return $ret;
}

function update_108() {
  $ret = array();
  // This update is needed for 4.5-RC sites, where profile data was not being
  // wiped from the user->data column correctly because update_80() was faulty.
  if (!variable_get('update_80_fix', false)) {
    // The data field needs to be cleared of profile fields.
    $result = db_query("SELECT uid FROM {users} WHERE data LIKE '%profile%'");
    while ($uid = db_fetch_object($result)) {
      $user = user_load(array('uid' => $uid->uid));
      $unset = array();
      foreach ($user as $key => $value) {
        if (substr($key, 0, 8) == 'profile_') {
          // Fields with a NULL value are wiped from the data column.
          $unset[$key] = NULL;
        }
      }
      user_save($user, $unset);
    }
  }
  return $ret;
}

function update_109() {
  $ret = array();
  // This is to fix the PostreSQL locales_source table.
  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {locales_source} RENAME location TO old_location");
    $ret[] = update_sql("ALTER TABLE {locales_source} ADD COLUMN location text");
    db_query("UPDATE {locales_source} SET location = old_location");
    $ret[] = update_sql("ALTER TABLE {locales_source} ALTER COLUMN location SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {locales_source} ALTER COLUMN location SET DEFAULT ''");
    $ret[] = update_sql("ALTER TABLE {locales_target} DROP CONSTRAINT {locales_target}_locale_key ");
    $ret[] = update_sql("ALTER TABLE {locales_target} DROP CONSTRAINT {locales_target}_plid_key ");
    $ret[] = update_sql("ALTER TABLE {locales_target} DROP CONSTRAINT {locales_target}_plural_key ");
  }
  return $ret;
}

function update_110() {
  $ret = array();

  // TODO: needs PGSQL version
  if ($GLOBALS['db_type'] == 'mysql') {
    /*
    ** Search
    */

    $ret[] = update_sql('DROP TABLE {search_index}');
    $ret[] = update_sql("CREATE TABLE {search_index} (
      word varchar(50) NOT NULL default '',
      sid int(10) unsigned NOT NULL default '0',
      type varchar(16) default NULL,
      fromsid int(10) unsigned NOT NULL default '0',
      fromtype varchar(16) default NULL,
      score int(10) unsigned default NULL,
      KEY sid (sid),
      KEY fromsid (fromsid),
      KEY word (word)
      ) TYPE=MyISAM");

    $ret[] = update_sql("CREATE TABLE {search_total} (
      word varchar(50) NOT NULL default '',
      count int(10) unsigned default NULL,
      PRIMARY KEY word (word)
      ) TYPE=MyISAM");


    /*
    ** Blocks
    */

    $ret[] = update_sql('ALTER TABLE {blocks} DROP path');
    $ret[] = update_sql('ALTER TABLE {blocks} ADD visibility tinyint(1) NOT NULL');
    $ret[] = update_sql('ALTER TABLE {blocks} ADD pages text NOT NULL');
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    /*
    ** Search
    */
    $ret[] = update_sql('DROP TABLE {search_index}');
    $ret[] = update_sql("CREATE TABLE {search_index} (
      word varchar(50) NOT NULL default '',
      sid integer NOT NULL default '0',
      type varchar(16) default NULL,
      fromsid integer NOT NULL default '0',
      fromtype varchar(16) default NULL,
      score integer default NULL
      )");
    $ret[] = update_sql("CREATE INDEX {search_index}_sid_idx on {search_index}(sid)");
    $ret[] = update_sql("CREATE INDEX {search_index}_fromsid_idx on {search_index}(fromsid)");
    $ret[] = update_sql("CREATE INDEX {search_index}_word_idx on {search_index}(word)");

    $ret[] = update_sql("CREATE TABLE {search_total} (
      word varchar(50) NOT NULL default '' PRIMARY KEY,
      count integer default NULL
      )");


    /*
    ** Blocks
    */
    // Postgres can only drop columns since 7.4
    #$ret[] = update_sql('ALTER TABLE {blocks} DROP path');

    $ret[] = update_sql('ALTER TABLE {blocks} ADD visibility smallint');
    $ret[] = update_sql("ALTER TABLE {blocks} ALTER COLUMN visibility set default 0");
    $ret[] = update_sql('UPDATE {blocks} SET visibility = 0');
    $ret[] = update_sql('ALTER TABLE {blocks} ALTER COLUMN visibility SET NOT NULL');
    $ret[] = update_sql('ALTER TABLE {blocks} ADD pages text');
    $ret[] = update_sql("ALTER TABLE {blocks} ALTER COLUMN pages set default ''");
    $ret[] = update_sql("UPDATE {blocks} SET pages = ''");
    $ret[] = update_sql('ALTER TABLE {blocks} ALTER COLUMN pages SET NOT NULL');

  }

  $ret[] = update_sql("DELETE FROM {variable} WHERE name = 'node_cron_last'");

  $ret[] = update_sql('UPDATE {blocks} SET status = 1, custom = 2 WHERE status = 0 AND custom = 1');

  return $ret;
}

function update_111() {
  $ret = array();

  $ret[] = update_sql("DELETE FROM {variable} WHERE name LIKE 'throttle_%'");

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {sessions} ADD PRIMARY KEY sid (sid)');
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {sessions} ADD UNIQUE(sid)');
  }

  return $ret;
}

function update_112() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {flood} (
      event varchar(64) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp int(11) NOT NULL default '0'
     );");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("CREATE TABLE {flood} (
      event varchar(64) NOT NULL default '',
      hostname varchar(128) NOT NULL default '',
      timestamp integer NOT NULL default 0
     );");
  }

  return $ret;
}

function update_113() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {accesslog} ADD aid int(10) NOT NULL auto_increment, ADD PRIMARY KEY (aid)');
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("SELECT * INTO TEMPORARY {accesslog}_t FROM {accesslog}");
    $ret[] = update_sql("DROP TABLE {accesslog}");
    $ret[] = update_sql("CREATE TABLE {accesslog} (
      aid serial,
      title varchar(255) default NULL,
      path varchar(255) default NULL,
      url varchar(255) default NULL,
      hostname varchar(128) default NULL,
      uid integer default '0',
      timestamp integer NOT NULL default '0'
    )");
    $ret[] = update_sql("INSERT INTO accesslog (title, path, url, hostname, uid, timestamp) SELECT title, path, url, hostname, uid, timestamp FROM accesslog_t");

    $ret[] = update_sql("DROP TABLE {accesslog}_t");
    $ret[] = update_sql("CREATE INDEX {accesslog}_timestamp_idx ON {accesslog} (timestamp);");

  }

  // Flush the menu cache:
  cache_clear_all('menu:', TRUE);

  return $ret;
}

function update_114() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {queue} (
      nid int(10) unsigned NOT NULL,
      uid int(10) unsigned NOT NULL,
      vote int(3) NOT NULL default '0',
      PRIMARY KEY (nid, uid)
     )");
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("CREATE TABLE {queue} (
      nid integer NOT NULL default '0',
      uid integer NOT NULL default '0',
      vote integer NOT NULL default '0',
      PRIMARY KEY (nid, uid)
    )");
    $ret[] = update_sql("CREATE INDEX {queue}_nid_idx ON queue(nid)");
    $ret[] = update_sql("CREATE INDEX {queue}_uid_idx ON queue(uid)");
  }

  $result = db_query("SELECT nid, votes, score, users FROM {node}");
  while ($node = db_fetch_object($result)) {
    if (isset($node->users)) {
      $arr = explode(',', $node->users);
      unset($node->users);
      foreach ($arr as $value) {
        $arr2 = explode('=', trim($value));
        if (isset($arr2[0]) && isset($arr2[1])) {
          switch ($arr2[1]) {
            case '+ 1':
              db_query("INSERT INTO {queue} (nid, uid, vote) VALUES (%d, %d, %d)", $node->nid, (int)$arr2[0], 1);
              break;
            case '- 1':
              db_query("INSERT INTO {queue} (nid, uid, vote) VALUES (%d, %d, %d)", $node->nid, (int)$arr2[0], -1);
              break;
            default:
              db_query("INSERT INTO {queue} (nid, uid, vote) VALUES (%d, %d, %d)", $node->nid, (int)$arr2[0], 0);
          }
        }
      }
    }
  }

  if ($GLOBALS['db_type'] == 'mysql') {
    // Postgres only supports dropping of columns since 7.4
    $ret[] = update_sql("ALTER TABLE {node} DROP votes");
    $ret[] = update_sql("ALTER TABLE {node} DROP score");
    $ret[] = update_sql("ALTER TABLE {node} DROP users");
  }

  return $ret;
}

function update_115() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {watchdog} ADD severity tinyint(3) unsigned NOT NULL default '0'");
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {watchdog} ADD severity smallint');
    $ret[] = update_sql('UPDATE {watchdog} SET severity = 0');
    $ret[] = update_sql('ALTER TABLE {watchdog} ALTER COLUMN severity SET NOT NULL');
    $ret[] = update_sql('ALTER TABLE {watchdog} ALTER COLUMN severity SET DEFAULT 0');
  }
  return $ret;
}

function update_116() {
  return array(update_sql("DELETE FROM {system} WHERE name = 'admin'"));
}

function update_117() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {vocabulary_node_types} (
                         vid int(10) NOT NULL default '',
                         type varchar(16) NOT NULL default '',
                         PRIMARY KEY (vid, type))");
  }
  else if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("CREATE TABLE {vocabulary_node_types} (
                         vid serial,
                         type varchar(16) NOT NULL default '',
                          PRIMARY KEY (vid, type)) ");
  }
  return $ret;
}

function update_118() {
  $ret = array();
  $node_types = array();
  $result = db_query('SELECT vid, nodes FROM {vocabulary}');
  while ($vocabulary = db_fetch_object($result)) {
    $node_types[$vocabulary->vid] = explode(',', $vocabulary->nodes);
  }
  foreach ($node_types as $vid => $type_array) {
    foreach ($type_array as $type) {
      db_query("INSERT INTO {vocabulary_node_types} (vid, type) VALUES (%d, '%s')", $vid, $type);
    }
  }
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} DROP nodes");
  }
  return $ret;
}

function update_119() {
  $ret = array();

  foreach (node_list() as $type) {
    $node_options = array();
    if (variable_get('node_status_'. $type, 1)) {
      $node_options[] = 'status';
    }
    if (variable_get('node_moderate_'. $type, 0)) {
      $node_options[] = 'moderate';
    }
    if (variable_get('node_promote_'. $type, 1)) {
      $node_options[] = 'promote';
    }
    if (variable_get('node_sticky_'. $type, 0)) {
      $node_options[] = 'sticky';
    }
    if (variable_get('node_revision_'. $type, 0)) {
      $node_options[] = 'revision';
    }
    variable_set('node_options_'. $type, $node_options);
    variable_del('node_status_'. $type);
    variable_del('node_moderate_'. $type);
    variable_del('node_promote_'. $type);
    variable_del('node_sticky_'. $type);
    variable_del('node_revision_'. $type);
  }

  return $ret;
}

function update_120() {
  $ret = array();

  // Rewrite old URL aliases.  Works for both PostgreSQL and MySQL
  $result = db_query("SELECT pid, src FROM {url_alias} WHERE src LIKE 'blog/%%'");
  while ($alias = db_fetch_object($result)) {
    list(, $page, $op, $uid) = explode('/', $alias->src);
    if ($page == 'feed') {
      $new = "blog/$uid/feed";
      update_sql("UPDATE {url_alias} SET src = '%s' WHERE pid = '%s'", $new, $alias->pid);
    }
  }

  return $ret;
}

function update_121() {
  $ret = array();

  // Remove the unused page table.
  $ret[] = update_sql('DROP TABLE {page}');

  return $ret;
}

function update_122() {

  $ret = array();
  $ret[] = update_sql("ALTER TABLE {blocks} ADD types text");
  return $ret;

}

function update_123() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD module varchar(255) NOT NULL default ''");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD module varchar(255)");
    $ret[] = update_sql("UPDATE {vocabulary} SET module = ''");
    $ret[] = update_sql("ALTER TABLE {vocabulary} ALTER COLUMN module SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {vocabulary} ALTER COLUMN module SET DEFAULT ''");
  }

  $ret[] = update_sql("UPDATE {vocabulary} SET module = 'taxonomy'");
  $vid = variable_get('forum_nav_vocabulary', '');
  if (!empty($vid)) {
    $ret[] = update_sql("UPDATE {vocabulary} SET module = 'forum' WHERE vid = " . $vid);
  }

  return $ret;
}

function update_124() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    // redo update_105, correctly creating node_comment_statistics
    $ret[] = update_sql("DROP TABLE IF EXISTS {node_comment_statistics}");

    $ret[] = update_sql("CREATE TABLE {node_comment_statistics} (
      nid int(10) unsigned NOT NULL auto_increment,
      last_comment_timestamp int(11) NOT NULL default '0',
      last_comment_name varchar(60) default NULL,
      last_comment_uid int(10) NOT NULL default '0',
      comment_count int(10) unsigned NOT NULL default '0',
      PRIMARY KEY (nid),
      KEY node_comment_timestamp (last_comment_timestamp)
      ) TYPE=MyISAM");
  }

  else {
    // also drop incorrectly named table for PostgreSQL
    $ret[] = update_sql("DROP TABLE {node}_comment_statistics");

    $ret[] = update_sql("CREATE TABLE {node_comment_statistics} (
      nid integer NOT NULL,
      last_comment_timestamp integer NOT NULL default '0',
      last_comment_name varchar(60)  default NULL,
      last_comment_uid integer NOT NULL default '0',
      comment_count integer NOT NULL default '0',
      PRIMARY KEY (nid)
    )");

    $ret[] = update_sql("CREATE INDEX {node_comment_statistics}_timestamp_idx ON {node_comment_statistics}(last_comment_timestamp);
");
  }

  // initialize table
  $ret[] = update_sql("INSERT INTO {node_comment_statistics} (nid, last_comment_timestamp, last_comment_name, last_comment_uid, comment_count) SELECT n.nid, n.changed, NULL, 0, 0 FROM {node} n");

  // fill table
  $result = db_query("SELECT c.nid, c.timestamp, c.name, c.uid, COUNT(c.nid) as comment_count FROM {node} n LEFT JOIN {comments} c ON c.nid = n.nid WHERE c.status = 0 GROUP BY c.nid, c.timestamp, c.name, c.uid");
  while ($comment_record = db_fetch_object($result)) {
    $count = db_result(db_query('SELECT COUNT(cid) FROM {comments} WHERE nid = %d AND status = 0', $comment_record->nid));
    $ret[] = db_query("UPDATE {node_comment_statistics} SET comment_count = %d, last_comment_timestamp = %d, last_comment_name = '%s', last_comment_uid = %d WHERE nid = %d", $count, $comment_record->timestamp, $comment_record->name, $comment_record->uid, $comment_record->nid);
  }

  return $ret;
}

function update_125() {
  // Postgres only update.
  $ret = array();

  if ($GLOBALS['db_type'] == 'pgsql') {

    $ret[] = update_sql("CREATE OR REPLACE FUNCTION if(boolean, anyelement, anyelement) RETURNS anyelement AS '
          SELECT CASE WHEN $1 THEN $2 ELSE $3 END;
        ' LANGUAGE 'sql'");

    $ret[] = update_sql("CREATE FUNCTION greatest(integer, integer, integer) RETURNS integer AS '
                          SELECT greatest($1, greatest($2, $3));
                        ' LANGUAGE 'sql'");

  }

  return $ret;
}

function update_126() {
  variable_set('forum_block_num_0', variable_get('forum_block_num', 5));
  variable_set('forum_block_num_1', variable_get('forum_block_num', 5));
  variable_del('forum_block_num');

  return array();
}

function update_127() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {poll} RENAME voters TO polled");
  }
  else if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {poll} CHANGE voters polled longtext");
  }
  return $ret;
}

function update_128() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {term_node} ADD PRIMARY KEY (tid,nid)');
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {term_node} ADD PRIMARY KEY (tid,nid)');
  }

  return $ret;
}

function update_129() {
  /**
   * PostgreSQL only update.
   */
  $ret = array();

  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('DROP TABLE {search_total}');

    $ret[] = update_sql("CREATE TABLE {search_total} (
              word varchar(50) NOT NULL default '',
              count float default NULL)");
    $ret[] = update_sql('CREATE INDEX {search_total}_word_idx ON {search_total}(word)');

    /**
     * Wipe the search index
     */
    include_once('modules/search.module');
    search_wipe();
  }

  return $ret;
}

function update_130() {
  $result = db_query("SELECT delta FROM {blocks} WHERE module = 'aggregator'");
  while ($block = db_fetch_object($result)) {
    list($type, $id) = explode(':', $block->delta);
    db_query("UPDATE {blocks} SET delta = '%s' WHERE module = 'aggregator' AND delta = '%s'", $type .'-'. $id, $block->delta);
  }
  return array();
}

function update_131() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {locales_source} CHANGE location location varchar(255) NOT NULL default ''");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {locales_source} RENAME location TO location_old");
    $ret[] = update_sql("ALTER TABLE {locales_source} ADD location varchar(255)");
    $ret[] = update_sql("UPDATE {locales_source} SET location = location_old");
    $ret[] = update_sql("ALTER TABLE {locales_source} ALTER location SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {locales_source} ALTER location SET DEFAULT ''");
    $ret[] = update_sql("ALTER TABLE {locales_source} DROP location_old");
  }

  return $ret;
}

/**
 * Cid matching by MySQL should be case-sensitive.
 */
function update_132() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {cache} CHANGE cid cid varchar(255) BINARY NOT NULL default ''");
  }

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
