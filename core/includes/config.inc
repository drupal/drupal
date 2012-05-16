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
 * Moves the default config supplied by a module to the live config directory.
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
    $files = glob($module_config_dir . '/' . '*.xml');
    foreach ($files as $key => $file) {
      // Load config data into the active store and write it out to the
      // file system in the drupal config directory. Note the config name
      // needs to be the same as the file name WITHOUT the extension.
      $parts = explode('/', $file);
      $file = array_pop($parts);
      $config_name = str_replace('.xml', '', $file);

      $storage = new DatabaseStorage($config_name);
      $data = FileStorage::decode(file_get_contents($module_config_dir . '/' . $file));
      $storage->write($data);
    }
  }
}

/**
 * Retrieves an iterable array which lists the children under a config 'branch'.
 *
 * Given the following configuration files:
 * - core.entity.node_type.article.xml
 * - core.entity.node_type.page.xml
 *
 * You can pass a prefix 'core.entity.node_type' and get back an array of the
 * filenames that match. This allows you to iterate through all files in a
 * branch.
 *
 * @param $prefix
 *   The prefix of the files we are searching for.
 *
 * @return
 *   An array of file names under a branch.
 */
function config_get_files_with_prefix($prefix = '') {
  $files = glob(config_get_config_directory() . '/' . $prefix . '*.xml');
  $clean_name = function ($value) {
    return basename($value, '.xml');
  };
  return array_map($clean_name, $files);
}

/**
 * @todo
 *
 * @param $prefix
 *   @todo
 *
 * @return
 *   @todo
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
 *   an XML configuration file. For @code config(book.admin) @endcode, the
 *   config object returned will contain the contents of book.admin.xml.
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
