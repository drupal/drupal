<?php
// $Id$

/*
** Move this file to the root of your Drupal tree and access it (execute
** it) through your browser. Make sure to delete this file afterwards so
** it can not be accessed by Malicious Mallory.
*/

include "includes/common.inc";

/*
** Create sequence tables for pear-ification of MySQL
*/

/*

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
*/

/*
** Give old nodes a teaser:
**   update your source tree and database first
*/

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
      print "updated node '$node->title' ($node->type)<br />";
      break;
    default:
      print "unknown node '$node->title' ($node->type)<br />";
  }

}

?>