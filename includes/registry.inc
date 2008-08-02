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
 * of code that must be parsed on each request). The list of included files is
 * cached per menu callback for subsequent loading by the menu router. This way,
 * a given page request will have all the code it needs but little else, minimizing
 * time spent parsing unneeded code.
 */

/**
 * @see registry_rebuild.
 */
function _registry_rebuild() {
  // Reset the resources cache.
  _registry_get_resource_name();
  // Get the list of files we are going to parse.
  $files = array();
  foreach (module_rebuild_cache() as $module) {
    if ($module->status) {
      $dir = dirname($module->filename);
      foreach ($module->info['files'] as $file) {
        $files["./$dir/$file"] = array();
      }
    }
  }
  foreach (file_scan_directory('includes', '\.inc$') as $filename => $file) {
    $files["./$filename"] = array();
  }

  foreach (registry_get_parsed_files() as $filename => $file) {
    // Add the md5 to those files we've already parsed.
    if (isset($files[$filename])) {
      $files[$filename]['md5'] = $file['md5'];
    }
    else {
      // Flush the registry of resources in files that are no longer on disc
      // or don't belong to installed modules.
      db_query("DELETE FROM {registry} WHERE filename = '%s'", $filename);
      db_query("DELETE FROM {registry_file} WHERE filename = '%s'", $filename);
    }
  }
  _registry_parse_files($files);

  cache_clear_all('*', 'cache_registry');
}

/**
 * Return the list of files in registry_file
 */
function registry_get_parsed_files() {
  $files = array();
  $res = db_query("SELECT * FROM {registry_file}");
  while ($file = db_fetch_array($res)) {
    $files[$file['filename']] = $file;
  }
  return $files;
}

/**
 * Parse all files that have changed since the registry was last built, and save their function and class listings.
 *
 * @param $files
 *  The list of files to check and parse.
 */
function _registry_parse_files($files) {
  $changed_files = array();
  foreach ($files as $filename => $file) {
    $contents = file_get_contents($filename);
    $md5 = md5($contents);
    $new_file = !isset($file['md5']);
    if ($new_file || $md5 != $file['md5']) {
      // We update the md5 after we've saved the files resources rather than here, so if we
      // don't make it through this rebuild, the next run will reparse the file.
      _registry_parse_file($filename, $contents);
      $file['md5'] = $md5;
      if ($new_file) {
        db_query("INSERT INTO {registry_file} (md5, filename) VALUES ('%s', '%s')", $md5, $filename);
      }
      else {
        db_query("UPDATE {registry_file} SET md5 = '%s' WHERE filename = '%s'", $md5, $filename);
      }
    }
  }
}

/**
 * Parse a file and save its function and class listings.
 *
 * @param $filename
 *  Name of the file we are going to parse.
 * @param $contents
 *  Contents of the file we are going to parse as a string.
 */
function _registry_parse_file($filename, $contents) {
  static $map = array(T_FUNCTION => 'function', T_CLASS => 'class', T_INTERFACE => 'interface');
  // Delete registry entries for this file, so we can insert the new resources.
  db_query("DELETE FROM {registry} WHERE filename = '%s'", $filename);
  $tokens = token_get_all($contents);
  while ($token = next($tokens)) {
    // Ignore all tokens except for those we are specifically saving.
    if (is_array($token) && isset($map[$token[0]])) {
      $type = $map[$token[0]];
      if ($resource_name = _registry_get_resource_name($tokens, $type)) {
        db_query("INSERT INTO {registry} (name, type, filename) VALUES ('%s', '%s', '%s')", $resource_name, $type, $filename);
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
  static $resources;

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
    if ($token == '{') {
      ++$num_braces;
    }
    elseif ($token == '}') {
      --$num_braces;
    }
  }
}

/**
 * @} End of "defgroup registry".
 */

