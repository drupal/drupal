<?php

/**
 * @file
 * Schema API handling functions.
 */

use Drupal\Core\Update\UpdateHookRegistry;

/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * Indicates that a module has not been installed yet.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Core\Update\UpdateHookRegistry::SCHEMA_UNINSTALLED constant
 *   instead.
 *
 * @see https://www.drupal.org/node/2444417
 */
const SCHEMA_UNINSTALLED = UpdateHookRegistry::SCHEMA_UNINSTALLED;

/**
 * Returns an array of available schema versions for a module.
 *
 * @param string $module
 *   A module name.
 *
 * @return array|bool
 *   If the module has updates, an array of available updates sorted by
 *   version. Otherwise, FALSE.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0.
 *   Use \Drupal\Core\Update\SchemaDataInterface::getAvailableUpdates() instead.
 *
 * @see https://www.drupal.org/node/2444417
 * @see \Drupal\Core\Update\UpdateHookRegistry::getAvailableUpdates()
 */
function drupal_get_schema_versions($module) {
  @trigger_error('drupal_get_schema_versions() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::getAvailableUpdates() instead. See https://www.drupal.org/node/2444417', E_USER_DEPRECATED);
  return \Drupal::service('update.update_hook_registry')->getAvailableUpdates($module);
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
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0.
 *   Use \Drupal\Core\Update\UpdateHookRegistry::getInstalledVersion() or
 *   \Drupal\Core\Update\UpdateHookRegistry::getAllInstalledVersions()
 *   instead.
 *
 * @see https://www.drupal.org/node/2444417
 * @see \Drupal\Core\Update\UpdateHookRegistry::getInstalledVersion()
 */
function drupal_get_installed_schema_version($module, $reset = FALSE, $array = FALSE) {
  @trigger_error('drupal_get_installed_schema_version() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::getInstalledVersion() or \Drupal\Core\Update\UpdateHookRegistry::getAllInstalledVersions() instead. See https://www.drupal.org/node/2444417', E_USER_DEPRECATED);
  /** @var \Drupal\Core\Update\UpdateHookRegistry $service */
  $service = \Drupal::service('update.update_hook_registry');
  if ($array) {
    return $service->getAllInstalledVersions();
  }
  return $service->getInstalledVersion((string) $module);
}

/**
 * Updates the installed version information for a module.
 *
 * @param string $module
 *   A module name.
 * @param string $version
 *   The new schema version.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0.
 *   Use \Drupal\Core\Update\UpdateHookRegistry::setInstalledVersion()
 *   instead.
 *
 * @see https://www.drupal.org/node/2444417
 * @see \Drupal\Core\Update\UpdateHookRegistry::setInstalledVersion()
 */
function drupal_set_installed_schema_version($module, $version) {
  @trigger_error('drupal_set_installed_schema_version() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Update\UpdateHookRegistry::setInstalledVersion() instead. See https://www.drupal.org/node/2444417', E_USER_DEPRECATED);
  \Drupal::service('update.update_hook_registry')->setInstalledVersion($module, $version);
}

/**
 * Creates all tables defined in a module's hook_schema().
 *
 * @param string $module
 *   The module for which the tables will be created.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/2970993
 * @see \Drupal\Core\Extension\ModuleInstaller::installSchema()
 */
function drupal_install_schema($module) {
  @trigger_error('drupal_install_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993', E_USER_DEPRECATED);
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
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/2970993
 * @see \Drupal\Core\Extension\ModuleInstaller::uninstallSchema()
 */
function drupal_uninstall_schema($module) {
  @trigger_error('drupal_uninstall_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993', E_USER_DEPRECATED);
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
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided. Testing classes could use
 *   \Drupal\TestTools\Extension\SchemaInspector for introspection.
 *
 * @see https://www.drupal.org/node/2970993
 * @see \Drupal\TestTools\Extension\SchemaInspector::getTablesSpecification()
 */
function drupal_get_module_schema($module, $table = NULL) {
  @trigger_error('drupal_get_module_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. Testing classes could use \Drupal\TestTools\Extension\SchemaInspector for introspection. See https://www.drupal.org/node/2970993', E_USER_DEPRECATED);
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
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct
 *   replacement is provided.
 *
 * @see https://www.drupal.org/node/2970993
 */
function _drupal_schema_initialize(&$schema, $module, $remove_descriptions = TRUE) {
  @trigger_error('_drupal_schema_initialize() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993', E_USER_DEPRECATED);
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
