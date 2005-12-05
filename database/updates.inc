<?php
// $Id: updates.inc,v 1.151 2005/12/05 09:06:30 dries Exp $

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
 *
 * A quick guide to mysql2postgres conversion. Usually (but not allways!) you will use following sql statements:
 *
 * - Adding a key (an index):
 * mysql: ALTER TABLE {$table} ADD KEY $column ($column)
 * pgsql: CREATE INDEX {$table}_$column_idx ON {$table}($column)  // Please note the _idx "extension"
 *
 * - Adding a primary key:
 * mysql: ALTER TABLE {$table} ADD PRIMARY KEY $column ($column)
 * pgsql: ALTER TABLE {$table} ADD PRIMARY KEY ($column)
 *
 * - Dropping a primary key:
 * mysql: ALTER TABLE {$table} DROP PRIMARY KEY
 * pgsql: ALTER TABLE {$table} DROP CONSTRAINT {$table}_pkey
 *
 * - Dropping a column:
 * mysql: ALTER TABLE {$table} DROP $column
 * pgsql: ALTER TABLE {$table} RENAME $column TO $column_old  // For compatibility reasons we don't drop columns but rename them
 *
 * - Dropping an index:
 * mysql: ALTER TABLE {$table} DROP INDEX $index
 * pgsql: DROP INDEX {$table}_$column_idx                            // When index was defined by CREATE INDEX
 * pgsql: ALTER TABLE {$table} DROP CONSTRAINT {$table}_$column_key  // In case of UNIQUE($column)
 *
 * - Adding a column: (an example)
 * mysql: $ret = update_sql("ALTER TABLE {vocabulary} ADD tags tinyint(3) unsigned default '0' NOT NULL");
 * pgsql: db_add_column($ret, 'vocabulary', 'tags', 'smallint', array('default' => 0, 'not null' => TRUE));
 *
 * - Changing a column: (an example):
 * mysql: $ret[] = update_sql("ALTER TABLE {locales_source} CHANGE location location varchar(255) NOT NULL default ''");
 * pgsql: db_change_column($ret, 'locales_source', 'location', 'location', 'varchar(255)', array('not null' => TRUE, 'default' => "''"));
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
  "2005-10-15" => "update_150",
  "2005-10-23" => "update_151",
  "2005-10-28" => "update_152",
  "2005-11-03" => "update_153",
  "2005-11-14" => "update_154",
  "2005-11-27" => "update_155",
); // Please leave trailing , in the last line

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
      url varchar(255) d