<?php

// ----- category -----

function _category_get($field, $value) {
  return db_query("SELECT * FROM category WHERE $field = '$value'");
}

// returns the category object where $field = $value:
function category_get_object($field, $value) {
  return db_fetch_object(_category_get($field, $value));
}

// returns the category array where $field = $value:
function category_get_array($field, $value) {
  return db_fetch_array(_category_get($field, $value));
}

// save a category:
function category_save($edit) {
  if (!$edit[cid]) $edit[cid] = db_insert_id(db_query("INSERT INTO category (name) VALUES ('". check_input($edit[name])."')"));
  foreach ($edit as $key=>$value) db_query("UPDATE category SET $key = '". check_input($value) ."' WHERE cid = '$edit[cid]'");
}


// delete category $cid:
function category_del($cid) {
  db_query("DELETE FROM category WHERE cid = '". check_input($cid) ."'");
  db_query("UPDATE node SET cid = 0 WHERE cid = '". check_input($cid) ."'");
}

// return post threshold:
function category_post_threshold($cid) {
  $category = db_fetch_object(db_query("SELECT post AS threshold FROM category WHERE cid = '". check_input($cid) ."'"));
  return $category->threshold;
}

// return dump threshold:
function category_dump_threshold($cid) {
  $category = db_fetch_object(db_query("SELECT dump AS threshold FROM category WHERE cid = '". check_input($cid) ."'"));
  return $category->threshold;
}

// return expiration threshold:
function category_expire_threshold($cid) {
  $category = db_fetch_object(db_query("SELECT expire AS threshold FROM category WHERE cid = '". check_input($cid) ."'"));
  return $category->threshold;
}

// return default comment status of category $cid:
function category_comment($cid) {
  $category = category_get_object("cid", $cid);
  return $category->comment ? $category->comment : 0;
}

// return default promote status of category $cid:
function category_promote($cid) {
  $category = category_get_object("cid", $cid);
  return $category->promote ? $category->promote : 0;
}

// return default submission status of category $cid:
function category_submission($cid) {
  $category = category_get_object("cid", $cid);
  return $category->submission ? $category->submission : 0;
}

// return linked string with name of category $cid:
function category_name($cid) {
  $category = category_get_object("cid", $cid);
  return ($category) ? "<A HREF=\"index.php?category=$category->cid\">$category->name</A>" : "";
}

function category_form_select($type, $edit = array(), $size = 1) {
  $result = db_query("SELECT * FROM category WHERE type = '$type'");
  while ($category = db_fetch_object($result)) {
    $options .= "<OPTION VALUE=\"$category->cid\"". ($edit[cid] == $category->cid ? "SELECTED" : "") .">". check_select($category->name) ."</OPTION>";
  }
  return "<SELECT NAME=\"edit[cid]\" SIZE=\"$size\"". ($size > 1 ? "MULTIPLE" : "") .">$options</SELECT>\n";
}

// ----- topic -----

function _topic_get($field, $value) {
  return db_query("SELECT * FROM topic WHERE $field = '$value'");
}

// returns the topic object where $field = $value:
function topic_get_object($field, $value) {
  return db_fetch_object(_topic_get($field, $value));
}

// returns the topic array where $field = $value:
function topic_get_array($field, $value) {
  return db_fetch_array(_topic_get($field, $value));
}

// save a topic:
function topic_save($edit) {
  if (!$edit[tid]) $edit[tid] = db_insert_id(db_query("INSERT INTO topic (name) VALUES ('". check_input($edit[name])."')"));
  foreach ($edit as $key=>$value) db_query("UPDATE topic SET $key = '". check_input($value) ."' WHERE tid = '$edit[tid]'");
}

// returns a sorted tree-representation of all topics:
function topic_tree($parent = 0, $name = "", $tree = array()) {
  $result = db_query("SELECT * FROM topic WHERE pid = '$parent' ORDER BY name");
  while ($topic = db_fetch_object($result)) {
    $tree[$topic->tid] = ($name ? "$name - $topic->name" : $topic->name);
    $tree = topic_tree($topic->tid, $tree[$topic->tid], $tree);
  }
  return $tree;
}

// delete topic $tid:
function topic_del($tid) {
  db_query("DELETE FROM topic WHERE tid = '". check_input($tid) ."'");
  db_query("UPDATE node SET tid = 0 WHERE tid = '". check_input($tid) ."'");
}

// return linked string with name of topic $tid:
function topic_name($tid, $name = 0) {
  $topic = topic_get_object("tid", $tid);
  $name = $name ? "<A HREF=\"index.php?topic=$topic->tid\">$topic->name</A> - $name" : "<A HREF=\"index.php?topic=$topic->tid\">$topic->name</A>";
  return ($topic->pid) ? topic_name($topic->pid, $name) : $name;
}

// return moderators for topic $tid:
function topic_moderate($tid) {
  $topic = topic_get_object("tid", $tid);
  return $topic->moderate;
}

// renders a HTML form to select one or more topics:
function topic_form_select($edit = array(), $size = 1) {
  foreach (topic_tree() as $tid=>$name) {
    $options .= "<OPTION VALUE=\"$tid\"". ($edit[tid] == $tid ? "SELECTED" : "") .">". check_select($name) ."</OPTION>";
  }
  return "<SELECT NAME=\"edit[tid]\" SIZE=\"$size\"". ($size > 1 ? "MULTIPLE" : "") .">$options</SELECT>\n";
}

// ----- structure -----

// add node $nid to category $cid and topic $tid:
function structure_save($nid, $cid, $tid) {
  category_node($nid, $cid);
  topic_node($nid, $tid);
}

// render a HMTL selection form:
function structure_form($type, $edit = array(), $size = 1) {
  $output .= "<B>Category and topic:</B><BR>\n";
  $output .= category_form_select($type, $edit, $size) ." ". topic_form_select($edit, $size) ."<BR>";
  $output .= "<SMALL><I>". t("Select the category and the topic this submission belongs in.") ."</I></SMALL><P>";
  return $output;
}

?>