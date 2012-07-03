<?php

/**
 * @file
 * Hooks provided by the Configuration module.
 */

/**
 * @defgroup config_hooks Configuration system hooks
 * @{
 * @todo Overall description of the configuration system.
 * @}
 */

/**
 * Create configuration upon synchronizing configuration changes.
 *
 * This callback is invoked when configuration is synchronized between storages
 * and allows a module to take over the synchronization of configuration data.
 *
 * Modules should implement this callback if they manage configuration data
 * (such as image styles, node types, or fields) which needs to be
 * prepared and passed through module API functions to properly handle a
 * configuration change.
 *
 * @param string $name
 *   The name of the configuration object.
 * @param Drupal\Core\Config\Config $new_config
 *   A configuration object containing the new configuration data.
 * @param Drupal\Core\Config\Config $old_config
 *   A configuration object containing the old configuration data.
 */
function MODULE_config_import_create($name, $new_config, $old_config) {
  // Only configurable thingies require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }
  $config_test = new ConfigTest($new_config);
  $config_test->save();
  return TRUE;
}

/**
 * Update configuration upon synchronizing configuration changes.
 *
 * This callback is invoked when configuration is synchronized between storages
 * and allows a module to take over the synchronization of configuration data.
 *
 * Modules should implement this callback if they manage configuration data
 * (such as image styles, node types, or fields) which needs to be
 * prepared and passed through module API functions to properly handle a
 * configuration change.
 *
 * @param string $name
 *   The name of the configuration object.
 * @param Drupal\Core\Config\Config $new_config
 *   A configuration object containing the new configuration data.
 * @param Drupal\Core\Config\Config $old_config
 *   A configuration object containing the old configuration data.
 */
function MODULE_config_import_change($name, $new_config, $old_config) {
  // Only configurable thingies require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }
  $config_test = new ConfigTest($new_config);
  $config_test->setOriginal($old_config);
  $config_test->save();
  return TRUE;
}

/**
 * Delete configuration upon synchronizing configuration changes.
 *
 * This callback is invoked when configuration is synchronized between storages
 * and allows a module to take over the synchronization of configuration data.
 *
 * Modules should implement this callback if they manage configuration data
 * (such as image styles, node types, or fields) which needs to be
 * prepared and passed through module API functions to properly handle a
 * configuration change.
 *
 * @param string $name
 *   The name of the configuration object.
 * @param Drupal\Core\Config\Config $new_config
 *   A configuration object containing the new configuration data.
 * @param Drupal\Core\Config\Config $old_config
 *   A configuration object containing the old configuration data.
 */
function MODULE_config_import_delete($name, $new_config, $old_config) {
  // Only configurable thingies require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }
  // @todo image_style_delete() supports the notion of a "replacement style"
  //   to be used by other modules instead of the deleted style. Essential!
  //   But that is impossible currently, since the config system only knows
  //   about deleted and added changes. Introduce an 'old_ID' key within
  //   config objects as a standard?
  $config_test = new ConfigTest($old_config);
  $config_test->delete();
  return TRUE;
}

