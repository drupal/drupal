<?php

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