<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestStorage.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Config;

/**
 * @todo.
 */
class ConfigTestStorage extends ConfigEntityStorage {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityStorage::importCreate().
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importCreate($name, $new_config, $old_config);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityStorage::importUpdate().
   */
  public function importUpdate($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importUpdate($name, $new_config, $old_config);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityStorage::importDelete().
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importDelete($name, $new_config, $old_config);
  }

}
