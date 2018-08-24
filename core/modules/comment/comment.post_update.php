<?php

/**
 * @file
 * Post update functions for the comment module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;

/**
 * Enable the comment admin view.
 */
function comment_post_update_enable_comment_admin_view() {
  $module_handler = \Drupal::moduleHandler();
  $entity_type_manager = \Drupal::entityTypeManager();

  // Save the comment delete action to config.
  $config_install_path = $module_handler->getModule('comment')->getPath() . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
  $storage = new FileStorage($config_install_path);
  $entity_type_manager
    ->getStorage('action')
    ->create($storage->read('system.action.comment_delete_action'))
    ->save();

  // Only create if the views module is enabled.
  if (!$module_handler->moduleExists('views')) {
    return;
  }

  // Save the comment admin view to config.
  $optional_install_path = $module_handler->getModule('comment')->getPath() . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
  $storage = new FileStorage($optional_install_path);
  $entity_type_manager
    ->getStorage('view')
    ->create($storage->read('views.view.comment'))
    ->save();
}

/**
 * Add comment settings.
 */
function comment_post_update_add_ip_address_setting() {
  $config_factory = \Drupal::configFactory();
  $settings = $config_factory->getEditable('comment.settings');
  $settings->set('log_ip_addresses', TRUE)
    ->save(TRUE);
}
