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

function category_del($id) {
  db_query("DELETE FROM category WHERE cid = '$id'");
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

// renders a form to select one or more topics:
function topic_form_select($type, $topics = array(), $size = 1) {
  foreach (topic_tree() as $tid=>$name) {
    $options .= "<OPTION VALUE=\"$tid\">$name</OPTION>\n";
  }
  return "<B>". t("Topic") .":</B><BR><SELECT NAME=\"edit[topic][]\" SIZE=\"$size\"". ($size > 1 ? "MULTIPLE" : "") .">$options</SELECT><P>\n";
}

function topic_del($id) {
  db_query("DELETE FROM topic WHERE tid = '$id'");
}

?>