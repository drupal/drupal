<?php

function _section_get($field, $value) {
  return db_query("SELECT * FROM section WHERE $field = '$value'");
}

function section_get_object($field, $value) {
  return db_fetch_object(_section_get($field, $value));
}

function section_get_array($field, $value) {
  return db_fetch_array(_section_get($field, $value));
}

function section_save($edit) {
  if ($edit["sid"]) db_query("UPDATE section SET name = '". check_input($edit[name]) ."', pid = '". check_input($edit[pid]) ."' WHERE sid = $edit[sid]", 1);
  else db_query("INSERT INTO section (name, pid) VALUES ('". check_input($edit[name])."', '". check_input($edit[pid]) ."')", 1);

  $sid = ($edit[sid] ? $edit[sid] : db_insert_id());

  db_query("DELETE FROM section_type WHERE sid = '$sid'", 1);
  foreach ($edit[type] as $key=>$type) db_query("INSERT INTO section_type (sid, type) VALUES ('". check_input($sid) ."', '". check_input($type) ."')", 1);
}

function section_tree($parent = 0, $name = "", $tree = array()) {
  $result = db_query("SELECT * FROM section WHERE pid = '$parent'");
  while ($section = db_fetch_object($result)) {
    $tree[$section->sid] = ($name ? "$name - $section->name" : $section->name);
    $tree = section_tree($section->sid, $tree[$section->sid], $tree);
  }
  return $tree;
}

///// old /////

function section_get() {
  $array = array();
  $result = db_query("SELECT name FROM sections");
  while ($section = db_fetch_object($result)) array_push($array, $section->name);
  return $array;
}

function section_post_threshold($section, $default) {
  $section = db_fetch_object(db_query("SELECT post AS threshold FROM sections WHERE name = '". check_input($section) ."'"));
  return $section->threshold ? $section->threshold : $default;
}

function section_dump_threshold($section, $default) {
  $section = db_fetch_object(db_query("SELECT dump AS threshold FROM sections WHERE name = '". check_input($section) ."'"));
  return $section->threshold ? $section->threshold : $default;
}

function section_timout_threshold($section, $default) {
  $section = db_fetch_object(db_query("SELECT timout AS threshold FROM sections WHERE name = '". check_input($section) ."'"));
  return $section->threshold ? $section->threshold : $default;
}

?>