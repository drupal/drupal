<?php
// $Id$

/*
** Move this file to the root of your Drupal tree and access it (execute
** it) through your browser. Make sure to delete this file afterwards so
** it can not be accessed by Malicious Mallory.
*/

include "includes/common.inc";

/*
** If not in 'safe mode', increase the maximum execution time:
*/

if (!get_cfg_var("safe_mode")) {
  set_time_limit(180);
}

/*
** Create sequence tables for pear-ification of MySQL
*/

if ($part == 1) {

  foreach (module_list() as $name) {
    if (module_hook($name, "status")) {
      print "$name ...";
      db_query("DROP TABLE IF EXISTS ". $name ."_seq");
      db_query("CREATE TABLE ". $name ."_seq (id INTEGER UNSIGNED AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))");
      $result = db_query("SELECT MAX(". ($name == "node" ? "nid" : "lid") .") FROM $name", 1);
      $count = $result ? db_result($result, 0) : 0;
      db_query("INSERT INTO ". $name ."_seq (id) VALUES ('$count')");
      print "done ($count)<br />";
    }
  }

}

/*
** Give old nodes a teaser:
**   update your source tree and database first
*/

if ($part == 2) {
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
}

/*
** Remove old book pages:
*/

if ($part == 3) {

  // remove book pages that used to be 'expired':
  $result = db_query("SELECT n.nid, n.title FROM node n WHERE n.type = 'book' AND n.status = 0");
  while ($node = db_fetch_object($result)) {
    print "removing node $node->nid '$node->title' (dumped node)<br />";
    db_query("DELETE FROM node WHERE nid = '$node->nid'");
    db_query("DELETE FROM book WHERE nid = '$node->nid'");
    db_query("DELETE FROM comments WHERE lid = '$node->nid'");
  }
}

?>