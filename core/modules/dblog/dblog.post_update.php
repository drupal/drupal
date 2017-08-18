<?php

/**
 * @file
 * Post update functions for the Database Logging module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\views\Entity\View;

/**
 * Replace 'Recent log messages' with a view.
 */
function dblog_post_update_convert_recent_messages_to_view() {
  // Only create if the views module is enabled and the watchdog view doesn't
  // exist.
  if (\Drupal::moduleHandler()->moduleExists('views')) {
    if (!View::load('watchdog')) {
      // Save the watchdog view to config.
      $module_handler = \Drupal::moduleHandler();
      $optional_install_path = $module_handler->getModule('dblog')->getPath() . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      $storage = new FileStorage($optional_install_path);

      \Drupal::entityTypeManager()
        ->getStorage('view')
        ->create($storage->read('views.view.watchdog'))
        ->save();

      return t('The watchdog view has been created.');
    }

    return t("The watchdog view already exists and was not replaced. To replace the 'Recent log messages' with a view, rename the watchdog view and uninstall and install the 'Database Log' module");
  }
}
