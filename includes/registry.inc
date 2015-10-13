<?php

/**
 * @file
 * This file contains the code registry parser engine.
 */

/**
 * @defgroup registry Code registry
 * @{
 * The code registry engine.
 *
 * Drupal maintains an internal registry of all interfaces or classes in the
 * system, allowing it to lazy-load code files as needed (reducing the amount
 * of code that must be parsed on each request).
 */

/**
 * Does the work for registry_update().
 */
function _registry_update() {

  // The registry serves as a central autoloader for all classes, including
  // the database query builders. However, the registry rebuild process
  // requires write ability to the database, which means having access to the
  // query builders that require the registry in order to be loaded. That
  // causes a fatal race condition. Therefore we manually include the
  // appropriate query builders for the currently active database before the
  // registry rebuild process runs.
  $connection_info = Database::getConnectionInfo();
  $driver = $connection_info['default']['driver'];
  require_once DRUPAL_ROOT . '/includes/database/query.inc';
  require_once DRUPAL_ROOT . '/includes/database/select.inc';
  require_once DRUPAL_ROOT . '/includes/database/' . $driver . '/query.inc';

  // Get current list of modules and their files.
  $modules = db_query("SELECT * FROM {system} WHERE type = 'module'")->fetchAll();
  // Get the list of files we are going to parse.
  $files = array();
  foreach ($modules as &$module) {
    $module->info = unserialize($module->info);
    $dir = dirname($module->filename);

    // Store the module directory for use in hook_registry_files_alter().
    $module->dir = $dir;

    if ($module->status) {
      // Add files for enabled modules to the registry.
      foreach ($module->info['files'] as $file) {
        $files["$dir/$file"] = array('module' => $module->name, 'weight' => $module->weight);
      }
    }
  }
  foreach (file_scan_directory('includes', '/\.inc$/') as $filename => $file) {
    $files["$filename"] = array('module' => '', 'weight' => 0);
  }

  $transaction = db_transaction();
  try {
    // Allow modules to manually modify the list of files before the registry
    // parses them. The $modules array provides the .info file information, which
    // includes the list of files registered to each module. Any files in the
    // list can then be added to the list of files that the registry will parse,
    // or modify attributes of a file.
    drupal_alter('registry_files', $files, $modules);
    foreach (registry_get_parsed_files() as $filename => $file) {
      // Add the hash for those files we have already parsed.
      if (isset($files[$filename])) {
        $files[$filename]['hash'] = $file['hash'];
      }
      else {
        // Flush the registry of resources in files that are no longer on disc
        // or are in files that no installed modules require to be parsed.
        db_delete('registry')
          ->condition('filename', $filename)
          ->execute();
        db_delete('registry_file')
          ->condition('filename', $filename)
          ->execute();
      }
    }
    $parsed_files = _registry_parse_files($files);

    $unchanged_resources = array();
    $lookup_cache = array();
    if ($cache = cache_get('lookup_cache', 'cache_bootstrap')) {
      $lookup_cache = $cache->data;
    }
    foreach ($lookup_cache as $key => $file) {
      // If the file for this cached resource is carried over unchanged from
      // the last registry build, then we can safely re-cache it.
      if ($file && in_array($file, array_keys($files)) && !in_array($file, $parsed_files)) {
        $unchanged_resources[$key] = $file;
      }
    }
    module_implements('', FALSE, TRUE);
    _registry_check_code(REGISTRY_RESET_LOOKUP_CACHE);
  }
  catch (Exception $e) {
    $transaction->rollback();
    watchdog_exception('registry', $e);
    throw $e;
  }

  // We have some unchanged resources, warm up the cache - no need to pay
  // for looking them up again.
  if (count($unchanged_resources) > 0) {
    cache_set('lookup_cache', $unchanged_resources, 'cache_bootstrap');
  }
}

/**
 * Return the list of files in registry_file
 */
function registry_get_parsed_files() {
  $files = array();
  // We want the result as a keyed array.
  $files = db_query("SELECT * FROM {registry_file}")->fetchAllAssoc('filename', PDO::FETCH_ASSOC);
  return $files;
}

/**
 * Parse all changed files and save their interface and class listings.
 *
 * Parse all files that have changed since the registry was last built, and save
 * their interface and class listings.
 *
 * @param $files
 *  The list of files to check and parse.
 */
function _registry_parse_files($files) {
  $parsed_files = array();
  foreach ($files as $filename => $file) {
    if (file_exists($filename)) {
      $hash = hash_file('sha256', $filename);
      if (empty($file['hash']) || $file['hash'] != $hash) {
        $file['hash'] = $hash;
        $parsed_files[$filename] = $file;
      }
    }
  }
  foreach ($parsed_files as $filename => $file) {
    _registry_parse_file($filename, file_get_contents($filename), $file['module'], $file['weight']);
    db_merge('registry_file')
      ->key(array('filename' => $filename))
      ->fields(array(
        'hash' => $file['hash'],
      ))
      ->execute();
  }
  return array_keys($parsed_files);
}

/**
 * Parse a file and save its interface and class listings.
 *
 * @param $filename
 *   Name of the file we are going to parse.
 * @param $contents
 *   Contents of the file we are going to parse as a string.
 * @param $module
 *   (optional) Name of the module this file belongs to.
 * @param $weight
 *   (optional) Weight of the module.
 */
function _registry_parse_file($filename, $contents, $module = '', $weight = 0) {
  if (preg_match_all('/^\s*(?:abstract|final)?\s*(class|interface|trait)\s+([a-zA-Z0-9_]+)/m', $contents, $matches)) {
    foreach ($matches[2] as $key => $name) {
      db_merge('registry')
        ->key(array(
          'name' => $name,
          'type' => $matches[1][$key],
        ))
        ->fields(array(
          'filename' => $filename,
          'module' => $module,
          'weight' => $weight,
        ))
        ->execute();
    }
    // Delete any resources for this file where the name is not in the list
    // we just merged in.
    db_delete('registry')
      ->condition('filename', $filename)
      ->condition('name', $matches[2], 'NOT IN')
      ->execute();
  }
}

/**
 * @} End of "defgroup registry".
 */
