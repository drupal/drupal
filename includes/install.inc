<?php

define('SCHEMA_UNINSTALLED', -1);
define('SCHEMA_INSTALLED', 0);


// The system module (Drupal core) is currently a special case
include_once './database/updates.inc';

// Include install files for each module
foreach (module_list() as $module) {
  $install_file = './'. drupal_get_path('module', $module) .'/'. $module .'.install';
  if (is_file($install_file)) {
    include_once $install_file;
  }
}


/**
 * Returns an array of available schema versions for a module.
 *
 * @param $module
 *   A module name.
 * @return
 *   If the module has updates, an array of available updates. Otherwise,
 *   FALSE.
 */
function drupal_get_schema_versions($module) {
  $functions = get_defined_functions();
  foreach ($functions['user'] as $function) {
    if (strpos($function, $module .'_update_') === 0) {
      $version = substr($function, strlen($module .'_update_'));
      if (is_numeric($version)) {
        $updates[] = $version;
      }
    }
  }
  if (count($updates) == 0) {
    return FALSE;
  }
  return $updates;
}

/**
 * Returns the currently installed schema version for a module.
 *
 * @param $module
 *   A module name.
 * @return
 *   The currently installed schema version.
 */
function drupal_get_installed_schema_version($module, $reset = FALSE) {
  static $versions;

  if ($reset) {
    unset($versions);
  }

  if (!$versions) {
    $versions = array();
    $result = db_query("SELECT name, schema_version FROM {system} WHERE type = 'module'");
    while ($row = db_fetch_object($result)) {
      $versions[$row->name] = $row->schema_version;
    }
  }

  return $versions[$module];
}

/**
 * Update the installed version information for a module.
 *
 * @param $module
 *   A module name.
 * @param $version
 *   The new schema version.
 */
function drupal_set_installed_schema_version($module, $version) {
  db_query("UPDATE {system} SET schema_version = %d WHERE name = '%s'", $version, $module);
}
