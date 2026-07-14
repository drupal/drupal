<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

use Drupal\Core\Extension\ModuleWeight;

/**
 * Sets weight of a particular module.
 *
 * The weight of uninstalled modules cannot be changed.
 *
 * @param string $module
 *   The name of the module (without the .module extension).
 * @param int $weight
 *   An integer representing the weight of the module.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(ModuleWeight::class)->set() instead.
 *
 * @see https://www.drupal.org/node/3595653
 */
function module_set_weight($module, $weight): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal::service(ModuleWeight::class)->set() instead. See https://www.drupal.org/node/3595653', E_USER_DEPRECATED);
  \Drupal::service(ModuleWeight::class)->set($module, $weight);
}

/**
 * Sorts the configured list of enabled modules.
 *
 * The list of enabled modules is expected to be ordered by weight and name.
 * The list is always sorted on write to avoid the overhead on read.
 *
 * @param array $data
 *   An array of module configuration data.
 *
 * @return array
 *   An array of module configuration data sorted by weight and name.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service(ModuleWeight::class)->sort() instead.
 *
 * @see https://www.drupal.org/node/3595653
 */
function module_config_sort($data) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use \Drupal::service(ModuleWeight::class)->sort() instead. See https://www.drupal.org/node/3595653', E_USER_DEPRECATED);
  return \Drupal::service(ModuleWeight::class)->sort($data);
}
