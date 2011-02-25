<?php

function system_update_110() {
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
      )");

    $ret[] = update_sql("CREATE TABLE {search_total} (
      word varchar(50) NOT NULL default '',
      count int(10) unsigned default NULL,
      PRIMARY KEY word (word)
      )");


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

function system_update_111() {
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

function system_update_112() {
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

function system_update_113() {
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
    $ret[] = update_sql("INSERT INTO {accesslog} (title, path, url, hostname, uid, timestamp) SELECT title, path, url, hostname, uid, timestamp FROM {accesslog}_t");

    $ret[] = update_sql("DROP TABLE {accesslog}_t");
    $ret[] = update_sql("CREATE INDEX {accesslog}_timestamp_idx ON {accesslog} (timestamp);");

  }

  // Flush the menu cache:
  cache_clear_all('menu:', TRUE);

  return $ret;
}

function system_update_114() {
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

function system_update_115() {
  $ret = array();

  // This update has been moved to update_fix_watchdog_115 in update.php because it
  // is needed for the basic functioning of the update script.

  return $ret;
}

function system_update_116() {
  return array(update_sql("DELETE FROM {system} WHERE name = 'admin'"));
}

function system_update_117() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {vocabulary_node_types} (
                         vid int(10) NOT NULL default '0',
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

function system_update_118() {
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

function system_update_119() {
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

function system_update_120() {
  $ret = array();

  // Rewrite old URL aliases. Works for both PostgreSQL and MySQL
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

function system_update_121() {
  $ret = array();

  // Remove the unused page table.
  $ret[] = update_sql('DROP TABLE {page}');

  return $ret;
}

function system_update_122() {

  $ret = array();
  $ret[] = update_sql("ALTER TABLE {blocks} ADD types text");
  return $ret;

}

function system_update_123() {
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

function system_update_124() {
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
      )");
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
    db_query("UPDATE {node_comment_statistics} SET comment_count = %d, last_comment_timestamp = %d, last_comment_name = '%s', last_comment_uid = %d WHERE nid = %d", $count, $comment_record->timestamp, $comment_record->name, $comment_record->uid, $comment_record->nid);
  }

  return $ret;
}

function system_update_125() {
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

function system_update_126() {
  variable_set('forum_block_num_0', variable_get('forum_block_num', 5));
  variable_set('forum_block_num_1', variable_get('forum_block_num', 5));
  variable_del('forum_block_num');

  return array();
}

function system_update_127() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {poll} RENAME voters TO polled");
  }
  else if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {poll} CHANGE voters polled longtext");
  }
  return $ret;
}

function system_update_128() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql('ALTER TABLE {term_node} ADD PRIMARY KEY (tid,nid)');
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {term_node} ADD PRIMARY KEY (tid,nid)');
  }

  return $ret;
}

function system_update_129() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {vocabulary} ADD tags tinyint(3) unsigned default '0' NOT NULL");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    db_add_column($ret, 'vocabulary', 'tags', 'smallint', array('default' => 0, 'not null' => TRUE));
  }

  return $ret;
}

function system_update_130() {
  $ret = array();

  // This update has been moved to update_fix_sessions in update.php because it
  // is needed for the basic functioning of the update script.

  return $ret;
}

function system_update_131() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {boxes} DROP INDEX title");
    // Removed recreation of the index, which is not present in the db schema
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("ALTER TABLE {boxes} DROP CONSTRAINT {boxes}_title_key");
  }

  return $ret;
}

function system_update_132() {
  /**
   * PostgreSQL only update.
   */
  $ret = array();

  if (!variable_get('update_132_done', FALSE)) {
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

    variable_del('update_132_done');
  }

  return $ret;
}

function system_update_133() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("CREATE TABLE {contact} (
      subject varchar(255) NOT NULL default '',
      recipients longtext NOT NULL,
      reply longtext NOT NULL
      )");
    $ret[] = update_sql("ALTER TABLE {users} ADD login int(11) NOT NULL default '0'");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    // Table {contact} is changed in update_143() so I have moved it's creation there.
    // It was never created here for postgres because of errors.

    db_add_column($ret, 'users', 'login', 'int', array('default' => 0, 'not null' => TRUE));
  }

  return $ret;
}

function system_update_134() {
  $ret = array();
  $ret[] = update_sql('ALTER TABLE {blocks} DROP types');
  return $ret;
}

function system_update_135() {
  if (!variable_get('update_135_done', FALSE)) {
    $result = db_query("SELECT delta FROM {blocks} WHERE module = 'aggregator'");
    while ($block = db_fetch_object($result)) {
      list($type, $id) = explode(':', $block->delta);
      db_query("UPDATE {blocks} SET delta = '%s' WHERE module = 'aggregator' AND delta = '%s'", $type .'-'. $id, $block->delta);
    }

    variable_del('update_135_done');
  }
  return array();
}

function system_update_136() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      $ret[] = update_sql("DROP INDEX {users}_changed_idx"); // We drop the index first because it won't be renamed
      $ret[] = update_sql("ALTER TABLE {users} RENAME changed TO access");
      $ret[] = update_sql("CREATE INDEX {users}_access_idx on {users}(access)"); // Re-add the index
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {users} CHANGE COLUMN changed access int(11) NOT NULL default '0'");
      break;
  }

  $ret[] = update_sql('UPDATE {users} SET access = login WHERE login > created');
  $ret[] = update_sql('UPDATE {users} SET access = created WHERE access = 0');
  return $ret;
}

function system_update_137() {
  $ret = array();

  if (!variable_get('update_137_done', FALSE)) {
    if ($GLOBALS['db_type'] == 'mysql') {
      $ret[] = update_sql("ALTER TABLE {locales_source} CHANGE location location varchar(255) NOT NULL default ''");
    }
    elseif ($GLOBALS['db_type'] == 'pgsql') {
      db_change_column($ret, 'locales_source', 'location', 'location', 'varchar(255)', array('not null' => TRUE, 'default' => "''"));
    }
    variable_del('update_137_done');
  }

  return $ret;
}

function system_update_138() {
  $ret = array();
  // duplicate of update_97 which never got into the default database.* files.
  $ret[] = update_sql("INSERT INTO {url_alias} (src, dst) VALUES ('node/feed', 'rss.xml')");
  return $ret;
}

function system_update_139() {
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      db_add_column($ret, 'accesslog', 'timer', 'int', array('not null' => TRUE, 'default' => 0));
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {accesslog} ADD timer int(10) unsigned NOT NULL default '0'");
      break;
  }

  return $ret;
}

function system_update_140() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {url_alias} ADD INDEX (src)");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("CREATE INDEX {url_alias}_src_idx ON {url_alias}(src)");
  }
  return $ret;
}

function system_update_141() {
  $ret = array();

  variable_del('upload_maxsize_total');

  return $ret;
}

function system_update_142() {
  $ret = array();

  // This update has been moved to update_fix_sessions in update.php because it
  // is needed for the basic functioning of the update script.

  return $ret;
}

function system_update_143() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {contact} CHANGE subject category VARCHAR(255) NOT NULL ");
    $ret[] = update_sql("ALTER TABLE {contact} ADD PRIMARY KEY (category)");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    // Why the table is created here? See update_133().
    $ret[] = update_sql("CREATE TABLE {contact} (
      category varchar(255) NOT NULL default '',
      recipients text NOT NULL default '',
      reply text NOT NULL default '',
      PRIMARY KEY (category))");
  }

  return $ret;
}

function system_update_144() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {node} CHANGE type type VARCHAR(32) NOT NULL");
  }
  elseif ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql("DROP INDEX {node}_type_idx"); // Drop indexes using "type" column
    $ret[] = update_sql("DROP INDEX {node}_title_idx");
    db_change_column($ret, 'node', 'type', 'type', 'varchar(32)', array('not null' => TRUE, 'default' => "''"));
    // Let's recreate the indexes
    $ret[] = update_sql("CREATE INDEX {node}_type_idx ON {node}(type)");
    $ret[] = update_sql("CREATE INDEX {node}_title_type_idx ON {node}(title,type)");
    $ret[] = update_sql("CREATE INDEX {node}_status_type_nid_idx ON {node}(status,type,nid)");
  }
  return $ret;
}

function system_update_145() {
  $default_theme = variable_get('theme_default', 'bluemarine');

  $themes = list_themes();
  if (!array_key_exists($default_theme, $themes)) {
      variable_set('theme_default', 'bluemarine');
      $default_theme = 'bluemarine';
   }

  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      db_change_column($ret, 'blocks', 'region', 'region', 'varchar(64)', array('default' => "'left'", 'not null' => TRUE));
      db_add_column($ret, 'blocks', 'theme', 'varchar(255)', array('not null' => TRUE, 'default' => "''"));
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {blocks} CHANGE region region varchar(64) default 'left' NOT NULL");
      $ret[] = update_sql("ALTER TABLE {blocks} ADD theme varchar(255) NOT NULL default ''");
      break;
  }

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

function system_update_146() {
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
    $ret[] = update_sql("CREATE TABLE {node_revisions} (
      nid integer NOT NULL default '0',
      vid integer NOT NULL default '0',
      uid integer NOT NULL default '0',
      title varchar(128) NOT NULL default '',
      body text NOT NULL default '',
      teaser text NOT NULL default '',
      log text NOT NULL default '',
      timestamp integer NOT NULL default '0',
      format int NOT NULL default '0',
      PRIMARY KEY (vid))");
    $ret[] = update_sql("INSERT INTO {node_revisions} (nid, vid, uid, title, body, teaser, timestamp, format)
      SELECT nid, nid AS vid, uid, title, body, teaser, changed AS timestamp, format
      FROM {node}");
    $ret[] = update_sql('CREATE INDEX {node_revisions}_nid_idx ON {node_revisions}(nid)');
    $ret[] = update_sql('CREATE INDEX {node_revisions}_uid_idx ON {node_revisions}(uid)');
    $vid = db_next_id('{node}_nid');
    $ret[] = update_sql("CREATE SEQUENCE {node_revisions}_vid_seq INCREMENT 1 START $vid");

    db_add_column($ret, 'node',  'vid', 'int', array('not null' => TRUE, 'default' => 0));
    db_add_column($ret, 'files', 'vid', 'int', array('not null' => TRUE, 'default' => 0));
    db_add_column($ret, 'book',  'vid', 'int', array('not null' => TRUE, 'default' => 0));
    db_add_column($ret, 'forum', 'vid', 'int', array('not null' => TRUE, 'default' => 0));

    $ret[] = update_sql("ALTER TABLE {book} DROP CONSTRAINT {book}_pkey");
    $ret[] = update_sql("ALTER TABLE {forum} DROP CONSTRAINT {forum}_pkey");
    $ret[] = update_sql("ALTER TABLE {files} DROP CONSTRAINT {files}_pkey");

    $ret[] = update_sql("UPDATE {node} SET vid = nid");
    $ret[] = update_sql("UPDATE {forum} SET vid = nid");
    $ret[] = update_sql("UPDATE {book} SET vid = nid");
    $ret[] = update_sql("UPDATE {files} SET vid = nid");

    $ret[] = update_sql("ALTER TABLE {book} ADD PRIMARY KEY (vid)");
    $ret[] = update_sql("ALTER TABLE {forum} ADD PRIMARY KEY (vid)");

    $ret[] = update_sql("CREATE TABLE {old_revisions} AS SELECT nid, type, revisions FROM {node} WHERE revisions != ''");

    $ret[] = update_sql('CREATE INDEX {node}_vid_idx ON {node}(vid)');
    $ret[] = update_sql('CREATE INDEX {forum}_nid_idx ON {forum}(nid)');
    $ret[] = update_sql('CREATE INDEX {files}_fid_idx ON {files}(fid)');
    $ret[] = update_sql('CREATE INDEX {files}_vid_idx ON {files}(vid)');
  }

  // Move logs too.
  $result = db_query("SELECT nid, log FROM {book} WHERE log != ''");
  while ($row = db_fetch_object($result)) {
    db_query("UPDATE {node_revisions} SET log = '%s' WHERE vid = %d", $row->log, $row->nid);
  }

  $ret[] = update_sql("ALTER TABLE {book} DROP log");
  $ret[] = update_sql("ALTER TABLE {node} DROP teaser");
  $ret[] = update_sql("ALTER TABLE {node} DROP body");
  $ret[] = update_sql("ALTER TABLE {node} DROP format");
  $ret[] = update_sql("ALTER TABLE {node} DROP revisions");

  return $ret;
}

function system_update_147() {
  $ret = array();

  // this update is mysql only, pgsql should get it right in the first try.
  if ($GLOBALS['db_type'] == 'mysql') {
    $ret[] = update_sql("ALTER TABLE {node_revisions} DROP type");
  }

  return $ret;
}

function system_update_148() {
  $ret = array();

  // Add support for tracking users' session ids (useful for tracking anon users)
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      db_add_column($ret, 'accesslog', 'sid', 'varchar(32)', array('not null' => TRUE, 'default' => "''"));
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {accesslog} ADD sid varchar(32) NOT NULL default ''");
      break;
  }

  return $ret;
}

function system_update_149() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      db_add_column($ret, 'files', 'description', 'varchar(255)', array('not null' => TRUE, 'default' => "''"));
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {files} ADD COLUMN description VARCHAR(255) NOT NULL DEFAULT ''");
      break;
    default:
      break;
  }

  return $ret;
}

function system_update_150() {
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
        data text NOT NULL default '')");
      $ret[] = update_sql("CREATE INDEX {search_dataset}_sid_type_idx ON {search_dataset}(sid, type)");

      $ret[] = update_sql("CREATE TABLE {search_index} (
        word varchar(50) NOT NULL default '',
        sid integer NOT NULL default '0',
        type varchar(16) default NULL,
        fromsid integer NOT NULL default '0',
        fromtype varchar(16) default NULL,
        score float default NULL)");
      $ret[] = update_sql("CREATE INDEX {search_index}_sid_type_idx ON {search_index}(sid, type)");
      $ret[] = update_sql("CREATE INDEX {search_index}_fromsid_fromtype_idx ON {search_index}(fromsid, fromtype)");
      $ret[] = update_sql("CREATE INDEX {search_index}_word_idx ON {search_index}(word)");

      $ret[] = update_sql("CREATE TABLE {search_total} (
        word varchar(50) NOT NULL default '',
        count float default NULL,
        PRIMARY KEY(word))");
      break;
    default:
      break;
  }
  return $ret;
}

function system_update_151() {
  $ret = array();

  $ts = variable_get('theme_settings', null);

  // set up data array so we can loop over both sets of links
  $menus = array(0 => array('links_var' => 'primary_links',
                            'toggle_var' => 'toggle_primary_links',
                            'more_var' => 'primary_links_more',
                            'menu_name' => 'Primary links',
                            'menu_var' => 'menu_primary_menu',
                            'pid' => 0),
                 1 => array('links_var' => 'secondary_links',
                            'toggle_var' => 'toggle_secondary_links',
                            'more_var' => 'secondary_links_more',
                            'menu_name' => 'Secondary links',
                            'menu_var' => 'menu_secondary_menu',
                            'pid' => 0));

  for ($loop = 0; $loop <= 1 ; $loop ++) {
    // create new Primary and Secondary links menus
    $menus[$loop]['pid'] = db_next_id('{menu}_mid');
    $ret[] = update_sql("INSERT INTO {menu} (mid, pid, path, title, description, weight, type) " .
                         "VALUES ({$menus[$loop]['pid']}, 0, '', '{$menus[$loop]['menu_name']}', '', 0, 115)");

    // Gather links from various settings into a single array.
    $phptemplate_links = variable_get("phptemplate_". $menus[$loop]['links_var'], array());
    if (empty($phptemplate_links)) {
      $phptemplate_links = array('text' => array(), 'link' => array());
    }
    if (isset($ts) && is_array($ts)) {
      if (is_array($ts[$menus[$loop]['links_var']])) {
        $theme_links = $ts[$menus[$loop]['links_var']];
      }
      else {
        // Convert old xtemplate style links.
        preg_match_all('/<a\s+.*?href=[\"\'\s]?(.*?)[\"\'\s]?>(.*?)<\/a>/i', $ts[$menus[$loop]['links_var']], $urls);
        $theme_links['text'] = $urls[2];
        $theme_links['link'] = $urls[1];
      }
    }
    else {
      $theme_links = array('text' => array(), 'link' => array());
    }
    $links['text'] = array_merge($phptemplate_links['text'], $theme_links['text']);
    $links['link'] = array_merge($phptemplate_links['link'], $theme_links['link']);

    // insert all entries from theme links into new menus
    $num_inserted = 0;
    for ($i = 0; $i < count($links['text']); $i++) {
      if ($links['text'][$i] != "" && $links['link'][$i] != "") {
        $num_inserted ++;
        $node_unalias = db_fetch_array(db_query("SELECT src FROM {url_alias} WHERE dst = '%s'", $links['link'][$i]));
        if (isset($node_unalias) && is_array($node_unalias)) {
          $link_path = $node_unalias['src'];
        }
        else {
          $link_path = $links['link'][$i];
        }

        $mid = db_next_id('{menu}_mid');
        $ret[] = update_sql("INSERT INTO {menu} (mid, pid, path, title, description, weight, type) " .
                             "VALUES ($mid, {$menus[$loop]['pid']}, '" . db_escape_string($link_path) .
                             "', '" . db_escape_string($links['text'][$i]) .
                             "', '" . db_escape_string($links['description'][$i]) . "', 0, 118)");
      }
    }
    // delete Secondary links if not populated.
    if ($loop == 1 && $num_inserted == 0) {
      db_query("DELETE FROM {menu} WHERE mid={$menus[$loop]['pid']}");
    }

    // Set menu_primary_menu and menu_primary_menu variables if links were
    // imported. If the user had links but the toggle display was off, they
    // will need to disable the new links manually in admins/settings/menu.
    if ($num_inserted == 0) {
      variable_set($menus[$loop]['menu_var'], 0);
    }
    else {
      variable_set($menus[$loop]['menu_var'], $menus[$loop]['pid']);
    }
    variable_del('phptemplate_' .$menus[$loop]['links_var']);
    variable_del('phptemplate_'. $menus[$loop]['links_var'] .'_more');
    variable_del($menus[$loop]['toggle_var']);
    variable_del($menus[$loop]['more_var']);
    // If user has old xtemplate links in a string, leave them in the var.
    if (isset($ts) && is_array($ts) && is_array($ts[$menus[$loop]['links_var']])) {
      variable_del($menus[$loop]['links_var']);
    }
  }

  if (isset($ts) && is_array($ts)) {
    variable_set('theme_settings', $ts);
  }

  $ret[] = update_sql("UPDATE {system} SET status = 1 WHERE name = 'menu'");

  return $ret;
}

function system_update_152() {
  $ret = array();

  // Postgresql only update
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      $ret[] = update_sql("ALTER TABLE {forum} DROP shadow");
      break;
    case 'mysql':
    case 'mysqli':
      break;
  }

  return $ret;
}

function system_update_153(){
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      $ret[] = update_sql("ALTER TABLE {contact} DROP CONSTRAINT {contact}_pkey");
      $ret[] = update_sql("CREATE SEQUENCE {contact}_cid_seq");
      db_add_column($ret, 'contact', 'cid', 'int', array('not null' => TRUE, 'default' => "nextval('{contact}_cid_seq')"));
      $ret[] = update_sql("ALTER TABLE {contact} ADD PRIMARY KEY (cid)");
      $ret[] = update_sql("ALTER TABLE {contact} ADD CONSTRAINT {contact}_category_key UNIQUE (category)");
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {contact} DROP PRIMARY KEY");
      $ret[] = update_sql("ALTER TABLE {contact} ADD COLUMN cid int(11) NOT NULL PRIMARY KEY auto_increment");
      $ret[] = update_sql("ALTER TABLE {contact} ADD UNIQUE KEY category (category)");
      break;
  }
  return $ret;
}

function system_update_154() {
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      db_add_column($ret, 'contact', 'weight', 'smallint', array('not null' => TRUE, 'default' => 0));
      db_add_column($ret, 'contact', 'selected', 'smallint', array('not null' => TRUE, 'default' => 0));
      break;
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {contact} ADD COLUMN weight tinyint(3) NOT NULL DEFAULT 0");
      $ret[] = update_sql("ALTER TABLE {contact} ADD COLUMN selected tinyint(1) NOT NULL DEFAULT 0");
      break;
  }
  return $ret;
}

function system_update_155() {
  $ret = array();

  // Postgresql only update
  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      $ret[] = update_sql("DROP TABLE {cache}");
      $ret[] = update_sql("CREATE TABLE {cache} (
        cid varchar(255) NOT NULL default '',
        data bytea default '',
        expire integer NOT NULL default '0',
        created integer NOT NULL default '0',
        headers text default '',
        PRIMARY KEY (cid)
        )");
      $ret[] = update_sql("CREATE INDEX {cache}_expire_idx ON {cache}(expire)");
      break;
    case 'mysql':
    case 'mysqli':
      break;
  }

  return $ret;
}

function system_update_156() {
  $ret = array();
  $ret[] = update_sql("DELETE FROM {cache}");
  system_themes();
  return $ret;
}

function system_update_157() {
  $ret = array();
  $ret[] = update_sql("DELETE FROM {url_alias} WHERE src = 'node/feed' AND dst = 'rss.xml'");
  $ret[] = update_sql("INSERT INTO {url_alias} (src, dst) VALUES ('rss.xml', 'node/feed')");
  return $ret;
}

function system_update_158() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'mysqli':
    case 'mysql':
      $ret[] = update_sql("ALTER TABLE {old_revisions} ADD done tinyint(1) NOT NULL DEFAULT 0");
      $ret[] = update_sql("ALTER TABLE {old_revisions} ADD INDEX (done)");
      break;

    case 'pgsql':
      db_add_column($ret, 'old_revisions', 'done', 'smallint', array('not null' => TRUE, 'default' => 0));
      $ret[] = update_sql('CREATE INDEX {old_revisions}_done_idx ON {old_revisions}(done)');
      break;
  }

  return $ret;
}

/**
 * Retrieve data out of the old_revisions table and put into new revision
 * system.
 *
 * The old_revisions table is not deleted because any data which could not be
 * put into the new system is retained.
 */
function system_update_159() {
  $ret = array();

  $result = db_query_range("SELECT * FROM {old_revisions} WHERE done = 0 AND type IN ('page', 'story', 'poll', 'book', 'forum', 'blog') ORDER BY nid DESC", 0, 20);

  if (db_num_rows($result)) {
    $vid = db_next_id('{node_revisions}_vid');
    while ($node = db_fetch_object($result)) {
      $revisions = unserialize($node->revisions);
      if (isset($revisions) && is_array($revisions) && count($revisions) > 0) {
        $revisions_query = array();
        $revisions_args = array();
        $book_query = array();
        $book_args = array();
        $forum_query = array();
        $forum_args = array();
        foreach ($revisions as $version) {
          $revision = array();
          foreach ($version['node'] as $node_field => $node_value) {
            $revision[$node_field] = $node_value;
          }
          $revision['uid'] = $version['uid'];
          $revision['timestamp'] = $version['timestamp'];
          $vid++;
          $revisions_query[] = "(%d, %d, %d, '%s', '%s', '%s', '%s', %d, %d)";
          $revisions_args = array_merge($revisions_args, array($node->nid, $vid, $revision['uid'], $revision['title'], $revision['body'], $revision['teaser'], $revision['log'], $revision['timestamp'], $revision['format']));
          switch ($node->type) {
            case 'forum':
              if ($revision['tid'] > 0) {
                $forum_query[] = "(%d, %d, %d)";
                $forum_args = array_merge($forum_args, array($vid, $node->nid, $revision['tid']));
              }
              break;

            case 'book':
              $book_query[] = "(%d, %d, %d, %d)";
              $book_args = array_merge($book_args, array($vid, $node->nid, $revision['parent'], $revision['weight']));
              break;
          }
        }
        if (count($revisions_query)) {
          $revision_status = db_query("INSERT INTO {node_revisions} (nid, vid, uid, title, body, teaser, log, timestamp, format) VALUES ". implode(',', $revisions_query), $revisions_args);
        }
        if (count($forum_query)) {
          $forum_status = db_query("INSERT INTO {forum} (vid, nid, tid) VALUES ". implode(',', $forum_query), $forum_args);
        }
        if (count($book_query)) {
          $book_status = db_query("INSERT INTO {book} (vid, nid, parent, weight) VALUES ". implode(',', $book_query), $book_args);
        }
        $delete = FALSE;
        switch ($node->type) {
          case 'forum':
            if ($forum_status && $revision_status) {
              $delete = TRUE;
            }
            break;

          case 'book':
            if ($book_status && $revision_status) {
              $delete = TRUE;
            }
            break;

          default:
            if ($revision_status) {
              $delete = TRUE;
            }
            break;
        }

        if ($delete) {
          db_query('DELETE FROM {old_revisions} WHERE nid = %d', $node->nid);
        }
        else {
          db_query('UPDATE {old_revisions} SET done = 1 WHERE nid = %d', $node->nid);
        }

        switch ($GLOBALS['db_type']) {
          case 'mysqli':
          case 'mysql':
            $ret[] = update_sql("UPDATE {sequences} SET id = $vid WHERE name = '{node_revisions}_vid'");
            break;

          case 'pgsql':
            $ret[] = update_sql("SELECT setval('{node_revisions}_vid_seq', $vid)");
            break;
        }
      }
      else {
        db_query('UPDATE {old_revisions} SET done = 1 WHERE nid = %d', $node->nid);
        watchdog('php', "Recovering old revisions for node $node->nid failed.", WATCHDOG_WARNING);
      }
    }
  }

  if (db_num_rows($result) < 20) {
    $ret[] = update_sql('ALTER TABLE {old_revisions} DROP done');
  }
  else {
    $ret['#finished'] = FALSE;
  }

  return $ret;
}

function system_update_160() {
  $types = module_invoke('node', 'get_types');
  if (is_array($types)) {
    foreach($types as $type) {
      if (!is_array(variable_get("node_options_$type", array()))) {
        variable_set("node_options_$type", array());
      }
    }
  }
  return array();
}

function system_update_161() {
  variable_del('forum_icon_path');
  return array();
}

function system_update_162() {
  $ret = array();

  // PostgreSQL only update
  switch ($GLOBALS['db_type']) {
    case 'pgsql':

      $ret[] = update_sql('DROP INDEX {book}_parent');
      $ret[] = update_sql('CREATE INDEX {book}_parent_idx ON {book}(parent)');

      $ret[] = update_sql('DROP INDEX {node_comment_statistics}_timestamp_idx');
      $ret[] = update_sql('CREATE INDEX {node_comment_statistics}_last_comment_timestamp_idx ON {node_comment_statistics}(last_comment_timestamp)');

      $ret[] = update_sql('ALTER TABLE {filters} ALTER delta SET DEFAULT 0');
      $ret[] = update_sql('DROP INDEX {filters}_module_idx');

      $ret[] = update_sql('DROP INDEX {locales_target}_lid_idx');
      $ret[] = update_sql('DROP INDEX {locales_target}_lang_idx');
      $ret[] = update_sql('CREATE INDEX {locales_target}_locale_idx ON {locales_target}(locale)');

      $ret[] = update_sql('DROP INDEX {node}_created');
      $ret[] = update_sql('CREATE INDEX {node}_created_idx ON {node}(created)');
      $ret[] = update_sql('DROP INDEX {node}_changed');
      $ret[] = update_sql('CREATE INDEX {node}_changed_idx ON {node}(changed)');

      $ret[] = update_sql('DROP INDEX {profile_fields}_category');
      $ret[] = update_sql('CREATE INDEX {profile_fields}_category_idx ON {profile_fields}(category)');

      $ret[] = update_sql('DROP INDEX {url_alias}_dst_idx');
      $ret[] = update_sql('CREATE UNIQUE INDEX {url_alias}_dst_idx ON {url_alias}(dst)');

      $ret[] = update_sql('CREATE INDEX {sessions}_uid_idx ON {sessions}(uid)');
      $ret[] = update_sql('CREATE INDEX {sessions}_timestamp_idx ON {sessions}(timestamp)');

      $ret[] = update_sql('ALTER TABLE {accesslog} DROP mask');

      db_change_column($ret, 'accesslog', 'path', 'path', 'text');
      db_change_column($ret, 'accesslog', 'url', 'url', 'text');
      db_change_column($ret, 'watchdog', 'link', 'link', 'text', array('not null' => TRUE, 'default' => "''"));
      db_change_column($ret, 'watchdog', 'location', 'location', 'text', array('not null' => TRUE, 'default' => "''"));
      db_change_column($ret, 'watchdog', 'referer', 'referer', 'text', array('not null' => TRUE, 'default' => "''"));

      break;
  }

  return $ret;
}

function system_update_163() {
  $ret = array();
  if ($GLOBALS['db_type'] == 'mysql' || $GLOBALS['db_type'] == 'mysqli') {
    $ret[] = update_sql('ALTER TABLE {cache} CHANGE data data LONGBLOB');
  }
  return $ret;
}

function system_update_164() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("CREATE TABLE {poll_votes} (
          nid int(10) unsigned NOT NULL,
          uid int(10) unsigned NOT NULL default 0,
          hostname varchar(128) NOT NULL default '',
          INDEX (nid),
          INDEX (uid),
          INDEX (hostname)
      )");
      break;

    case 'pgsql':
      $ret[] = update_sql("CREATE TABLE {poll_votes} (
          nid int NOT NULL,
          uid int NOT NULL default 0,
          hostname varchar(128) NOT NULL default ''
      )");
      $ret[] = update_sql('CREATE INDEX {poll_votes}_nid_idx ON {poll_votes} (nid)');
      $ret[] = update_sql('CREATE INDEX {poll_votes}_uid_idx ON {poll_votes} (uid)');
      $ret[] = update_sql('CREATE INDEX {poll_votes}_hostname_idx ON {poll_votes} (hostname)');
      break;
  }

  $result = db_query('SELECT nid, polled FROM {poll}');
  while ($poll = db_fetch_object($result)) {
    foreach (explode(' ', $poll->polled) as $polled) {
      if ($polled[0] == '_') {
        // $polled is a user id
        db_query('INSERT INTO {poll_votes} (nid, uid) VALUES (%d, %d)', $poll->nid, substr($polled, 1, -1));
      }
      else {
        // $polled is a host
        db_query("INSERT INTO {poll_votes} (nid, hostname) VALUES (%d, '%s')", $poll->nid, $polled);
      }
    }
  }

  $ret[] = update_sql('ALTER TABLE {poll} DROP polled');

  return $ret;
}

function system_update_165() {
  $cron_last = max(variable_get('drupal_cron_last', 0), variable_get('ping_cron_last', 0));
  variable_set('cron_last', $cron_last);
  variable_del('drupal_cron_last');
  variable_del('ping_cron_last');
  return array();
}

function system_update_166() {
  $ret = array();

  $ret[] = update_sql("DROP TABLE {directory}");
  switch ($GLOBALS['db_type']) {
    case 'mysqli':
    case 'mysql':
      $ret[] = update_sql("CREATE TABLE {client} (
        cid int(10) unsigned NOT NULL auto_increment,
        link varchar(255) NOT NULL default '',
        name varchar(128) NOT NULL default '',
        mail varchar(128) NOT NULL default '',
        slogan longtext NOT NULL,
        mission longtext NOT NULL,
        users int(10) NOT NULL default '0',
        nodes int(10) NOT NULL default '0',
        version varchar(35) NOT NULL default'',
        created int(11) NOT NULL default '0',
        changed int(11) NOT NULL default '0',
        PRIMARY KEY (cid)
      )");
      $ret[] = update_sql("CREATE TABLE {client_system} (
        cid int(10) NOT NULL default '0',
        name varchar(255) NOT NULL default '',
        type varchar(255) NOT NULL default '',
        PRIMARY KEY (cid,name)
      )");
      break;

    case 'pgsql':
      $ret[] = update_sql("CREATE TABLE {client} (
        cid SERIAL,
        link varchar(255) NOT NULL default '',
        name varchar(128) NOT NULL default '',
        mail varchar(128) NOT NULL default '',
        slogan text NOT NULL default '',
        mission text NOT NULL default '',
        users integer NOT NULL default '0',
        nodes integer NOT NULL default '0',
        version varchar(35) NOT NULL default'',
        created integer NOT NULL default '0',
        changed integer NOT NULL default '0',
        PRIMARY KEY (cid)
      )");
      $ret[] = update_sql("CREATE TABLE {client_system} (
        cid integer NOT NULL,
        name varchar(255) NOT NULL default '',
        type varchar(255) NOT NULL default '',
        PRIMARY KEY (cid,name)
      )");
      break;
  }

  return $ret;
}

function system_update_167() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'mysqli':
    case 'mysql':
      $ret[] = update_sql("ALTER TABLE {vocabulary_node_types} CHANGE type type varchar(32) NOT NULL default ''");
      break;
    case 'pgsql':
      db_change_column($ret, 'vocabulary_node_types', 'type', 'type', 'varchar(32)', array('not null' => TRUE, 'default' => "''"));
      $ret[] = update_sql("ALTER TABLE {vocabulary_node_types} ADD PRIMARY KEY (vid, type)");
      break;
  }

  return $ret;
}

function system_update_168() {
  $ret = array();

  $ret[] = update_sql("ALTER TABLE {term_hierarchy} ADD PRIMARY KEY (tid, parent)");

  return $ret;
}

function system_update_169() {
  // Warn PGSQL admins if their database is set up incorrectly
  if ($GLOBALS['db_type'] == 'pgsql') {
    $encoding = db_result(db_query('SHOW server_encoding'));
    if (!in_array(strtolower($encoding), array('unicode', 'utf8'))) {
      $msg = 'Your PostgreSQL database is set up with the wrong character encoding ('. $encoding .'). It is possible it will not work as expected. It is advised to recreate it with UTF-8/Unicode encoding. More information can be found in the <a href="http://www.postgresql.org/docs/7.4/interactive/multibyte.html">PostgreSQL documentation</a>.';
      watchdog('php', $msg, WATCHDOG_WARNING);
      drupal_set_message($msg, 'status');
    }
  }

  // Note: 'access' table manually updated in update.php
  return _system_update_utf8(array(
    'accesslog', 'aggregator_category',
    'aggregator_category_feed', 'aggregator_category_item',
    'aggregator_feed', 'aggregator_item', 'authmap', 'blocks',
    'book', 'boxes', 'cache', 'comments', 'contact',
    'node_comment_statistics', 'client', 'client_system', 'files',
    'filter_formats', 'filters', 'flood', 'forum', 'history',
    'locales_meta', 'locales_source', 'locales_target', 'menu',
    'node', 'node_access', 'node_revisions', 'profile_fields',
    'profile_values', 'url_alias', 'permission', 'poll', 'poll_votes',
    'poll_choices', 'role', 'search_dataset', 'search_index',
    'search_total', 'sessions', 'sequences', 'node_counter',
    'system', 'term_data', 'term_hierarchy', 'term_node',
    'term_relation', 'term_synonym', 'users', 'users_roles', 'variable',
    'vocabulary', 'vocabulary_node_types', 'watchdog'
  ));
}

/**
 * Converts a set of tables to UTF-8 encoding.
 *
 * This update is designed to be re-usable by contrib modules and is
 * used by system_update_169().
 */
function _system_update_utf8($tables) {
  // Are we starting this update for the first time?
  if (!isset($_SESSION['update_utf8'])) {
    switch ($GLOBALS['db_type']) {
      // Only for MySQL 4.1+
      case 'mysqli':
        break;
      case 'mysql':
        if (version_compare(mysql_get_server_info($GLOBALS['active_db']), '4.1.0', '<')) {
          return array();
        }
        break;
      case 'pgsql':
        return array();
    }

    // See if database uses UTF-8 already
    global $db_url;
    $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
    $db_name = substr($url['path'], 1);
    $result = db_fetch_array(db_query('SHOW CREATE DATABASE `%s`', $db_name));
    if (preg_match('/utf8/i', array_pop($result))) {
      return array();
    }

    // Make list of tables to convert
    $_SESSION['update_utf8'] = $tables;
    // Keep track of total for progress bar
    $_SESSION['update_utf8_total'] = count($tables);
  }

  // Fetch remaining tables list and convert next table
  $list = &$_SESSION['update_utf8'];

  $ret = update_convert_table_utf8(array_shift($list));

  // Are we done?
  if (count($list) == 0) {
    unset($_SESSION['update_utf8']);
    unset($_SESSION['update_utf8_total']);
    return $ret;
  }

  // Progress percentage
  $ret['#finished'] = 1 - (count($list) / $_SESSION['update_utf8_total']);
  return $ret;
}

function system_update_170() {
  if (!variable_get('update_170_done', false)) {
    switch ($GLOBALS['db_type']) {
      case 'pgsql':
        $ret = array();
        db_change_column($ret, 'system', 'schema_version', 'schema_version', 'smallint', array('not null' => TRUE, 'default' => -1));
        break;

      case 'mysql':
      case 'mysqli':
        db_query('ALTER TABLE {system} CHANGE schema_version schema_version smallint(3) not null default -1');
        break;
    }
    // Set schema version -1 (uninstalled) for disabled modules (only affects contrib).
    db_query('UPDATE {system} SET schema_version = -1 WHERE status = 0 AND schema_version = 0');
  }
  return array();
}

function system_update_171() {
  $ret = array();
  $ret[] = update_sql('DELETE FROM {users_roles} WHERE rid IN ('. DRUPAL_ANONYMOUS_RID. ', '. DRUPAL_AUTHENTICATED_RID. ')');
  return $ret;
}

function system_update_172() {
  // Multi-part update
  if (!isset($_SESSION['system_update_172'])) {
    $_SESSION['system_update_172'] = 0;
    $_SESSION['system_update_172_max'] = db_result(db_query('SELECT MAX(cid) FROM {comments}'));
  }

  include_once './modules/comment.module';

  $limit = 20;
  $result = db_query_range("SELECT cid, thread FROM {comments} WHERE cid > %d ORDER BY cid ASC", $_SESSION['system_update_172'], 0, $limit);
  while ($comment = db_fetch_object($result)) {
    $_SESSION['system_update_172'] = $comment->cid;
    $thread = explode('.', rtrim($comment->thread, '/'));
    foreach ($thread as $i => $offset) {
      // Decode old-style comment codes: 1,2,...,9,90,91,92,...,99,990,991,...
      $thread[$i] = int2vancode((strlen($offset) - 1) * 10 + substr($offset, -1, 1));
    }
    $thread = implode('.', $thread) .'/';
    db_query("UPDATE {comments} SET thread = '%s' WHERE cid = %d", $thread, $comment->cid);
  }

  if ($_SESSION['system_update_172'] == $_SESSION['system_update_172_max']) {
    unset($_SESSION['system_update_172']);
    unset($_SESSION['system_update_172_max']);
    return array();
  }
  return array('#finished' => $_SESSION['system_update_172'] / $_SESSION['system_update_172_max']);
}

function system_update_173() {
  $ret = array();
  // State tracker to determine whether we keep a backup of the files table or not.
  $safe = TRUE;

  // PostgreSQL needs CREATE TABLE foobar _AS_ SELECT ...
  $AS = ($GLOBALS['db_type'] == 'pgsql') ? 'AS' : '';

  // Backup the files table.
  $ret[] = update_sql("CREATE TABLE {files_backup} $AS SELECT * FROM {files}");

  // Do some files table sanity checking and cleanup.
  $ret[] = update_sql('DELETE FROM {files} WHERE fid = 0');
  $ret[] = update_sql('UPDATE {files} SET vid = nid WHERE vid = 0');

  // Create a temporary table to build the new file_revisions and files tables from.
  $ret[] = update_sql("CREATE TABLE {files_tmp} $AS SELECT * FROM {files}");
  $ret[] = update_sql('DROP TABLE {files}');

  switch ($GLOBALS['db_type']) {
    case 'pgsql':
      // create file_revisions table
      $ret[] = update_sql("CREATE TABLE {file_revisions} (
        fid integer NOT NULL default 0,
        vid integer NOT NULL default 0,
        description varchar(255) NOT NULL default '',
        list smallint NOT NULL default 0,
        PRIMARY KEY (fid, vid))");
      $result = update_sql("INSERT INTO {file_revisions} SELECT DISTINCT ON (fid,vid) fid, vid, description, list FROM {files_tmp}");
      $ret[] = $result;
      if ($result['success'] === FALSE) {
        $safe = FALSE;
      }

      // Create normalized files table
      $ret[] = update_sql("CREATE TABLE {files} (
        fid SERIAL,
        nid integer NOT NULL default 0,
        filename varchar(255) NOT NULL default '',
        filepath varchar(255) NOT NULL default '',
        filemime varchar(255) NOT NULL default '',
        filesize integer NOT NULL default 0,
        PRIMARY KEY (fid))");
      $result = update_sql("INSERT INTO {files} SELECT DISTINCT ON (fid) fid, nid, filename, filepath, filemime, filesize FROM {files_tmp}");
      $ret[] = $result;
      if ($result['success'] === FALSE) {
        $safe = FALSE;
      }

      $ret[] = update_sql("SELECT setval('{files}_fid_seq', max(fid)) FROM {files}");

      break;

    case 'mysqli':
    case 'mysql':
      // create file_revisions table
      $ret[] = update_sql("CREATE TABLE {file_revisions} (
        fid int(10) unsigned NOT NULL default 0,
        vid int(10) unsigned NOT NULL default 0,
        description varchar(255) NOT NULL default '',
        list tinyint(1) unsigned NOT NULL default 0,
        PRIMARY KEY (fid, vid)
        ) /*!40100 DEFAULT CHARACTER SET utf8 */");

      // Try as you might mysql only does distinct row if you are selecting more than 1 column.
      $result = update_sql('INSERT INTO {file_revisions} SELECT DISTINCT fid , vid, description, list FROM {files_tmp}');
      $ret[] = $result;
      if ($result['success'] === FALSE) {
        $safe = FALSE;
      }

      $ret[] = update_sql("CREATE TABLE {files} (
        fid int(10) unsigned NOT NULL default 0,
        nid int(10) unsigned NOT NULL default 0,
        filename varchar(255) NOT NULL default '',
        filepath varchar(255) NOT NULL default '',
        filemime varchar(255) NOT NULL default '',
        filesize int(10) unsigned NOT NULL default 0,
        PRIMARY KEY (fid)
        ) /*!40100 DEFAULT CHARACTER SET utf8 */");
      $result = update_sql("INSERT INTO {files} SELECT DISTINCT fid, nid, filename, filepath, filemime, filesize FROM {files_tmp}");
      $ret[] = $result;
      if ($result['success'] === FALSE) {
        $safe = FALSE;
      }

      break;
  }

  $ret[] = update_sql("DROP TABLE {files_tmp}");

  // Remove original files table if all went well. Otherwise preserve it and notify user.
  if ($safe) {
    $ret[] = update_sql("DROP TABLE {files_backup}");
  }
  else {
    drupal_set_message('Normalizing files table failed. A backup of the original table called {files_backup} remains in your database.');
  }

  return $ret;
}

function system_update_174() {
  // This update (update comments system variables on upgrade) has been removed.
  return array();
}

function system_update_175() {
  $result = db_query('SELECT * FROM {url_alias}');
  while ($path = db_fetch_object($result)) {
    $path->src = urldecode($path->src);
    $path->dst = urldecode($path->dst);
    db_query("UPDATE {url_alias} SET dst = '%s', src = '%s' WHERE pid = %d", $path->dst, $path->src, $path->pid);
  }
  return array();
}

function system_update_176() {
  $ret = array();
  $ret[] = update_sql('ALTER TABLE {filter_formats} ADD UNIQUE (name)');
  return $ret;
}

function system_update_177() {
  $ret = array();
  $message_ids = array(
    'welcome_subject' => 'Welcome subject',
    'welcome_body' => 'Welcome body text',
    'approval_subject' => 'Approval subject',
    'approval_body' => 'Approval body text',
    'pass_subject' => 'Password reset subject',
    'pass_body' => 'Password reset body text',
  );
  foreach ($message_ids as $message_id => $message_text) {
    if ($admin_setting = variable_get('user_mail_'. $message_id, FALSE)) {
      // Insert newlines and escape for display as HTML
      $admin_setting = nl2br(check_plain($message_text ."\n\n". $admin_setting));
      watchdog('legacy', $admin_setting);
      $last = db_fetch_object(db_query('SELECT max(wid) AS wid FROM {watchdog}'));
      // Deleting is required, because _user_mail_text() checks for the existance of the variable.
      variable_del('user_mail_'. $message_id);
      $ret[] = array(
        'query' => strtr('The mail template %message_id has been reset to the default. The old template <a href="%url">has been saved</a>.', array('%message_id' => 'user_mail_'. $message_id, '%url' => url('admin/logs/event/'. $last->wid))),
        'success' => TRUE
      );
    }
  }
  return $ret;
}

function _update_178_url_fix($text) {
  // Key is the attribute to replace.
  $urlpatterns['href'] = "/<a[^>]+href=\"([^\"]+)/i";
  $urlpatterns['src']  = "/<img[^>]+src=\"([^\"]+)/i";

  $old = $text;
  foreach ($urlpatterns as $type => $pattern) {
    if (preg_match_all($pattern, $text, $matches)) {
      foreach ($matches[1] as $url) {
        if ($url != '' && !strstr($url, 'mailto:') && !strstr($url, '://') && !strstr($url, '../') && !strstr($url, './') && $url[0] != '/' && $url[0] != '#') {
          $text = preg_replace('|'. $type .'\s*=\s*"'. preg_quote($url) .'\s*"|', $type. '="'.base_path(). $url  .'"', $text);
        }
      }
    }
  }
  return $text != $old ? $text : FALSE;
}

function _update_178_url_formats() {
  $formats = array();

  // Any format with the HTML filter in it
  $result = db_query("SELECT format FROM {filters} WHERE module = 'filter' AND delta = 0");
  while ($format = db_fetch_object($result)) {
    $formats[$format->format] = true;
  }

  // Any format with only the linebreak filter in it
  $result = db_query("SELECT format FROM {filters} WHERE module = 'filter' AND delta = 2");
  while ($format = db_fetch_object($result)) {
    if (db_result(db_query('SELECT COUNT(*) FROM {filters} WHERE format = %d', $format->format)) == 1) {
      $formats[$format->format] = true;
    }
  }

  // Any format with 'HTML' in its name
  $result = db_query("SELECT format FROM {filter_formats} WHERE name LIKE '%HTML%'");
  while ($format = db_fetch_object($result)) {
    $formats[$format->format] = true;
  }

  return $formats;
}

/**
 * Update base paths for relative URLs in node and comment content.
 */
function system_update_178() {

  if (variable_get('clean_url', 0) == 1) {
    // Multi-part update
    if (!isset($_SESSION['system_update_178_comment'])) {
      // Check which formats need to be converted
      $formats = _update_178_url_formats();
      if (count($formats) == 0) {
        return array();
      }

      // Build format query string
      $_SESSION['formats'] = array_keys($formats);
      $_SESSION['format_string'] = '('. substr(str_repeat('%d, ', count($formats)), 0, -2) .')';

      // Begin update
      $_SESSION['system_update_178_comment'] = 0;
      $_SESSION['system_update_178_node'] = 0;
      $_SESSION['system_update_178_comment_max'] = db_result(db_query('SELECT MAX(cid) FROM {comments} WHERE format IN '. $_SESSION['format_string'], $_SESSION['formats']));
      $_SESSION['system_update_178_node_max'] = db_result(db_query('SELECT MAX(vid) FROM {node_revisions} WHERE format IN '. $_SESSION['format_string'], $_SESSION['formats']));
    }

    $limit = 20;

    // Comments
    if ($_SESSION['system_update_178_comment'] != $_SESSION['system_update_178_comment_max']) {
      $args = array_merge(array($_SESSION['system_update_178_comment']), $_SESSION['formats']);
      $result = db_query_range("SELECT cid, comment FROM {comments} WHERE cid > %d AND format IN ". $_SESSION['format_string'] .' ORDER BY cid ASC', $args, 0, $limit);
      while ($comment = db_fetch_object($result)) {
        $_SESSION['system_update_178_comment'] = $comment->cid;
        $comment->comment = _update_178_url_fix($comment->comment);
        if ($comment->comment !== FALSE) {
          db_query("UPDATE {comments} SET comment = '%s' WHERE cid = %d", $comment->comment, $comment->cid);
        }
      }
    }

    // Node revisions
    $args = array_merge(array($_SESSION['system_update_178_node']), $_SESSION['formats']);
    $result = db_query_range("SELECT vid, teaser, body FROM {node_revisions} WHERE vid > %d AND format IN ". $_SESSION['format_string'] .' ORDER BY vid ASC', $args, 0, $limit);
    while ($node = db_fetch_object($result)) {
      $_SESSION['system_update_178_node'] = $node->vid;
      $set = array();
      $args = array();

      $node->teaser = _update_178_url_fix($node->teaser);
      if ($node->teaser !== FALSE) {
        $set[] = "teaser = '%s'";
        $args[] = $node->teaser;
      }

      $node->body = _update_178_url_fix($node->body);
      if ($node->body !== FALSE) {
        $set[] = "body = '%s'";
        $args[] = $node->body;
      }

      if (count($set)) {
        $args[] = $node->vid;
        db_query('UPDATE {node_revisions} SET '. implode(', ', $set) .' WHERE vid = %d', $args);
      }

    }

    if ($_SESSION['system_update_178_comment'] == $_SESSION['system_update_178_comment_max'] &&
        $_SESSION['system_update_178_node'] == $_SESSION['system_update_178_node_max']) {
      unset($_SESSION['system_update_178_comment']);
      unset($_SESSION['system_update_178_comment_max']);
      unset($_SESSION['system_update_178_node']);
      unset($_SESSION['system_update_178_node_max']);
      return array();
    }
    else {
      // Report percentage finished
      return array('#finished' =>
        ($_SESSION['system_update_178_comment'] + $_SESSION['system_update_178_node']) /
        ($_SESSION['system_update_178_comment_max'] + $_SESSION['system_update_178_node_max'])
      );
    }
  }

  return array();
}

/**
 * Update base paths for relative URLs in custom blocks, profiles and various variables.
 */
function system_update_179() {

  if (variable_get('clean_url', 0) == 1) {
    // Multi-part update
    if (!isset($_SESSION['system_update_179_uid'])) {
      // Check which formats need to be converted
      $formats = _update_178_url_formats();
      if (count($formats) == 0) {
        return array();
      }

      // Custom Blocks (too small for multipart)
      $format_string = '('. substr(str_repeat('%d, ', count($formats)), 0, -2) .')';
      $result = db_query("SELECT bid, body FROM {boxes} WHERE format IN ". $format_string, array_keys($formats));
      while ($block = db_fetch_object($result)) {
        $block->body = _update_178_url_fix($block->body);
        if ($block->body !== FALSE) {
          db_query("UPDATE {boxes} SET body = '%s' WHERE bid = %d", $block->body, $block->bid);
        }
      }

      // Variables (too small for multipart)
      $vars = array('site_mission', 'site_footer', 'user_registration_help');
      foreach (node_get_types() as $type => $name) {
        $vars[] = $type .'_help';
      }
      foreach ($vars as $var) {
        $value = variable_get($var, NULL);
        if (!is_null($value)) {
          $value = _update_178_url_fix($value);
          if ($value !== FALSE) {
            variable_set($var, $value);
          }
        }
      }

      // See if profiles need to be updated: is the default format HTML?
      if (!isset($formats[variable_get('filter_default_format', 1)])) {
        return array();
      }
      $result = db_query("SELECT fid FROM {profile_fields} WHERE type = 'textarea'");
      $fields = array();
      while ($field = db_fetch_object($result)) {
        $fields[] = $field->fid;
      }
      if (count($fields) == 0) {
        return array();
      }

      // Begin multi-part update for profiles
      $_SESSION['system_update_179_fields'] = $fields;
      $_SESSION['system_update_179_field_string'] = '('. substr(str_repeat('%d, ', count($fields)), 0, -2) .')';
      $_SESSION['system_update_179_uid'] = 0;
      $_SESSION['system_update_179_fid'] = 0;
      $_SESSION['system_update_179_max'] = db_result(db_query('SELECT MAX(uid) FROM {profile_values} WHERE fid IN '. $_SESSION['system_update_179_field_string'], $_SESSION['system_update_179_fields']));
    }

    // Fetch next 20 profile values to convert
    $limit = 20;
    $args = array_merge(array($_SESSION['system_update_179_uid'], $_SESSION['system_update_179_fid'], $_SESSION['system_update_179_uid']), $_SESSION['system_update_179_fields']);
    $result = db_query_range("SELECT fid, uid, value FROM {profile_values} WHERE ((uid = %d AND fid > %d) OR uid > %d) AND fid IN ". $_SESSION['system_update_179_field_string'] .' ORDER BY uid ASC, fid ASC', $args, 0, $limit);
    while ($field = db_fetch_object($result)) {
      $_SESSION['system_update_179_uid'] = $field->uid;
      $_SESSION['system_update_179_fid'] = $field->fid;
      $field->value = _update_178_url_fix($field->value);
      if ($field->value !== FALSE) {
        db_query("UPDATE {profile_values} SET value = '%s' WHERE uid = %d AND fid = %d", $field->value, $field->uid, $field->fid);
      }

    }

    // Done?
    if (db_num_rows($result) == 0) {
      unset($_SESSION['system_update_179_uid']);
      unset($_SESSION['system_update_179_fid']);
      unset($_SESSION['system_update_179_max']);
      return array();
    }
    else {
      // Report percentage finished
      // (Note: make sure we complete all fields for the last user by not reporting 100% too early)
      return array('#finished' => $_SESSION['system_update_179_uid'] / ($_SESSION['system_update_179_max'] + 1));
    }
  }

  return array();
}

function system_update_180() {
  $ret = array();

  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {node} DROP PRIMARY KEY");
      $ret[] = update_sql("ALTER TABLE {node} ADD PRIMARY KEY (nid, vid)");
      $ret[] = update_sql("ALTER TABLE {node} ADD UNIQUE (vid)");
      $ret[] = update_sql("ALTER TABLE {node} ADD INDEX (nid)");

      $ret[] = update_sql("ALTER TABLE {node_counter} CHANGE nid nid INT(10) NOT NULL DEFAULT '0'");
      break;
    case 'pgsql':
      $ret[] = update_sql("ALTER TABLE {node} DROP CONSTRAINT {node}_pkey"); // Change PK
      $ret[] = update_sql("ALTER TABLE {node} ADD PRIMARY KEY (nid, vid)");
      $ret[] = update_sql('DROP INDEX {node}_vid_idx'); // Change normal index to UNIQUE index
      $ret[] = update_sql('CREATE UNIQUE INDEX {node}_vid_idx ON {node}(vid)');
      $ret[] = update_sql('CREATE INDEX {node}_nid_idx ON {node}(nid)'); // Add index on nid
      break;
  }

  return $ret;
}

function system_update_181() {
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {profile_fields} ADD autocomplete TINYINT(1) NOT NULL AFTER visibility ;");
      break;
    case 'pgsql':
      db_add_column($ret, 'profile_fields', 'autocomplete', 'smallint', array('not null' => TRUE, 'default' => 0));
      break;
  }
  return $ret;
}

/**
 * The lid field in pgSQL should not be UNIQUE, but an INDEX.
 */
function system_update_182() {
  $ret = array();

  if ($GLOBALS['db_type'] == 'pgsql') {
    $ret[] = update_sql('ALTER TABLE {locales_target} DROP CONSTRAINT {locales_target}_lid_key');
    $ret[] = update_sql('CREATE INDEX {locales_target}_lid_idx ON {locales_target} (lid)');
  }

  return $ret;
}

/**
 * Cid matching by MySQL should be case-sensitive.
 */
function system_update_183() {
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {cache} CHANGE cid cid varchar(255) BINARY NOT NULL default ''");
      break;
  }
  return $ret;
}

function system_update_184() {
  $ret = array();
  switch ($GLOBALS['db_type']) {
    case 'mysql':
    case 'mysqli':
      $ret[] = update_sql("ALTER TABLE {access} CHANGE aid aid int(10) NOT NULL AUTO_INCREMENT ");
      $ret[] = update_sql("ALTER TABLE {boxes} CHANGE bid bid int NOT NULL AUTO_INCREMENT ");
      break;
    case 'pgsql':
       // No database update required for PostgreSQL because it already uses big SERIAL numbers.
  }

  return $ret;
}
