<?php

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Gets the randomly generated config directory name.
 *
 * @return
 *   The directory name.
 */
function config_get_config_directory() {
  global $config_directory_name;

  if ($test_prefix = drupal_valid_test_ua()) {
    // @see Drupal\simpletest\WebTestBase::setUp()
    $path = conf_path() . '/files/simpletest/' . substr($test_prefix, 10) . '/config';
  }
  else {
    $path = conf_path() . '/files/' . $config_directory_name;
  }
  return $path;
}

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
  $drupal_config_dir = config_get_config_directory();
  if (is_dir(drupal_get_path('module', $module) . '/config')) {
    $files = glob($module_config_dir . '/*.' . FileStorage::getFileExtension());
    foreach ($files as $key => $file) {
      // Load config data into the active store and write it out to the
      // file system in the drupal config directory. Note the config name
      // needs to be the same as the file name WITHOUT the extension.
      $config_name = basename($file, '.' . FileStorage::getFileExtension());

      $database_storage = new DatabaseStorage($config_name);
      $file_storage = new FileStorage($config_name);
      $file_storage->setPath($module_config_dir);
      $database_storage->write($file_storage->read());
    }
  }
}

/**
 * @todo http://drupal.org/node/1552396 renames this into config_load_all().
 */
function config_get_storage_names_with_prefix($prefix = '') {
  return DatabaseStorage::getNamesWithPrefix($prefix);
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
 * @param $class
 *   The class name of the config object to be returned. Defaults to
 *   DrupalConfig.
 *
 * @return
 *   An instance of the class specified in the $class parameter.
 *
 * @todo Replace this with an appropriate factory / ability to inject in
 *   alternate storage engines..
 */
function config($name, $class = 'Drupal\Core\Config\DrupalConfig') {
  return new $class(new DatabaseStorage($name));
}
