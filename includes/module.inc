<?

function module_iterate($function) {
  global $repository;
  foreach ($repository as $name=>$module) {
    $function($name, $module);
  }
}

function module_execute($module, $hook, $argument = "") {
  global $repository;
  return ($repository[$module][$hook]) ? $repository[$module][$hook]($argument) : "";
}

function module_rehash_crons($name, $module) {
  if ($module["cron"]) {
    if (!db_fetch_object(db_query("SELECT * FROM crons WHERE module = '$name'"))) {
      db_query("INSERT INTO crons (module, scheduled, timestamp) VALUES ('". check_input($name) ."', '172800', '0')");
    }    
  }
  else {
    db_query("DELETE FROM crons WHERE module = '$name'");
  }
}

function module_rehash_blocks($name, $module) {
  db_query("DELETE FROM blocks WHERE module = '$name'");  
  if ($module["block"]) {
    foreach ($blocks = $module["block"]() as $offset=>$block) {
      db_query("INSERT INTO blocks (name, module, offset) VALUES ('". check_input($block["info"]) ."', '". check_input($name) ."', '". check_input($offset) ."')");
    }
  }
}

function module_rehash($name) {
  global $repository;
  
  if ($module = $repository[$name]) {
    $result = db_query("SELECT * FROM modules WHERE name = '$name'");

    if (!$object = db_fetch_object($result)) {
      db_query("INSERT INTO modules (name) VALUES ('". check_input($name) ."')");
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
