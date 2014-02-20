<?php

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Gets configuration object names starting with a given prefix.
 *
 * @deprecated Deprecated since Drupal 8.x-dev, to be removed in Drupal 8.0.
 *   Use \Drupal::configFactory()->listAll() instead.
 */
function config_get_storage_names_with_prefix($prefix = '') {
  return \Drupal::configFactory()->listAll($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code \Drupal::config('book.admin') @endcode will return a configuration
 * object in which the book module can store its administrative settings.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *  Use \Drupal::config().
 *
 * @param string $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
 *   the config object returned will contain the contents of book.admin
 *   configuration file.
 *
 * @return \Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return \Drupal::config($name);
}

/**
 * Returns the typed config manager service.
 *
 * Use the typed data manager service for creating typed configuration objects.
 *
 * @deprecated Deprecated since Drupal 8.x-dev, to be removed in Drupal 8.0.
 *   Use \Drupal::service('config.typed') instead.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager::create()
 *
 * @return \Drupal\Core\Config\TypedConfigManager
 */
function config_typed() {
  return \Drupal::service('config.typed');
}

