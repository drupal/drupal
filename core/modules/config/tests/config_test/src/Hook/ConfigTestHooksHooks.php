<?php

declare(strict_types=1);

namespace Drupal\config_test\Hook;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_test.
 */
class ConfigTestHooksHooks {

  /**
   * Implements hook_ENTITY_TYPE_load().
   */
  #[Hook('config_test_load')]
  public function configTestLoad(): void {
    $GLOBALS['hook_config_test']['load'] = 'config_test_config_test_load';
  }

  /**
   * Implements hook_ENTITY_TYPE_create() for 'config_test'.
   */
  #[Hook('config_test_create')]
  public function configTestCreate(ConfigTest $config_test): void {
    if (\Drupal::state()->get('config_test.prepopulate')) {
      $config_test->set('foo', 'baz');
    }
    $this->updateIsSyncingStore('create', $config_test);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_test_presave')]
  public function configTestPresave(ConfigTest $config_test): void {
    $GLOBALS['hook_config_test']['presave'] = 'config_test_config_test_presave';
    $this->updateIsSyncingStore('presave', $config_test);
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('config_test_insert')]
  public function configTestInsert(ConfigTest $config_test): void {
    $GLOBALS['hook_config_test']['insert'] = 'config_test_config_test_insert';
    $this->updateIsSyncingStore('insert', $config_test);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('config_test_update')]
  public function configTestUpdate(ConfigTest $config_test): void {
    $GLOBALS['hook_config_test']['update'] = 'config_test_config_test_update';
    $this->updateIsSyncingStore('update', $config_test);
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete().
   */
  #[Hook('config_test_predelete')]
  public function configTestPredelete(ConfigTest $config_test): void {
    $GLOBALS['hook_config_test']['predelete'] = 'config_test_config_test_predelete';
    $this->updateIsSyncingStore('predelete', $config_test);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('config_test_delete')]
  public function configTestDelete(ConfigTest $config_test): void {
    $GLOBALS['hook_config_test']['delete'] = 'config_test_config_test_delete';
    $this->updateIsSyncingStore('delete', $config_test);
  }

  /**
   * Helper function for testing hooks during configuration sync.
   *
   * @param string $hook
   *   The fired hook.
   * @param \Drupal\config_test\Entity\ConfigTest $config_test
   *   The ConfigTest entity.
   */
  protected function updateIsSyncingStore($hook, ConfigTest $config_test) {
    $current_value = \Drupal::state()->get('config_test.store_isSyncing', FALSE);
    if ($current_value !== FALSE) {
      $current_value['global_state::' . $hook] = \Drupal::isConfigSyncing();
      $current_value['entity_state::' . $hook] = $config_test->isSyncing();
      \Drupal::state()->set('config_test.store_isSyncing', $current_value);
    }
  }

}
