<?php
// $Id$

// TODO: still used by themes, yet doesn't return anything at the moment
function node_index() {
}

function node_get_comments($nid) {
  $comment = db_fetch_object(db_query("SELECT COUNT(c.lid) AS number FROM node n LEFT JOIN comments c ON n.nid = c.lid WHERE n.nid = '$nid' GROUP BY n.nid"));
  return $comment->number ? $comment->number : 0;
}

function node_teaser($body) {

  $size = 400;

  /*
  ** If we have a short body, return the entire body:
  */

  if (strlen($body) < $size) {
    return $body;
  }

  /*
  ** If we have a long body, try not to split paragraphs:
  */

  if ($length = strpos($body, "\n", $size)) {
    return substr($body, 0, $length + 1);
  }

  /*
  ** If we have a long body, try not to split sentences:
  */

  return substr($body, 0, strpos($body, ". ", $size) + 1);

}

function node_invoke($node, $name, $arg = 0) {
  if (is_array($node)) {
    $function = $node[type] ."_$name";
  }
  else if (is_object($node)) {
    $function = $node->type ."_$name";
  }
  else if (is_string($node)) {
    $function = $node ."_$name";
  }

  if (function_exists($function)) {
    return ($arg ? $function($node, $arg) : $function($node));
  }
}

function node_object($node) {

  if (is_array($node)) {
    foreach ($node as $key => $value) {
      $object->$key = $value;
    }
  }
  else {
    $object = $node;
  }

  return $object;
}

function node_array($node) {

  if (is_object($node)) {
    foreach ($node as $key => $value) {
      $array[$key] = $value;
    }
  }
  else {
    $array = $node;
  }

  return $array;
}

function node_load($conditions) {

  // prepare query:
  foreach ($conditions as $key => $value) {
    $cond[] = "n.". check_query($key) ." = '". check_query($value) ."'";
  }

  // retrieve the node:
  $node = db_fetch_object(db_query("SELECT n.*, u.uid, u.name FROM node n LEFT JOIN users u ON u.uid = n.uid LEFT JOIN comments c ON c.lid = n.nid WHERE ". implode(" AND ", $cond)));

  // call the node specific callback (if any):
  if ($extra = module_invoke($node->type, "load", $node)) {
    foreach ($extra as $key => $value) {
      $node->$key = $value;
    }
  }

  return $node;

}

function node_save($node, $filter) {

  $fields = array("nid", "uid", "type", "title", "teaser", "body", "status", "comment", "promote", "moderate", "created", "changed");

  foreach ($filter as $key => $value) {
    /*
    ** Only save those fields specified by the filter.  If the filter
    ** does not specify a default value, use the value of the $node's
    ** corresponding field instead.
    */

    if (is_numeric($key)) {
      $edit->$value = $node->$value;
    }
    else {
      $edit->$key = $value;
    }
  }

  $node = $edit;

  if (empty($node->nid)) {
    /*
    ** Verify a user's submission rate and avoid duplicate nodes being
    ** inserted:
    */

    throttle("node", variable_get("max_node_rate", 900));

    /*
    ** Insert a new node:
    */

    // set some required fields:
    $node->created = time();
    $node->nid = db_result(db_query("SELECT MAX(nid) + 1 FROM node"));

    // prepare the query:
    foreach ($node as $key => $value) {
      if (in_array($key, $fields)) {
        $k[] = check_query($key);
        $v[] = "'". check_query($value) ."'";
      }
    }

    // insert the node into the database:
    db_query("INSERT INTO node (". implode(", ", $k) .") VALUES (". implode(", ", $v) .")");

    // call the node specific callback (if any):
    module_invoke($node->type, "insert", $node);

    watchdog("special", "node: added '$node->title'");
  }
  else {

    /*
    ** Update an existing node:
    */

    // set some required fields:
    $node->changed = time();

    // prepare the query:
    foreach ($node as $key => $value) {
      if (in_array($key, $fields)) {
        $q[] = check_query($key) ." = '". check_query($value) ."'";
      }
    }

    // update the node in the database:
    db_query("UPDATE node SET ". implode(", ", $q) ." WHERE nid = '". check_query($node->nid) ."'");

    // call the node specific callback (if any):
    module_invoke($node->type, "update", $node);

    watchdog("special", "node: updated '$node->title'");
  }

  /*
  ** Return the node ID:
  */

  return $node->nid;

}

function node_delete($node) {

  if (is_array($node)) {
    $node = node_object($node);
  }

  // delete the node and its comments:
  db_query("DELETE FROM node WHERE nid = '$node->nid'");
  db_query("DELETE FROM comments WHERE lid = '$node->nid'");
  db_query("DELETE FROM moderate WHERE nid = '$node->nid'");

  // call the node specific callback (if any):
  module_invoke($node->type, "delete", &$node);

  watchdog("special", "node: deleted '$node->title'");
}

function node_view($node, $main = 0) {
  global $theme;

  if (is_array($node)) {
    $node = node_object($node);
  }

  /*
  ** The "view" hook can be implemented to overwrite the default function
  ** to display nodes.
  */

  if (module_hook($node->type, "view")) {
    node_invoke($node, "view", $main);
  }
  else {
    $theme->node($node, $main);
  }
}

?>
