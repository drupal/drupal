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
  db_query("DELETE FROM node_category WHERE cid = '". check_input($cid) ."'");
}

function category_node($nid, $cid) {
  db_query("INSERT INTO node_category (nid, cid) VALUES ('". check_input($nid) ."', '". check_input($cid) ."')", 1);
}

function category_form_select($type, $edit = array(), $size = 1) {
  $result = db_query("SELECT * FROM category WHERE type = '$type'");
  while ($category = db_fetch_object($result)) {
    $options .= "<OPTION VALUE=\"$category->cid\"". ($edit[$category->cid] ? "SELECTED" : "") .">". check_select($category->name) ."</OPTION>";
  }
  return "<SELECT NAME=\"category[]\" SIZE=\"$size\"". ($size > 1 ? "MULTIPLE" : "") .">$options</SELECT>\n";
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
  db_query("DELETE FROM node_topic WHERE tid = '". check_input($tid) ."'");
}

// add node $nid to topic $tid:
function topic_node($nid, $tid) {
  db_query("INSERT INTO node_topic (nid, tid) VALUES ('". check_input($nid) ."', '". check_input($tid) ."')", 1);
}

// renders a HTML form to select one or more topics:
function topic_form_select($edit = array(), $size = 1) {
  foreach (topic_tree() as $tid=>$name) {
    $options .= "<OPTION VALUE=\"$tid\"". ($edit[$tid] ? "SELECTED" : "") .">". check_select($name) ."</OPTION>";
  }
  return "<SELECT NAME=\"topic[]\" SIZE=\"$size\"". ($size > 1 ? "MULTIPLE" : "") .">$options</SELECT>\n";
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
  $output .= "<SMALL><I>". t("Select the category and the topic this sumbission belongs in.") ."</I></SMALL><P>";
  return $output;
}

?>