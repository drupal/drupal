<?php

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Installs the default configuration of a given module.
 *
 * @param
 *   The name of the module we are installing.
 *
 * @todo Make this acknowledge other storage engines rather than having
 *   SQL be hardcoded.
 */
function config_install_default_config($module) {
  $module_config_dir = drupal_get_path('module', $module) . '/config';
  if (is_dir($module_config_dir)) {
    $database_storage = new DatabaseStorage();
    $module_file_storage = new FileStorage(array('directory' => $module_config_dir));

    foreach ($module_file_storage->listAll() as $config_name) {
      $data = $module_file_storage->read($config_name);
      $database_storage->write($config_name, $data);
    }
  }
}

/**
 * @todo Modules need a way to access the active store, whatever it is.
 */
function config_get_storage_names_with_prefix($prefix = '') {
  $storage = new DatabaseStorage();
  return $storage->listAll($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code config(book.admin) @endcode will return a configuration object in which
 * the book module can store its administrative settings.
 *
 * @param $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   a configuration file. For @code config(book.admin) @endcode, the config
 *   object returned will contain the contents of book.admin configuration file.
 *
 * @return Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return drupal_container()->get('config.factory')->get($name)->load();
}

