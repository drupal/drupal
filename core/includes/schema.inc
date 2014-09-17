<?php

/**
 * @file
 * Schema API handling functions.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;

/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * Indicates that a module has not been installed yet.
 */
const SCHEMA_UNINSTALLED = -1;

/**
 * Gets the schema definition of a table, or the whole database schema.
 *
 * The returned schema will include any modifications made by any
 * module that implements hook_schema_alter().
 *
 * @param string $table
 *   The name of the table. If not given, the schema of all tables is returned.
 * @param bool $rebuild
 *   If TRUE, the schema will be rebuilt instead of retrieved from the cache.
 */
function drupal_get_schema($table = NULL, $rebuild = FALSE) {
  static $schema;

  if ($rebuild || !isset($schema)) {
    $schema = drupal_get_complete_schema($rebuild);
  }

  if (!isset($table)) {
    return $schema;
  }
  if (isset($schema[$table])) {
    return $schema[$table];
  }
  else {
    return FALSE;
  }
}

/**
 * Gets the whole database schema.
 *
 * The returned schema will include any modifications made by any
 * module that implements hook_schema_alter().
 *
 * @param bool $rebuild
 *   If TRUE, the schema will be rebuilt instead of retrieved from the cache.
 */
function drupal_get_complete_schema($rebuild = FALSE) {
  static $schema;

  if (!isset($schema) || $rebuild) {
    // Try to load the schema from cache.
    if (!$rebuild && $cached = \Drupal::cache()->get('schema')) {
      $schema = $cached->data;
    }
    // Otherwise, rebuild the schema cache.
    else {
      $schema = array();
      // Load the .install files to get hook_schema.
      \Drupal::moduleHandler()->loadAllIncludes('install');

      require_once __DIR__ . '/common.inc';
      // Invoke hook_schema for all modules.
      foreach (\Drupal::moduleHandler()->getImplementations('schema') as $module) {
        // Cast the result of hook_schema() to an array, as a NULL return value
        // would cause array_merge() to set the $schema variable to NULL as well.
        // That would break modules which use $schema further down the line.
        $current = (array) \Drupal::moduleHandler()->invoke($module, 'schema');
        // Set 'module' and 'name' keys for each table, and remove descriptions,
        // as they needlessly slow down \Drupal::cache()->get() for every single request.
        _drupal_schema_initialize($current, $module);
        $schema = array_merge($schema, $current);
      }
      \Drupal::moduleHandler()->alter('schema', $schema);

      // If the schema is empty, avoid saving it: some database engines require
      // the schema to perform queries, and this could lead to infinite loops.
      if (!empty($schema)) {
        \Drupal::cache()->set('schema', $schema, Cache::PERMANENT);
      }
    }
  }

  return $schema;
}

/**
 * Returns an array of available schema versions for a module.
 *
 * @param string $module
 *   A module name.
 *
 * @return array|bool
 *   If the module has updates, an array of available updates sorted by
 *   version. Otherwise, FALSE.
 */
function drupal_get_schema_versions($module) {
  $updates = &drupal_static(__FUNCTION__, NULL);
  if (!isset($updates[$module])) {
    $updates = array();
    foreach (\Drupal::moduleHandler()->getModuleList() as $loaded_module => $filename) {
      $updates[$loaded_module] = array();
    }

    // Prepare regular expression to match all possible defined hook_update_N().
    $regexp = '/^(?<module>.+)_update_(?<version>\d+)$/';
    $functions = get_defined_functions();
    // Narrow this down to functions ending with an integer, since all
    // hook_update_N() functions end this way, and there are other
    // possible functions which match '_update_'. We use preg_grep() here
    // instead of foreaching through all defined functions, since the loop
    // through all PHP functions can take significant page execution time
    // and this function is called on every administrative page via
    // system_requirements().
    foreach (preg_grep('/_\d+$/', $functions['user']) as $function) {
      // If this function is a module update function, add it to the list of
      // module updates.
      if (preg_match($regexp, $function, $matches)) {
        $updates[$matches['module']][] = $matches['version'];
      }
    }
    // Ensure that updates are applied in numerical order.
    foreach ($updates as &$module_updates) {
      sort($module_updates, SORT_NUMERIC);
    }
  }
  return empty($updates[$module]) ? FALSE : $updates[$module];
}

/**
 * Returns the currently installed schema version for a module.
 *
 * @param string $module
 *   A module name.
 * @param bool $reset
 *   Set to TRUE after installing or uninstalling an extension.
 * @param bool $array
 *   Set to TRUE if you want to get information about all modules in the
 *   system.
 *
 * @return string|int
 *   The currently installed schema version, or SCHEMA_UNINSTALLED if the
 *   module is not installed.
 */
function drupal_get_installed_schema_version($module, $reset = FALSE, $array = FALSE) {
  static $versions = array();

  if ($reset) {
    $versions = array();
  }

  if (!$versions) {
    if (!$versions = \Drupal::keyValue('system.schema')->getAll()) {
      $versions = array();
    }
  }

  if ($array) {
    return $versions;
  }
  else {
    return isset($versions[$module]) ? $versions[$module] : SCHEMA_UNINSTALLED;
  }
}

/**
 * Updates the installed version information for a module.
 *
 * @param string $module
 *   A module name.
 * @param string $version
 *   The new schema version.
 */
function drupal_set_installed_schema_version($module, $version) {
  \Drupal::keyValue('system.schema')->set($module, $version);
  // Reset the static cache of module schema versions.
  drupal_get_installed_schema_version(NULL, TRUE);
}

/**
 * Creates all tables defined in a module's hook_schema().
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param string $module
 *   The module for which the tables will be created.
 */
function drupal_install_schema($module) {
  $schema = drupal_get_schema_unprocessed($module);
  _drupal_schema_initialize($schema, $module, FALSE);

  foreach ($schema as $name => $table) {
    db_create_table($name, $table);
  }
}

/**
 * Removes all tables defined in a module's hook_schema().
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param string $module
 *   The module for which the tables will be removed.
 *
 * @return array
 *   An array of arrays with the following key/value pairs:
 *    - success: a boolean indicating whether the query succeeded.
 *    - query: the SQL query(s) executed, passed through
 *      \Drupal\Component\Utility\String::checkPlain().
 */
function drupal_uninstall_schema($module) {
  $schema = drupal_get_schema_unprocessed($module);
  _drupal_schema_initialize($schema, $module, FALSE);

  foreach ($schema as $table) {
    if (db_table_exists($table['name'])) {
      db_drop_table($table['name']);
    }
  }
}

/**
 * Returns the unprocessed and unaltered version of a module's schema.
 *
 * Use this function only if you explicitly need the original
 * specification of a schema, as it was defined in a module's
 * hook_schema(). No additional default values will be set,
 * hook_schema_alter() is not invoked and these unprocessed
 * definitions won't be cached.
 *
 * This function can be used to retrieve a schema specification in
 * hook_schema(), so it allows you to derive your tables from existing
 * specifications.
 *
 * It is also used by drupal_install_schema() and
 * drupal_uninstall_schema() to ensure that a module's tables are
 * created exactly as specified without any changes introduced by a
 * module that implements hook_schema_alter().
 *
 * @param string $module
 *   The module to which the table belongs.
 * @param string $table
 *   The name of the table. If not given, the module's complete schema
 *   is returned.
 */
function drupal_get_schema_unprocessed($module, $table = NULL) {
  // Load the .install file to get hook_schema.
  module_load_install($module);
  $schema = \Drupal::moduleHandler()->invoke($module, 'schema');

  if (isset($table)) {
    if (isset($schema[$table])) {
      return $schema[$table];
    }
    return array();
  }
  elseif (!empty($schema)) {
    return $schema;
  }
  return array();
}

/**
 * Fills in required default values for table definitions from hook_schema().
 *
 * @param array $schema
 *   The schema definition array as it was returned by the module's
 *   hook_schema().
 * @param string $module
 *   The module for which hook_schema() was invoked.
 * @param bool $remove_descriptions
 *   (optional) Whether to additionally remove 'description' keys of all tables
 *   and fields to improve performance of serialize() and unserialize().
 *   Defaults to TRUE.
 */
function _drupal_schema_initialize(&$schema, $module, $remove_descriptions = TRUE) {
  // Set the name and module key for all tables.
  foreach ($schema as $name => &$table) {
    if (empty($table['module'])) {
      $table['module'] = $module;
    }
    if (!isset($table['name'])) {
      $table['name'] = $name;
    }
    if ($remove_descriptions) {
      unset($table['description']);
      foreach ($table['fields'] as &$field) {
        unset($field['description']);
      }
    }
  }
}

/**
 * Typecasts values to proper datatypes.
 *
 * MySQL PDO silently casts, e.g. FALSE and '' to 0, when inserting the value
 * into an integer column, but PostgreSQL PDO does not. Look up the schema
 * information and use that to correctly typecast the value.
 *
 * @param array $info
 *   An array describing the schema field info.
 * @param mixed $value
 *   The value to be converted.
 *
 * @return mixed
 *   The converted value.
 */
function drupal_schema_get_field_value(array $info, $value) {
  // Preserve legal NULL values.
  if (isset($value) || !empty($info['not null'])) {
    if ($info['type'] == 'int' || $info['type'] == 'serial') {
      $value = (int) $value;
    }
    elseif ($info['type'] == 'float') {
      $value = (float) $value;
    }
    elseif (!is_array($value)) {
      $value = (string) $value;
    }
  }
  return $value;
}

/**
 * @} End of "addtogroup schemaapi".
 */
