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
function hook_config_import_create($name, $new_config, $old_config) {
  // Only configurable entities require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }
  $config_test = entity_create('config_test', $new_config->get());
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
function hook_config_import_change($name, $new_config, $old_config) {
  // Only configurable entities require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }

  // @todo Make this less ugly.
  list($entity_type) = explode('.', $name);
  $entity_info = entity_get_info($entity_type);
  $id = substr($name, strlen($entity_info['config prefix']) + 1);
  $config_test = entity_load('config_test', $id);

  $config_test->original = clone $config_test;
  foreach ($old_config->get() as $property => $value) {
    $config_test->original->$property = $value;
  }

  foreach ($new_config->get() as $property => $value) {
    $config_test->$property = $value;
  }

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
function hook_config_import_delete($name, $new_config, $old_config) {
  // Only configurable entities require custom handling. Any other module
  // settings can be synchronized directly.
  if (strpos($name, 'config_test.dynamic.') !== 0) {
    return FALSE;
  }
  // @todo image_style_delete() supports the notion of a "replacement style"
  //   to be used by other modules instead of the deleted style. Essential!
  //   But that is impossible currently, since the config system only knows
  //   about deleted and added changes. Introduce an 'old_ID' key within
  //   config objects as a standard?
  list($entity_type) = explode('.', $name);
  $entity_info = entity_get_info($entity_type);
  $id = substr($name, strlen($entity_info['config prefix']) + 1);
  config_test_delete($id);
  return TRUE;
}

