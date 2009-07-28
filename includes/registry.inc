<?php
// $Id$

/**
 * @file
 * This file contains the code registry parser engine.
 */

/**
 * @defgroup registry Code registry
 * @{
 * The code registry engine.
 *
 * Drupal maintains an internal registry of all functions or classes in the
 * system, allowing it to lazy-load code files as needed (reducing the amount
 * of code that must be parsed on each request). 
 */

/**
 * @see registry_rebuild.
 */
function _registry_rebuild() {

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

  // Reset the resources cache.
  _registry_get_resource_name();

  // Get current list of modules and their files.
  $modules = drupal_system_listing('/\.module$/', 'modules', 'name', 0);
  system_get_files_database($modules, 'module');
  // Get the list of files we are going to parse.
  $files = array();
  foreach ($modules as &$module) {
    $dir = dirname($module->filepath);

    // Store the module directory for use in hook_registry_files_alter().
    $module->dir = $dir;

    // Parse the .info file for all modules, reguardless of their status so the
    // list of files can then be used in hook_registry_files_alter()
    // implementations.
    $module->info = drupal_parse_info_file($dir . '/' . $module->name . '.info');

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

  // Allow modules to manually modify the list of files before the registry
  // parses them. The $modules array provides the .info file information, which
  // includes the list of files registered to each module. Any files in the
  // list can then be added to the list of files that the registry will parse,
  // or modify attributes of a file.
  drupal_alter('registry_files', $files, $modules);
  foreach (registry_get_parsed_files() as $filename => $file) {
    // Add the file creation and modification dates to those files we have
    // already parsed.
    if (isset($files[$filename])) {
      $files[$filename]['filectime'] = $file['filectime'];
      $files[$filename]['filemtime'] = $file['filemtime'];
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
  if ($cache = cache_get('lookup_cache', 'cache_registry')) {
    $lookup_cache = $cache->data;
  }
  foreach ($lookup_cache as $key => $file) {
    // If the file for this cached resource is carried over unchanged from
    // the last registry build, then we can safely re-cache it.
    if ($file && in_array($file, array_keys($files)) && !in_array($file, $parsed_files)) {
      $unchanged_resources[$key] = $file;
    }
  }
  _registry_check_code(REGISTRY_RESET_LOOKUP_CACHE);

  module_implements(MODULE_IMPLEMENTS_CLEAR_CACHE);
  cache_clear_all('*', 'cache_registry', TRUE);

  // We have some unchanged resources, warm up the cache - no need to pay
  // for looking them up again.
  if (count($unchanged_resources) > 0) {
    cache_set('lookup_cache', $unchanged_resources, 'cache_registry');
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
 * Parse all files that have changed since the registry was last built, and save their function and class listings.
 *
 * @param $files
 *  The list of files to check and parse.
 */
function _registry_parse_files($files) {
  $parsed_files = array();
  foreach ($files as $filename => $file) {
    $filectime = filectime($filename);
    $filemtime = filemtime($filename);
    $modified_file = !isset($file['filectime']) || !isset($file['filemtime'])
                || $filectime != $file['filectime'] || $filemtime != $file['filemtime'];
    if ($modified_file) {
      $contents = file_get_contents($filename);
      $parsed_files[] = $filename;
      // We update the filectime/filemtime after we've saved the files resources
      // rather than here, so if we don't make it through this rebuild, the next
      // run will reparse the file.
      _registry_parse_file($filename, $contents, $file['module'], $file['weight']);
      db_merge('registry_file')
        ->key(array('filename' => $filename))
        ->fields(array(
          'filectime' => $filectime,
          'filemtime' => $filemtime,
        ))
        ->execute();
    }
  }
  return $parsed_files;
}

/**
 * Parse a file and save its function and class listings.
 *
 * @param $filename
 *  Name of the file we are going to parse.
 * @param $contents
 *  Contents of the file we are going to parse as a string.
 * @param $module
 *   (optional) Name of the module this file belongs to.
 * @param $weight
 *   (optional) Weight of the module.
 */
function _registry_parse_file($filename, $contents, $module = '', $weight = 0) {
  $map = &drupal_static(__FUNCTION__, array(T_FUNCTION => 'function', T_CLASS => 'class', T_INTERFACE => 'interface'));
  // Delete registry entries for this file, so we can insert the new resources.
  db_delete('registry')
    ->condition('filename', $filename)
    ->execute();
  $tokens = token_get_all($contents);
  while ($token = next($tokens)) {
    // Ignore all tokens except for those we are specifically saving.
    if (is_array($token) && isset($map[$token[0]])) {
      $type = $map[$token[0]];
      if ($resource_name = _registry_get_resource_name($tokens, $type)) {
        $suffix = '';
        // Collect the part of the function name after the module name,
        // so that we can query the registry for possible hook implementations.
        if ($type == 'function' && !empty($module)) {
          $n = strlen($module);
          if (substr($resource_name, 0, $n) == $module) {
            $suffix = substr($resource_name, $n + 1);
          }
        }
        $fields = array(
          'filename' => $filename,
          'module' => $module,
          'suffix' => $suffix,
          'weight' => $weight,
        );
        // Because some systems, such as cache, currently use duplicate function
        // names in separate files an insert query cannot be used here as it
        // would cause a key constraint violation. Instead we use a merge query.
        // In practice this should not be an issue as those systems all initialize
        // pre-registry and therefore are never loaded by the registry so it
        // doesn't matter if those records in the registry table point to one
        // filename instead of another.
        // TODO: Convert this back to an insert query after all duplicate
        // function names have been purged from Drupal.
        db_merge('registry')
          ->key(array('name' => $resource_name, 'type' => $type))
          ->fields($fields)
          ->execute();

        // We skip the body because classes may contain functions.
        _registry_skip_body($tokens);
      }
    }
  }
}

/**
 * Derive the name of the next resource in the token stream.
 *
 * When called without arguments, it resets its static cache.
 *
 * @param $tokens
 *  The collection of tokens for the current file being parsed.
 * @param $type
 *  The human-readable token name, either: "function", "class", or "interface".
 * @return
 *  The name of the resource, or FALSE if the resource has already been processed.
 */
function _registry_get_resource_name(&$tokens = NULL, $type = NULL) {
  // Keep a running list of all resources we've saved so far, so that we never
  // save one more than once.
  $resources = &drupal_static(__FUNCTION__);

  if (!isset($tokens)) {
    $resources = array();
    return;
  }
  // Determine the name of the resource.
  next($tokens); // Eat a space.
  $token = next($tokens);
  if ($token == '&') {
    $token = next($tokens);
  }
  $resource_name = $token[1];

  // Ensure that we never save it more than once.
  if (isset($resources[$type][$resource_name])) {
    return FALSE;
  }
  $resources[$type][$resource_name] = TRUE;

  return $resource_name;
}

/**
 * Skip the body of a code block, as defined by { and }.
 *
 * This function assumes that the body starts at the next instance
 * of { from the current position.
 *
 * @param $tokens
 */
function _registry_skip_body(&$tokens) {
  $num_braces = 1;

  $token = '';
  // Get to the first open brace.
  while ($token != '{' && ($token = next($tokens)));

  // Scan through the rest of the tokens until we reach the matching
  // end brace.
  while ($num_braces && ($token = next($tokens))) {
    // PHP is really logical to have three different tokens for { with
    // inconsistent names and only one for a closing brace.
    if ($token == '{' || (is_array($token) && ($token[0] == T_DOLLAR_OPEN_CURLY_BRACES || $token[0] == T_CURLY_OPEN))) {
      ++$num_braces;
    }
    elseif ($token == '}') {
      --$num_braces;
    }
    // Consume strings manually as workaround for a bug in PHP < 5.2.3 (see
    // http://drupal.org/node/368116).
    elseif ($token == '"' || $token == '`' || (is_array($token) && $token[0] == T_START_HEREDOC)) {
      $stop = is_array($token) ? T_END_HEREDOC : $token;
      while (($token = next($tokens)) && (is_array($token) ? $token[0] : $token) != $stop);
    }
  }
}

/**
 * @} End of "defgroup registry".
 */

