<?php
// $Id: install.inc,v 1.1 2005/12/08 08:40:10 dries Exp $

define('SCHEMA', 0);
define('SCHEMA_MIN', 1);


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
 * Returns an array of availiable schema versions for a module.
 *
 * @param $module
 *   A module name.
 * @return
 *   If the module has updates, an array of available updates. Otherwise,
 *   FALSE.
 */
function drupal_get_schema_versions($module) {
  if (!($max = module_invoke($module, 'version', SCHEMA))) {
    return FALSE;
  }
  if (!($min = module_invoke($module, 'version', SCHEMA_MIN))) {
    $min = 1;
  }
  return range($min, $max);
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
