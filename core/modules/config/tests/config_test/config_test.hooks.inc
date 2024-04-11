<?php

/**
 * @file
 * Fake third-party hook implementations for ConfigTest entities.
 *
 * Testing the module/hook system is not the purpose of this test helper module.
 * Therefore, this file implements hooks on behalf of config_test module for
 * config_test entity hooks themselves.
 */

use Drupal\config_test\Entity\ConfigTest;

/**
 * Implements hook_config_test_load().
 */
function config_test_config_test_load() {
  $GLOBALS['hook_config_test']['load'] = __FUNCTION__;
}

/**
 * Implements hook_ENTITY_TYPE_create() for 'config_test'.
 */
function config_test_config_test_create(ConfigTest $config_test) {
  if (\Drupal::state()->get('config_test.prepopulate')) {
    $config_test->set('foo', 'baz');
  }
  _config_test_update_is_syncing_store('create', $config_test);
}

/**
 * Implements hook_config_test_presave().
 */
function config_test_config_test_presave(ConfigTest $config_test) {
  $GLOBALS['hook_config_test']['presave'] = __FUNCTION__;
  _config_test_update_is_syncing_store('presave', $config_test);
}

/**
 * Implements hook_config_test_insert().
 */
function config_test_config_test_insert(ConfigTest $config_test) {
  $GLOBALS['hook_config_test']['insert'] = __FUNCTION__;
  _config_test_update_is_syncing_store('insert', $config_test);
}

/**
 * Implements hook_config_test_update().
 */
function config_test_config_test_update(ConfigTest $config_test) {
  $GLOBALS['hook_config_test']['update'] = __FUNCTION__;
  _config_test_update_is_syncing_store('update', $config_test);
}

/**
 * Implements hook_config_test_predelete().
 */
function config_test_config_test_predelete(ConfigTest $config_test) {
  $GLOBALS['hook_config_test']['predelete'] = __FUNCTION__;
  _config_test_update_is_syncing_store('predelete', $config_test);
}

/**
 * Implements hook_config_test_delete().
 */
function config_test_config_test_delete(ConfigTest $config_test) {
  $GLOBALS['hook_config_test']['delete'] = __FUNCTION__;
  _config_test_update_is_syncing_store('delete', $config_test);
}

/**
 * Helper function for testing hooks during configuration sync.
 *
 * @param string $hook
 *   The fired hook.
 * @param \Drupal\config_test\Entity\ConfigTest $config_test
 *   The ConfigTest entity.
 */
function _config_test_update_is_syncing_store($hook, ConfigTest $config_test) {
  $current_value = \Drupal::state()->get('config_test.store_isSyncing', FALSE);
  if ($current_value !== FALSE) {
    $current_value['global_state::' . $hook] = \Drupal::isConfigSyncing();
    $current_value['entity_state::' . $hook] = $config_test->isSyncing();
    \Drupal::state()->set('config_test.store_isSyncing', $current_value);
  }
}
