<?php

/**
 * @file
 * Schema API handling functions.
 */

/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * Indicates that a module has not been installed yet.
 */
const SCHEMA_UNINSTALLED = -1;

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
    $updates = [];
    foreach (\Drupal::moduleHandler()->getModuleList() as $loaded_module => $filename) {
      $updates[$loaded_module] = [];
    }

    // Prepare regular expression to match all possible defined hook_update_N().
    $regexp = '/^(?<module>.+)_update_(?<version>\d+)$/';
    $functions = get_defined_functions();
    // Narrow this down to functions ending with an integer, since all
    // hook_update_N() functions end this way, and there are other
    // possible functions which match '_update_'. We use preg_grep() here
    // since looping through all PHP functions can take significant page
    // execution time and this function is called on every administrative page
    // via system_requirements().
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
  $versions = &drupal_static(__FUNCTION__, []);

  if ($reset) {
    $versions = [];
  }

  if (!$versions) {
    if (!$versions = \Drupal::keyValue('system.schema')->getAll()) {
      $versions = [];
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
 * @param string $module
 *   The module for which the tables will be created.
 */
function drupal_install_schema($module) {
  $schema = drupal_get_module_schema($module);
  _drupal_schema_initialize($schema, $module, FALSE);

  foreach ($schema as $name => $table) {
    \Drupal::database()->schema()->createTable($name, $table);
  }
}

/**
 * Removes all tables defined in a module's hook_schema().
 *
 * @param string $module
 *   The module for which the tables will be removed.
 */
function drupal_uninstall_schema($module) {
  $tables = drupal_get_module_schema($module);
  _drupal_schema_initialize($tables, $module, FALSE);
  $schema = \Drupal::database()->schema();
  foreach ($tables as $table) {
    if ($schema->tableExists($table['name'])) {
      $schema->dropTable($table['name']);
    }
  }
}

/**
 * Returns a module's schema.
 *
 * This function can be used to retrieve a schema specification in
 * hook_schema(), so it allows you to derive your tables from existing
 * specifications.
 *
 * @param string $module
 *   The module to which the table belongs.
 * @param string $table
 *   The name of the table. If not given, the module's complete schema
 *   is returned.
 */
function drupal_get_module_schema($module, $table = NULL) {
  // Load the .install file to get hook_schema.
  module_load_install($module);
  $schema = \Drupal::moduleHandler()->invoke($module, 'schema');

  if (isset($table)) {
    if (isset($schema[$table])) {
      return $schema[$table];
    }
    return [];
  }
  elseif (!empty($schema)) {
    return $schema;
  }
  return [];
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
 * @} End of "addtogroup schemaapi".
 */
