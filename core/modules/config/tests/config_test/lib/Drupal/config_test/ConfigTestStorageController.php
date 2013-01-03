<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestStorageController.
 */

namespace Drupal\config_test;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Config\Config;

/**
 * @todo.
 */
class ConfigTestStorageController extends ConfigStorageController {

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::importCreate().
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importCreate($name, $new_config, $old_config);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::importChange().
   */
  public function importChange($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importChange($name, $new_config, $old_config);
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigStorageController::importDelete().
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importDelete($name, $new_config, $old_config);
  }

}
