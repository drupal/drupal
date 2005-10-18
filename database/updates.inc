<?php
// $Id: updates.inc,v 1.139 2005/10/18 14:41:26 unconed Exp $

/**
 * @file
 * All incremental database updates performed between Drupal releases.
 *
 * For all updates after 147, please use the following syntax:
 *
 * function update_N() {
 *   $ret = array();
 *
 *   switch ($GLOBALS['db_type']) {
 *     case 'pgsql':
 *       // PostgreSQL code goes here
 *       break;
 *     case 'mysql':
 *     case 'mysqli':
 *       // MySQL code goes here
 *       break;
 *   }
 *
 *   return $ret;
 * }
 *
 */

// Define the various updates in an array("date : comment" => "function");
$sql_updates = array(
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
  "2005-04-08: first update since Drupal 4.6.0 release" => "update_129",
  "2005-04-10" => "update_130",
  "2005-04-11" => "update_131",
  "2005-04-14" => "update_132",
  "2005-04-24" => "update_133",
  "2005-04-30" => "update_134",
  "2005-05-06" => "update_135",
  "2005-05-08" => "update_136",
  "2005-05-09" => "update_137",
  "2005-05-10" => "update_138",
  "2005-05-11" => "update_139",
  "2005-05-12" => "update_140",
  "2005-05-22" => "update_141",
  "2005-07-29" => "update_142",
  "2005-07-30" => "update_143",
  "2005-08-08" => "update_144",
  "2005-08-15" => "update_145",
  "2005-08-25" => "update_146",
  "2005-09-07" => "update_147",
  "2005-09-18" => "update_148",
  "2005-09-27" => "update_149",
  "2005-10-15" => "update_150"
);

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

  foreach (node_get_types() as $type => $name) {
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
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD tags tinyint(3) unsigned default '0' NOT NULL");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD tags smallint default '0' NOT NULL");
  }

  return $ret;
}

function update_130() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {sessions} ADD cache int(11) NOT NULL default '0' AFTER timestamp");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {sessions} ADD cache int(11) NOT NULL default '0' AFTER timestamp");
  }
  return $ret;
}

function update_131() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {boxes} DROP INDEX title");
    $ret[] = update_sql("ALTER TABLE {boxes} ADD INDEX title (title)");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("DROP INDEX boxes_title_idx");;
    $ret[] = update_sql("CREATE INDEX title ON {boxes} (title)");
  }

  return $ret;
}

function update_132() {
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
    include_once './modules/search.module';
    search_wipe();
  }

  return $ret;
}

function update_133() {
  $ret[] = update_sql("CREATE TABLE {contact} (
    subject varchar(255) NOT NULL default '',
    recipients longtext NOT NULL default '',
    reply longtext NOT NULL default ''
    )");

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {users} ADD login int(11) NOT NULL default '0'");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {users} ADD login integer");
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN login SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {users} ALTER COLUMN login SET DEFAULT '0'");
  }

  return $ret;
}

function update_134() {
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {blocks} DROP types');
  }
  else {
    // Postgres can only drop columns since 7.4
    #$ret[] = update_sql('ALTER TABLE {blocks} DROP types');
  }
  return $ret;
}

function update_135() {
  $result = db_query("SELECT delta FROM {blocks} WHERE module = 'aggregator'");
  while ($block = db_fetch_object($result)) {
    list($type, $id) = explode(':', $block->delta);
    db_query("UPDATE {blocks} SET delta = '%s' WHERE module = 'aggregator' AND delta = '%s'", $type .'-'. $id, $block->delta);
  }
  return array();
}

function update_136() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {users} CHANGE COLUMN changed access int(11) NOT NULL default '0'");
  $ret[] = update_sql('UPDATE {users} SET access = login WHERE login > created');
  $ret[] = update_sql('UPDATE {users} SET access = created WHERE access = 0');
  return $ret;
}

function update_137() {
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

function update_138() {
  $ret = array();
  // duplicate of update_97 which never got into the default database.* files.
  $ret[] = update_sql("INSERT INTO {url_alias} (src, dst) VALUES ('node/feed', 'rss.xml')");
  return $ret;
}

function update_139() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {accesslog} ADD timer int(10) unsigned NOT NULL default '0'");
  return $ret;
}

function update_140() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {url_alias} ADD INDEX (src)");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("CREATE INDEX url_alias_src ON {url_alias}(src)");
  }
  return $ret;
}

function update_141() {
  $ret = array();

  variable_del('upload_maxsize_total');

  return $ret;
}

function update_142() {
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {watchdog} ADD COLUMN referer varchar(128) NOT NULL");
  return $ret;
}

function update_143() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {contact} CHANGE subject category VARCHAR(255) NOT NULL ");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {contact} RENAME COLUMN subject TO category");
  }
  $ret[] = update_sql("ALTER TABLE {contact} ADD PRIMARY KEY (category)");

  return $ret;
}

function update_144() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {node} CHANGE type type VARCHAR(32) NOT NULL");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {node} RENAME type TO type_old");
    $ret[] = update_sql("ALTER TABLE {node} ADD type varchar(32)");
    $ret[] = update_sql("ALTER TABLE {node} ALTER type SET NOT NULL");
    $ret[] = update_sql("ALTER TABLE {node} ALTER type SET DEFAULT ''");
    $ret[] = update_sql("UPDATE {node} SET type = type_old");
    $ret[] = update_sql("ALTER TABLE {node} DROP type_old");
  }
  return $ret;
}

function update_145() {
  $default_theme = variable_get('theme_default', 'bluemarine');
  $ret = array();
  $ret[] = update_sql("ALTER TABLE {blocks} CHANGE region region varchar(64) default 'left' NOT NULL");
  $ret[] = update_sql("ALTER TABLE {blocks} ADD theme varchar(255) NOT NULL default ''");

  // Intialize block data for default theme
  $ret[] = update_sql("UPDATE {blocks} SET region = 'left' WHERE region = '0'");
  $ret[] = update_sql("UPDATE {blocks} SET region = 'right' WHERE region = '1'");
  db_query("UPDATE {blocks} SET theme = '%s'", $default_theme);

  // Initialze block data for other enabled themes.
  $themes = list_themes();
  foreach (array_keys($themes) as $theme) {
    if (($theme != $default_theme) && $themes[$theme]->status == 1) {
      system_initialize_theme_blocks($theme);
    }
  }

  return $ret;
}

function update_146() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {node_revisions}
                                SELECT nid, nid AS vid, uid, type, title, body, teaser, changed AS timestamp, format
                                FROM {node}");

    $ret[] = update_sql("ALTER TABLE {node_revisions} CHANGE nid nid int(10) unsigned NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {node_revisions} ADD log longtext");

    $ret[] = update_sql("ALTER TABLE {node} ADD vid int(10) unsigned NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {files} ADD vid int(10) unsigned NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {book} ADD vid int(10) unsigned NOT NULL default '0'");
    $ret[] = update_sql("ALTER TABLE {forum} ADD vid int(10) unsigned NOT NULL default '0'");

    $ret[] = update_sql("ALTER TABLE {book} DROP PRIMARY KEY");
    $ret[] = update_sql("ALTER TABLE {forum} DROP PRIMARY KEY");
    $ret[] = update_sql("ALTER TABLE {files} DROP PRIMARY KEY");

    $ret[] = update_sql("UPDATE {node} SET vid = nid");
    $ret[] = update_sql("UPDATE {forum} SET vid = nid");
    $ret[] = update_sql("UPDATE {book} SET vid = nid");
    $ret[] = update_sql("UPDATE {files} SET vid = nid");

    $ret[] = update_sql("ALTER TABLE {book} ADD PRIMARY KEY vid (vid)");
    $ret[] = update_sql("ALTER TABLE {forum} ADD PRIMARY KEY vid (vid)");
    $ret[] = update_sql("ALTER TABLE {node_revisions} ADD PRIMARY KEY vid (vid)");
    $ret[] = update_sql("ALTER TABLE {node_revisions} ADD KEY nid (nid)");
    $ret[] = update_sql("ALTER TABLE {node_revisions} ADD KEY uid (uid)");

    $ret[] = update_sql("CREATE TABLE {old_revisions} SELECT nid, type, revisions FROM {node} WHERE revisions != ''");

    $ret[] = update_sql("ALTER TABLE {book} ADD KEY nid (nid)");
    $ret[] = update_sql("ALTER TABLE {forum} ADD KEY nid (nid)");
    $ret[] = update_sql("ALTER TABLE {files} ADD KEY fid (fid)");
    $ret[] = update_sql("ALTER TABLE {files} ADD KEY vid (vid)");
    $vid = db_next_id('{node}_nid');
    $ret[] = update_sql("INSERT INTO {sequences} (name, id) VALUES ('{node_revisions}_vid', $vid)");
  }
  else { // pgsql
    $ret[] = update_sql("CREATE TABLE {node_revisions} AS
                                SELECT nid, nid AS vid, uid, type, title, body, teaser, changed AS timestamp, format
                                FROM {node}");

    $ret[] = update_sql("UPDATE {node} SET vid = nid");
    $ret[] = update_sql("UPDATE {forum} SET vid = nid");
    $ret[] = update_sql("UPDATE {book} SET vid = nid");
    $ret[] = update_sql("UPDATE {files} SET vid = nid");

    $ret[] = update_sql("CREATE TABLE {old_revisions} AS SELECT nid, type, revisions FROM {node} WHERE revisions != ''");
  }

  // Move logs too.
  $result = db_query("SELECT nid, log FROM {book} WHERE log != ''");
  while ($row = db_fetch_object($result)) {
    db_query("UPDATE {node_revisions} SET log = '%s' WHERE vid = %d", $row->log, $row->nid);
  }

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {book} DROP log");
    $ret[] = update_sql("ALTER TABLE {node} DROP teaser");
    $ret[] = update_sql("ALTER TABLE {node} DROP body");
    $ret[] = update_sql("ALTER TABLE {node} DROP format");
    $ret[] = update_sql("ALTER TABLE {node} DROP revisions");
  }
  else { // pgsql
  }

  return $ret;
}

function update_147() {
  $ret = array();

  // this update is mysql only, pgsql should get it right in the first try.
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {node_revisions} DROP type");
  }

  return $ret;
}

function update_148() {
  $ret = array();

  // Add support for tracking users' session ids (useful for tracking anon users)
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      $ret[] = update_sql("ALTER TABLE {accesslog} ADD sid varchar(32)");
      $ret[] = update_sql("ALTER TABLE {accesslog} ALTER sid SET NOT NULL");
      $ret[] = update_sql("ALTER TABLE {accesslog} ALTER sid SET DEFAULT ''");
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {accesslog} ADD sid varchar(32) NOT NULL default ''");
      break;
 }

  return $ret;
}

function update_149() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'pgsql':
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {files} ADD COLUMN description VARCHAR(255) NOT NULL DEFAULT ''");
      break;
    default:
      break;
  }

  return $ret;
}

function update_150() {
  $ret = array();

  $ret[] = update_sql("DELETE FROM {variable} WHERE name = 'node_cron_last'");
  $ret[] = update_sql("DELETE FROM {variable} WHERE name = 'minimum_word_size'");
  $ret[] = update_sql("DELETE FROM {variable} WHERE name = 'remove_short'");

  $ret[] = update_sql("DELETE FROM {node_counter} WHERE nid = 0");

  $ret[] = update_sql('DROP TABLE {search_index}');
  $ret[] = update_sql('DROP TABLE {search_total}');
  
  switch ($GLOBALS['db_type']) {
    case 'mysqli':
    case 'mysql':
      $ret[] = update_sql("CREATE TABLE {search_dataset} (
                           sid int(10) unsigned NOT NULL default '0',
                           type varchar(16) default NULL,
                           data longtext NOT NULL,
                           KEY sid_type (sid, type)
                           )");

      $ret[] = update_sql("CREATE TABLE {search_index} (
                           word varchar(50) NOT NULL default '',
                           sid int(10) unsigned NOT NULL default '0',
                           type varchar(16) default NULL,
                           fromsid int(10) unsigned NOT NULL default '0',
                           fromtype varchar(16) default NULL,
                           score float default NULL,
                           KEY sid_type (sid, type),
                           KEY from_sid_type (fromsid, fromtype),
                           KEY word (word)
                           )");

      $ret[] = update_sql("CREATE TABLE {search_total} (
                           word varchar(50) NOT NULL default '',
                           count float default NULL,
                           PRIMARY KEY word (word)
                           )");
      break;
    case 'pgsql':
      $ret[] = update_sql("CREATE TABLE {search_dataset} (
                           sid integer NOT NULL default '0',
                           type varchar(16) default NULL,
                           data text NOT NULL default '',
                           KEY sid_type (sid, type)
                           )");

      $ret[] = update_sql("CREATE TABLE {search_index} (
                           word varchar(50) NOT NULL default '',
                           sid integer NOT NULL default '0',
                           type varchar(16) default NULL,
                           fromsid integer NOT NULL default '0',
                           fromtype varchar(16) default NULL,
                           score float default NULL
                           )");
      $ret[] = update_sql("CREATE INDEX search_index_sid_type_idx ON {search_index}(sid, type)");
      $ret[] = update_sql("CREATE INDEX search_index_from_sid_type_idx ON {search_index}(fromsid, fromtype)");
      $ret[] = update_sql("CREATE INDEX search_index_word_idx ON {search_index}(word)");

      $ret[] = update_sql("CREATE TABLE {search_total} (
                           word varchar(50) NOT NULL default '',
                           count float default NULL
                           )");
      $ret[] = update_sql("CREATE INDEX search_total_word_idx ON {search_total}(word)");
      break;
    default:
      break;
  }
  return $ret;
}

function update_sql($sql) {
  $edit = $_POST["edit"];
  $result = db_query($sql);
  if ($result) {
    return array('1', check_plain($sql) ."\n<span class=\"success\">OK</span>\n");
  }
  else {
    return array('0', check_plain($sql) ."\n<span class=\"failure\">FAILED</span>\n");
  }
}

?>
