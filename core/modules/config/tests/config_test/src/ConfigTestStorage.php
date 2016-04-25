<?php

namespace Drupal\config_test;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Config;

/**
 * @todo.
 */
class ConfigTestStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importCreate($name, $new_config, $old_config);
  }

  /**
   * {@inheritdoc}
   */
  public function importUpdate($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importUpdate($name, $new_config, $old_config);
  }

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    // Set a global value we can check in test code.
    $GLOBALS['hook_config_import'] = __METHOD__;

    return parent::importDelete($name, $new_config, $old_config);
  }

}
