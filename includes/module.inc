<?php

// applies function $function to every known module:
function module_iterate($function, $argument = "") {
  global $repository;
  foreach ($repository as $name=>$module) {
    $function($name, $module, $argument);
  }
}

// executes hook $hook of module $module with optional arguments:
function module_execute($module, $hook, $argument = "") {
  global $repository;
  return ($repository[$module][$hook]) ? $repository[$module][$hook]($argument) : "";
}

// returns true if module $module supports hook $hook, and false otherwise:
function module_hook($module, $hook) {
  global $repository;
  return $repository[$module][$hook];
}

// rehashes the crons:
function module_rehash_crons($name, $module) {
  if ($module["cron"]) {
    if (!db_fetch_object(db_query("SELECT * FROM crons WHERE module = '$name'"))) {
      db_query("INSERT INTO crons (module, scheduled, timestamp) VALUES ('$name', '172800', '0')");
    }
  }
  else {
    db_query("DELETE FROM crons WHERE module = '$name'");
  }
}

// rehashes the blocks:
function module_rehash_blocks($name, $module) {
  db_query("UPDATE blocks SET remove = '1' WHERE module = '$name'");
  if ($module["block"] && $blocks = $module["block"]()) {
    foreach ($blocks as $offset=>$block) {
      if (!db_fetch_object(db_query("SELECT * FROM blocks WHERE module = '$name' AND name = '$block[info]'"))) {
        db_query("INSERT INTO blocks (name, module, offset) VALUES ('$block[info]', '$name', '$offset')");
      }
      else {
        db_query("UPDATE blocks SET offset = '$offset', remove = '0' WHERE module = '$name' AND name = '$block[info]'");
      }
    }
  }
  db_query("DELETE FROM blocks WHERE module = '$name' AND remove = '1'");
}

// rehashes a module:
function module_rehash($name) {
  global $repository;

  if ($module = $repository[$name]) {
    $result = db_query("SELECT * FROM modules WHERE name = '$name'");

    if (!$object = db_fetch_object($result)) {
      db_query("INSERT INTO modules (name) VALUES ('$name')");
    }

    // rehash crons (if necessary):
    module_rehash_crons($name, $module);

    // rehash blocks (if necessary):
    module_rehash_blocks($name, $module);
  }
  else {
    // remove all reference to module:
    db_query("DELETE FROM modules WHERE name = '$name'");
    db_query("DELETE FROM blocks WHERE module = '$name'");
    db_query("DELETE FROM crons WHERE module = '$name'");
  }
}

// load modules into repository:
$handle = opendir("modules");
while ($file = readdir($handle)) {
  if ($filename = substr($file, 0, strpos($file, ".module"))) {
    include "modules/$filename.module";
    $repository[$filename] = $module;
  }
}
closedir($handle);

?>
