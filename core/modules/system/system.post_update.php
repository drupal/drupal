<?php

/**
 * @file
 * Post update functions for System.
 */

/**
 * @addtogroup updates-8.0.0-beta
 * @{
 */

/**
 * Re-save all config objects with enforced dependencies.
 */
function system_post_update_fix_enforced_dependencies() {
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');
  // Iterate on all configuration entities.
  foreach ($config_factory->listAll() as $id) {
    $config = $config_factory->get($id);
    if ($config->get('dependencies.enforced') !== NULL) {
      // Resave the configuration entity.
      $entity = $config_manager->loadConfigEntityByName($id);
      $entity->save();
    }
  }

  return t('All configuration objects with enforced dependencies re-saved.');
}

/**
 * @} End of "addtogroup updates-8.0.0-beta".
 */
