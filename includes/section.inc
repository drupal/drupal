<?php

function section_get() {
  $array = array();
  $result = db_query("SELECT name FROM sections");
  while ($section = db_fetch_object($result)) array_push($array, $section->name);
  return $array;
}

function section_post_threshold($section, $threshold = 5) {
  $result = db_query("SELECT post FROM sections WHERE name = '". check_input($section) ."'");
  return ($result) ? db_result($result, 0) : $threshold;
}

function section_dump_threshold($section, $threshold = - 3) {
  $result = db_query("SELECT dump FROM sections WHERE name = '". check_input($section) ."'");
  return ($result) ? db_result($result, 0) : $threshold;
}

function section_timout_threshold($section, $threshold = 10) {
  $result = db_query("SELECT timout FROM sections WHERE name = '". check_input($section) ."'");
  return ($result) ? db_result($result, 0) : $threshold;
}

?>