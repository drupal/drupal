<?php

/**
 * @file
 * API for handling file uploads and server file management.
 */

/**
 * Manually include stream wrapper code.
 *
 * Stream wrapper code is included here because there are cases where
 * File API is needed before a bootstrap, or in an alternate order (e.g.
 * maintenance theme).
 */
require_once DRUPAL_ROOT . '/includes/stream_wrappers.inc';

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 *
 * Fields on the file object:
 * - fid: File ID
 * - uid: The {users}.uid of the user who is associated with the file.
 * - filename: Name of the file with no path components. This may differ from
 *   the basename of the filepath if the file is renamed to avoid overwriting
 *   an existing file.
 * - uri: URI of the file.
 * - filemime: The file's MIME type.
 * - filesize: The size of the file in bytes.
 * - status: A bitmapped field indicating the status of the file. The first 8
 *   bits are reserved for Drupal core. The least significant bit indicates
 *   temporary (0) or permanent (1). Temporary files older than
 *   DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed during cron runs.
 * - timestamp: UNIX timestamp for the date the file was added to the database.
 */

/**
 * Flag used by file_prepare_directory() -- create directory if not present.
 */
define('FILE_CREATE_DIRECTORY', 1);

/**
 * Flag used by file_prepare_directory() -- file permissions may be changed.
 */
define('FILE_MODIFY_PERMISSIONS', 2);

/**
 * Flag for dealing with existing files: Appends number until name is unique.
 */
define('FILE_EXISTS_RENAME', 0);

/**
 * Flag for dealing with existing files: Replace the existing file.
 */
define('FILE_EXISTS_REPLACE', 1);

/**
 * Flag for dealing with existing files: Do nothing and return FALSE.
 */
define('FILE_EXISTS_ERROR', 2);

/**
 * Indicates that the file is permanent and should not be deleted.
 *
 * Temporary files older than DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed
 * during cron runs, but permanent files will not be removed during the file
 * garbage collection process.
 */
define('FILE_STATUS_PERMANENT', 1);

/**
 * Provides Drupal stream wrapper registry.
 *
 * A stream wrapper is an abstraction of a file system that allows Drupal to
 * use the same set of methods to access both local files and remote resources.
 *
 * Provide a facility for managing and querying user-defined stream wrappers
 * in PHP. PHP's internal stream_get_wrappers() doesn't return the class
 * registered to handle a stream, which we need to be able to find the handler
 * for class instantiation.
 *
 * If a module registers a scheme that is already registered with PHP, the
 * existing scheme will be unregistered and replaced with the specified class.
 *
 * A stream is referenced as "scheme://target".
 *
 * The optional $filter parameter can be used to retrieve only the stream
 * wrappers that are appropriate for particular usage. For example, this returns
 * only stream wrappers that use local file storage:
 * @code
 *   $local_stream_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL);
 * @endcode
 *
 * The $filter parameter can only filter to types containing a particular flag.
 * In some cases, you may want to filter to types that do not contain a
 * particular flag. For example, you may want to retrieve all stream wrappers
 * that are not writable, or all stream wrappers that are not local. PHP's
 * array_diff_key() function can be used to help with this. For example, this
 * returns only stream wrappers that do not use local file storage:
 * @code
 *   $remote_stream_wrappers = array_diff_key(file_get_stream_wrappers(STREAM_WRAPPERS_ALL), file_get_stream_wrappers(STREAM_WRAPPERS_LOCAL));
 * @endcode
 *
 * @param $filter
 *   (Optional) Filters out all types except those with an on bit for each on
 *   bit in $filter. For example, if $filter is STREAM_WRAPPERS_WRITE_VISIBLE,
 *   which is equal to (STREAM_WRAPPERS_READ | STREAM_WRAPPERS_WRITE |
 *   STREAM_WRAPPERS_VISIBLE), then only stream wrappers with all three of these
 *   bits set are returned. Defaults to STREAM_WRAPPERS_ALL, which returns all
 *   registered stream wrappers.
 *
 * @return
 *   An array keyed by scheme, with values containing an array of information
 *   about the stream wrapper, as returned by hook_stream_wrappers(). If $filter
 *   is omitted or set to STREAM_WRAPPERS_ALL, the entire Drupal stream wrapper
 *   registry is returned. Otherwise only the stream wrappers whose 'type'
 *   bitmask has an on bit for each bit specified in $filter are returned.
 *
 * @see hook_stream_wrappers()
 * @see hook_stream_wrappers_alter()
 */
function file_get_stream_wrappers($filter = STREAM_WRAPPERS_ALL) {
  $wrappers_storage = &drupal_static(__FUNCTION__);

  if (!isset($wrappers_storage)) {
    $wrappers = module_invoke_all('stream_wrappers');
    foreach ($wrappers as $scheme => $info) {
      // Add defaults.
      $wrappers[$scheme] += array('type' => STREAM_WRAPPERS_NORMAL);
    }
    drupal_alter('stream_wrappers', $wrappers);
    $existing = stream_get_wrappers();
    foreach ($wrappers as $scheme => $info) {
      // We only register classes that implement our interface.
      if (in_array('DrupalStreamWrapperInterface', class_implements($info['class']), TRUE)) {
        // Record whether we are overriding an existing scheme.
        if (in_array($scheme, $existing, TRUE)) {
          $wrappers[$scheme]['override'] = TRUE;
          stream_wrapper_unregister($scheme);
        }
        else {
          $wrappers[$scheme]['override'] = FALSE;
        }
        if (($info['type'] & STREAM_WRAPPERS_LOCAL) == STREAM_WRAPPERS_LOCAL) {
          stream_wrapper_register($scheme, $info['class']);
        }
        else {
          stream_wrapper_register($scheme, $info['class'], STREAM_IS_URL);
        }
      }
      // Pre-populate the static cache with the filters most typically used.
      $wrappers_storage[STREAM_WRAPPERS_ALL][$scheme] = $wrappers[$scheme];
      if (($info['type'] & STREAM_WRAPPERS_WRITE_VISIBLE) == STREAM_WRAPPERS_WRITE_VISIBLE) {
        $wrappers_storage[STREAM_WRAPPERS_WRITE_VISIBLE][$scheme] = $wrappers[$scheme];
      }
    }
  }

  if (!isset($wrappers_storage[$filter])) {
    $wrappers_storage[$filter] = array();
    foreach ($wrappers_storage[STREAM_WRAPPERS_ALL] as $scheme => $info) {
      // Bit-wise filter.
      if (($info['type'] & $filter) == $filter) {
        $wrappers_storage[$filter][$scheme] = $info;
      }
    }
  }

  return $wrappers_storage[$filter];
}

/**
 * Returns the stream wrapper class name for a given scheme.
 *
 * @param $scheme
 *   Stream scheme.
 *
 * @return
 *   Return string if a scheme has a registered handler, or FALSE.
 */
function file_stream_wrapper_get_class($scheme) {
  $wrappers = file_get_stream_wrappers();
  return empty($wrappers[$scheme]) ? FALSE : $wrappers[$scheme]['class'];
}

/**
 * Returns the scheme of a URI (e.g. a stream).
 *
 * @param $uri
 *   A stream, referenced as "scheme://target".
 *
 * @return
 *   A string containing the name of the scheme, or FALSE if none. For example,
 *   the URI "public://example.txt" would return "public".
 *
 * @see file_uri_target()
 */
function file_uri_scheme($uri) {
  $position = strpos($uri, '://');
  return $position ? substr($uri, 0, $position) : FALSE;
}

/**
 * Checks that the scheme of a stream URI is valid.
 *
 * Confirms that there is a registered stream handler for the provided scheme
 * and that it is callable. This is useful if you want to confirm a valid
 * scheme without creating a new instance of the registered handler.
 *
 * @param $scheme
 *   A URI scheme, a stream is referenced as "scheme://target".
 *
 * @return
 *   Returns TRUE if the string is the name of a validated stream,
 *   or FALSE if the scheme does not have a registered handler.
 */
function file_stream_wrapper_valid_scheme($scheme) {
  // Does the scheme have a registered handler that is callable?
  $class = file_stream_wrapper_get_class($scheme);
  if (class_exists($class)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}


/**
 * Returns the part of a URI after the schema.
 *
 * @param $uri
 *   A stream, referenced as "scheme://target".
 *
 * @return
 *   A string containing the target (path), or FALSE if none.
 *   For example, the URI "public://sample/test.txt" would return
 *   "sample/test.txt".
 *
 * @see file_uri_scheme()
 */
function file_uri_target($uri) {
  $data = explode('://', $uri, 2);

  // Remove erroneous leading or trailing, forward-slashes and backslashes.
  return count($data) == 2 ? trim($data[1], '\/') : FALSE;
}

/**
 * Gets the default file stream implementation.
 *
 * @return
 *   'public', 'private' or any other file scheme defined as the default.
 */
function file_default_scheme() {
  return variable_get('file_default_scheme', 'public');
}

/**
 * Normalizes a URI by making it syntactically correct.
 *
 * A stream is referenced as "scheme://target".
 *
 * The following actions are taken:
 * - Remove trailing slashes from target
 * - Trim erroneous leading slashes from target. e.g. ":///" becomes "://".
 *
 * @param $uri
 *   String reference containing the URI to normalize.
 *
 * @return
 *   The normalized URI.
 */
function file_stream_wrapper_uri_normalize($uri) {
  // Inline file_uri_scheme() function call for performance reasons.
  $position = strpos($uri, '://');
  $scheme = $position ? substr($uri, 0, $position) : FALSE;

  if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
    $target = file_uri_target($uri);

    if ($target !== FALSE) {
      $uri = $scheme . '://' . $target;
    }
  }
  return $uri;
}

/**
 * Returns a reference to the stream wrapper class responsible for a given URI.
 *
 * The scheme determines the stream wrapper class that should be
 * used by consulting the stream wrapper registry.
 *
 * @param $uri
 *   A stream, referenced as "scheme://target".
 *
 * @return
 *   Returns a new stream wrapper object appropriate for the given URI or FALSE
 *   if no registered handler could be found. For example, a URI of
 *   "private://example.txt" would return a new private stream wrapper object
 *   (DrupalPrivateStreamWrapper).
 */
function file_stream_wrapper_get_instance_by_uri($uri) {
  $scheme = file_uri_scheme($uri);
  $class = file_stream_wrapper_get_class($scheme);
  if (class_exists($class)) {
    $instance = new $class();
    $instance->setUri($uri);
    return $instance;
  }
  else {
    return FALSE;
  }
}

/**
 * Returns a reference to the stream wrapper class responsible for a scheme.
 *
 * This helper method returns a stream instance using a scheme. That is, the
 * passed string does not contain a "://". For example, "public" is a scheme
 * but "public://" is a URI (stream). This is because the later contains both
 * a scheme and target despite target being empty.
 *
 * Note: the instance URI will be initialized to "scheme://" so that you can
 * make the customary method calls as if you had retrieved an instance by URI.
 *
 * @param $scheme
 *   If the stream was "public://target", "public" would be the scheme.
 *
 * @return
 *   Returns a new stream wrapper object appropriate for the given $scheme.
 *   For example, for the public scheme a stream wrapper object
 *   (DrupalPublicStreamWrapper).
 *   FALSE is returned if no registered handler could be found.
 */
function file_stream_wrapper_get_instance_by_scheme($scheme) {
  $class = file_stream_wrapper_get_class($scheme);
  if (class_exists($class)) {
    $instance = new $class();
    $instance->setUri($scheme . '://');
    return $instance;
  }
  else {
    return FALSE;
  }
}

/**
 * Creates a web-accessible URL for a stream to an external or local file.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * There are two kinds of local files:
 * - "managed files", i.e. those stored by a Drupal-compatible stream wrapper.
 *   These are files that have either been uploaded by users or were generated
 *   automatically (for example through CSS aggregation).
 * - "shipped files", i.e. those outside of the files directory, which ship as
 *   part of Drupal core or contributed modules or themes.
 *
 * @param $uri
 *   The URI to a file for which we need an external URL, or the path to a
 *   shipped file.
 *
 * @return
 *   A string containing a URL that may be used to access the file.
 *   If the provided string already contains a preceding 'http', 'https', or
 *   '/', nothing is done and the same string is returned. If a stream wrapper
 *   could not be found to generate an external URL, then FALSE is returned.
 *
 * @see http://drupal.org/node/515192
 */
function file_create_url($uri) {
  // Allow the URI to be altered, e.g. to serve a file from a CDN or static
  // file server.
  drupal_alter('file_url', $uri);

  $scheme = file_uri_scheme($uri);

  if (!$scheme) {
    // Allow for:
    // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
    // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
    //   http://example.com/bar.jpg by the browser when viewing a page over
    //   HTTP and to https://example.com/bar.jpg when viewing a HTTPS page)
    // Both types of relative URIs are characterized by a leading slash, hence
    // we can use a single check.
    if (drupal_substr($uri, 0, 1) == '/') {
      return $uri;
    }
    else {
      // If this is not a properly formatted stream, then it is a shipped file.
      // Therefore, return the urlencoded URI with the base URL prepended.
      return $GLOBALS['base_url'] . '/' . drupal_encode_path($uri);
    }
  }
  elseif ($scheme == 'http' || $scheme == 'https') {
    // Check for HTTP so that we don't have to implement getExternalUrl() for
    // the HTTP wrapper.
    return $uri;
  }
  else {
    // Attempt to return an external URL using the appropriate wrapper.
    if ($wrapper = file_stream_wrapper_get_instance_by_uri($uri)) {
      return $wrapper->getExternalUrl();
    }
    else {
      return FALSE;
    }
  }
}

/**
 * Checks that the directory exists and is writable.
 *
 * Directories need to have execute permissions to be considered a directory by
 * FTP servers, etc.
 *
 * @param $directory
 *   A string reference containing the name of a directory path or URI. A
 *   trailing slash will be trimmed from a path.
 * @param $options
 *   A bitmask to indicate if the directory should be created if it does
 *   not exist (FILE_CREATE_DIRECTORY) or made writable if it is read-only
 *   (FILE_MODIFY_PERMISSIONS).
 *
 * @return
 *   TRUE if the directory exists (or was created) and is writable. FALSE
 *   otherwise.
 */
function file_prepare_directory(&$directory, $options = FILE_MODIFY_PERMISSIONS) {
  if (!file_stream_wrapper_valid_scheme(file_uri_scheme($directory))) {
    // Only trim if we're not dealing with a stream.
    $directory = rtrim($directory, '/\\');
  }

  // Check if directory exists.
  if (!is_dir($directory)) {
    // Let mkdir() recursively create directories and use the default directory
    // permissions.
    if (($options & FILE_CREATE_DIRECTORY) && @drupal_mkdir($directory, NULL, TRUE)) {
      return drupal_chmod($directory);
    }
    return FALSE;
  }
  // The directory exists, so check to see if it is writable.
  $writable = is_writable($directory);
  if (!$writable && ($options & FILE_MODIFY_PERMISSIONS)) {
    return drupal_chmod($directory);
  }

  return $writable;
}

/**
 * Creates a .htaccess file in each Drupal files directory if it is missing.
 */
function file_ensure_htaccess() {
  file_create_htaccess('public://', FALSE);
  if (variable_get('file_private_path', FALSE)) {
    file_create_htaccess('private://', TRUE);
  }
  file_create_htaccess('temporary://', TRUE);
}

/**
 * Creates a .htaccess file in the given directory.
 *
 * @param $directory
 *   The directory.
 * @param $private
 *   FALSE indicates that $directory should be an open and public directory.
 *   The default is TRUE which indicates a private and protected directory.
 * @param $force_overwrite
 *   Set to TRUE to attempt to overwrite the existing .htaccess file if one is
 *   already present. Defaults to FALSE.
 */
function file_create_htaccess($directory, $private = TRUE, $force_overwrite = FALSE) {
  if (file_uri_scheme($directory)) {
    $directory = file_stream_wrapper_uri_normalize($directory);
  }
  else {
    $directory = rtrim($directory, '/\\');
  }
  $htaccess_path =  $directory . '/.htaccess';

  if (file_exists($htaccess_path) && !$force_overwrite) {
    // Short circuit if the .htaccess file already exists.
    return;
  }

  $htaccess_lines = file_htaccess_lines($private);

  // Write the .htaccess file.
  if (file_put_contents($htaccess_path, $htaccess_lines)) {
    drupal_chmod($htaccess_path, 0444);
  }
  else {
    $variables = array('%directory' => $directory, '!htaccess' => '<br />' . nl2br(check_plain($htaccess_lines)));
    watchdog('security', "Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>!htaccess</code>", $variables, WATCHDOG_ERROR);
  }
}

/**
 * Returns the standard .htaccess lines that Drupal writes to file directories.
 *
 * @param $private
 *   (Optional) Set to FALSE to return the .htaccess lines for an open and
 *   public directory. The default is TRUE, which returns the .htaccess lines
 *   for a private and protected directory.
 *
 * @return
 *   A string representing the desired contents of the .htaccess file.
 *
 * @see file_create_htaccess()
 */
function file_htaccess_lines($private = TRUE) {
  $lines = <<<EOF
# Turn off all options we don't need.
Options None
Options +FollowSymLinks

# Set the catch-all handler to prevent scripts from being executed.
SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006
<Files *>
  # Override the handler again if we're run later in the evaluation list.
  SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003
</Files>

# If we know how to do it safely, disable the PHP engine entirely.
<IfModule mod_php5.c>
  php_flag engine off
</IfModule>
EOF;

  if ($private) {
    $lines = <<<EOF
# Deny all requests from Apache 2.4+.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

# Deny all requests from Apache 2.0-2.2.
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>
EOF
    . "\n\n" . $lines;
  }

  return $lines;
}

/**
 * Loads file objects from the database.
 *
 * @param $fids
 *   An array of file IDs.
 * @param $conditions
 *   (deprecated) An associative array of conditions on the {file_managed}
 *   table, where the keys are the database fields and the values are the
 *   values those fields must have. Instead, it is preferable to use
 *   EntityFieldQuery to retrieve a list of entity IDs loadable by
 *   this function.
 *
 * @return
 *   An array of file objects, indexed by fid.
 *
 * @todo Remove $conditions in Drupal 8.
 *
 * @see hook_file_load()
 * @see file_load()
 * @see entity_load()
 * @see EntityFieldQuery
 */
function file_load_multiple($fids = array(), $conditions = array()) {
  return entity_load('file', $fids, $conditions);
}

/**
 * Loads a single file object from the database.
 *
 * @param $fid
 *   A file ID.
 *
 * @return
 *   An object representing the file, or FALSE if the file was not found.
 *
 * @see hook_file_load()
 * @see file_load_multiple()
 */
function file_load($fid) {
  $files = file_load_multiple(array($fid), array());
  return reset($files);
}

/**
 * Saves a file object to the database.
 *
 * If the $file->fid is not set a new record will be added.
 *
 * @param $file
 *   A file object returned by file_load().
 *
 * @return
 *   The updated file object.
 *
 * @see hook_file_insert()
 * @see hook_file_update()
 */
function file_save(stdClass $file) {
  $file->timestamp = REQUEST_TIME;
  $file->filesize = filesize($file->uri);

  // Load the stored entity, if any.
  if (!empty($file->fid) && !isset($file->original)) {
    $file->original = entity_load_unchanged('file', $file->fid);
  }

  module_invoke_all('file_presave', $file);
  module_invoke_all('entity_presave', $file, 'file');

  if (empty($file->fid)) {
    drupal_write_record('file_managed', $file);
    // Inform modules about the newly added file.
    module_invoke_all('file_insert', $file);
    module_invoke_all('entity_insert', $file, 'file');
  }
  else {
    drupal_write_record('file_managed', $file, 'fid');
    // Inform modules that the file has been updated.
    module_invoke_all('file_update', $file);
    module_invoke_all('entity_update', $file, 'file');
  }

  // Clear internal properties.
  unset($file->original);
  // Clear the static loading cache.
  entity_get_controller('file')->resetCache(array($file->fid));

  return $file;
}

/**
 * Determines where a file is used.
 *
 * @param $file
 *   A file object.
 *
 * @return
 *   A nested array with usage data. The first level is keyed by module name,
 *   the second by object type and the third by the object id. The value
 *   of the third level contains the usage count.
 *
 * @see file_usage_add()
 * @see file_usage_delete()
 */
function file_usage_list(stdClass $file) {
  $result = db_select('file_usage', 'f')
    ->fields('f', array('module', 'type', 'id', 'count'))
    ->condition('fid', $file->fid)
    ->condition('count', 0, '>')
    ->execute();
  $references = array();
  foreach ($result as $usage) {
    $references[$usage->module][$usage->type][$usage->id] = $usage->count;
  }
  return $references;
}

/**
 * Records that a module is using a file.
 *
 * This usage information will be queried during file_delete() to ensure that
 * a file is not in use before it is physically removed from disk.
 *
 * Examples:
 * - A module that associates files with nodes, so $type would be
 *   'node' and $id would be the node's nid. Files for all revisions are stored
 *   within a single nid.
 * - The User module associates an image with a user, so $type would be 'user'
 *   and the $id would be the user's uid.
 *
 * @param $file
 *   A file object.
 * @param $module
 *   The name of the module using the file.
 * @param $type
 *   The type of the object that contains the referenced file.
 * @param $id
 *   The unique, numeric ID of the object containing the referenced file.
 * @param $count
 *   (optional) The number of references to add to the object. Defaults to 1.
 *
 * @see file_usage_list()
 * @see file_usage_delete()
 */
function file_usage_add(stdClass $file, $module, $type, $id, $count = 1) {
  db_merge('file_usage')
    ->key(array(
      'fid' => $file->fid,
      'module' => $module,
      'type' => $type,
      'id' => $id,
    ))
    ->fields(array('count' => $count))
    ->expression('count', 'count + :count', array(':count' => $count))
    ->execute();
}

/**
 * Removes a record to indicate that a module is no longer using a file.
 *
 * The file_delete() function is typically called after removing a file usage
 * to remove the record from the file_managed table and delete the file itself.
 *
 * @param $file
 *   A file object.
 * @param $module
 *   The name of the module using the file.
 * @param $type
 *   (optional) The type of the object that contains the referenced file. May
 *   be omitted if all module references to a file are being deleted.
 * @param $id
 *   (optional) The unique, numeric ID of the object containing the referenced
 *   file. May be omitted if all module references to a file are being deleted.
 * @param $count
 *   (optional) The number of references to delete from the object. Defaults to
 *   1. 0 may be specified to delete all references to the file within a
 *   specific object.
 *
 * @see file_usage_add()
 * @see file_usage_list()
 * @see file_delete()
 */
function file_usage_delete(stdClass $file, $module, $type = NULL, $id = NULL, $count = 1) {
  // Delete rows that have a exact or less value to prevent empty rows.
  $query = db_delete('file_usage')
    ->condition('module', $module)
    ->condition('fid', $file->fid);
  if ($type && $id) {
    $query
      ->condition('type', $type)
      ->condition('id', $id);
  }
  if ($count) {
    $query->condition('count', $count, '<=');
  }
  $result = $query->execute();

  // If the row has more than the specified count decrement it by that number.
  if (!$result && $count > 0) {
    $query = db_update('file_usage')
      ->condition('module', $module)
      ->condition('fid', $file->fid);
    if ($type && $id) {
      $query
        ->condition('type', $type)
        ->condition('id', $id);
    }
    $query->expression('count', 'count - :count', array(':count' => $count));
    $query->execute();
  }
}

/**
 * Copies a file to a new location and adds a file record to the database.
 *
 * This function should be used when manipulating files that have records
 * stored in the database. This is a powerful function that in many ways
 * performs like an advanced version of copy().
 * - Checks if $source and $destination are valid and readable/writable.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - If the $source and $destination are equal, the behavior depends on the
 *   $replace parameter. FILE_EXISTS_REPLACE will error out. FILE_EXISTS_RENAME
 *   will rename the file until the $destination is unique.
 * - Adds the new file to the files database. If the source file is a
 *   temporary file, the resulting file will also be a temporary file. See
 *   file_save_upload() for details on temporary files.
 *
 * @param $source
 *   A file object.
 * @param $destination
 *   A string containing the destination that $source should be copied to.
 *   This must be a stream wrapper URI.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file. If a managed file with
 *       the destination name exists then its database entry will be updated. If
 *       no database entry is found then a new one will be created.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   File object if the copy is successful, or FALSE in the event of an error.
 *
 * @see file_unmanaged_copy()
 * @see hook_file_copy()
 */
function file_copy(stdClass $source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  if (!file_valid_uri($destination)) {
    if (($realpath = drupal_realpath($source->uri)) !== FALSE) {
      watchdog('file', 'File %file (%realpath) could not be copied, because the destination %destination is invalid. This is often caused by improper use of file_copy() or a missing stream wrapper.', array('%file' => $source->uri, '%realpath' => $realpath, '%destination' => $destination));
    }
    else {
      watchdog('file', 'File %file could not be copied, because the destination %destination is invalid. This is often caused by improper use of file_copy() or a missing stream wrapper.', array('%file' => $source->uri, '%destination' => $destination));
    }
    drupal_set_message(t('The specified file %file could not be copied, because the destination is invalid. More information is available in the system log.', array('%file' => $source->uri)), 'error');
    return FALSE;
  }

  if ($uri = file_unmanaged_copy($source->uri, $destination, $replace)) {
    $file = clone $source;
    $file->fid = NULL;
    $file->uri = $uri;
    $file->filename = drupal_basename($uri);
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('uri' => $uri));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->fid;
        $file->filename = $existing->filename;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
      $file->filename = drupal_basename($destination);
    }

    $file = file_save($file);

    // Inform modules that the file has been copied.
    module_invoke_all('file_copy', $file, $source);

    return $file;
  }
  return FALSE;
}

/**
 * Determines whether the URI has a valid scheme for file API operations.
 *
 * There must be a scheme and it must be a Drupal-provided scheme like
 * 'public', 'private', 'temporary', or an extension provided with
 * hook_stream_wrappers().
 *
 * @param $uri
 *   The URI to be tested.
 *
 * @return
 *   TRUE if the URI is allowed.
 */
function file_valid_uri($uri) {
  // Assert that the URI has an allowed scheme. Barepaths are not allowed.
  $uri_scheme = file_uri_scheme($uri);
  if (empty($uri_scheme) || !file_stream_wrapper_valid_scheme($uri_scheme)) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Copies a file to a new location without invoking the file API.
 *
 * This is a powerful function that in many ways performs like an advanced
 * version of copy().
 * - Checks if $source and $destination are valid and readable/writable.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - If the $source and $destination are equal, the behavior depends on the
 *   $replace parameter. FILE_EXISTS_REPLACE will error out. FILE_EXISTS_RENAME
 *   will rename the file until the $destination is unique.
 * - Provides a fallback using realpaths if the move fails using stream
 *   wrappers. This can occur because PHP's copy() function does not properly
 *   support streams if safe_mode or open_basedir are enabled. See
 *   https://bugs.php.net/bug.php?id=60456
 *
 * @param $source
 *   A string specifying the filepath or URI of the source file.
 * @param $destination
 *   A URI containing the destination that $source should be copied to. The
 *   URI may be a bare filepath (without a scheme). If this value is omitted,
 *   Drupal's default files scheme will be used, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The path to the new file, or FALSE in the event of an error.
 *
 * @see file_copy()
 */
function file_unmanaged_copy($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $original_source = $source;

  // Assert that the source file actually exists.
  if (!file_exists($source)) {
    // @todo Replace drupal_set_message() calls with exceptions instead.
    drupal_set_message(t('The specified file %file could not be copied, because no file by that name exists. Please check that you supplied the correct filename.', array('%file' => $original_source)), 'error');
    if (($realpath = drupal_realpath($original_source)) !== FALSE) {
      watchdog('file', 'File %file (%realpath) could not be copied because it does not exist.', array('%file' => $original_source, '%realpath' => $realpath));
    }
    else {
      watchdog('file', 'File %file could not be copied because it does not exist.', array('%file' => $original_source));
    }
    return FALSE;
  }

  // Build a destination URI if necessary.
  if (!isset($destination)) {
    $destination = file_build_uri(drupal_basename($source));
  }


  // Prepare the destination directory.
  if (file_prepare_directory($destination)) {
    // The destination is already a directory, so append the source basename.
    $destination = file_stream_wrapper_uri_normalize($destination . '/' . drupal_basename($source));
  }
  else {
    // Perhaps $destination is a dir/file?
    $dirname = drupal_dirname($destination);
    if (!file_prepare_directory($dirname)) {
      // The destination is not valid.
      watchdog('file', 'File %file could not be copied, because the destination directory %destination is not configured correctly.', array('%file' => $original_source, '%destination' => $dirname));
      drupal_set_message(t('The specified file %file could not be copied, because the destination directory is not properly configured. This may be caused by a problem with file or directory permissions. More information is available in the system log.', array('%file' => $original_source)), 'error');
      return FALSE;
    }
  }

  // Determine whether we can perform this operation based on overwrite rules.
  $destination = file_destination($destination, $replace);
  if ($destination === FALSE) {
    drupal_set_message(t('The file %file could not be copied because a file by that name already exists in the destination directory.', array('%file' => $original_source)), 'error');
    watchdog('file', 'File %file could not be copied because a file by that name already exists in the destination directory (%directory)', array('%file' => $original_source, '%directory' => $destination));
    return FALSE;
  }

  // Assert that the source and destination filenames are not the same.
  $real_source = drupal_realpath($source);
  $real_destination = drupal_realpath($destination);
  if ($source == $destination || ($real_source !== FALSE) && ($real_source == $real_destination)) {
    drupal_set_message(t('The specified file %file was not copied because it would overwrite itself.', array('%file' => $source)), 'error');
    watchdog('file', 'File %file could not be copied because it would overwrite itself.', array('%file' => $source));
    return FALSE;
  }
  // Make sure the .htaccess files are present.
  file_ensure_htaccess();
  // Perform the copy operation.
  if (!@copy($source, $destination)) {
    // If the copy failed and realpaths exist, retry the operation using them
    // instead.
    if ($real_source === FALSE || $real_destination === FALSE || !@copy($real_source, $real_destination)) {
      watchdog('file', 'The specified file %file could not be copied to %destination.', array('%file' => $source, '%destination' => $destination), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  // Set the permissions on the new file.
  drupal_chmod($destination);

  return $destination;
}

/**
 * Constructs a URI to Drupal's default files location given a relative path.
 */
function file_build_uri($path) {
  $uri = file_default_scheme() . '://' . $path;
  return file_stream_wrapper_uri_normalize($uri);
}

/**
 * Determines the destination path for a file.
 *
 * @param $destination
 *   A string specifying the desired final URI or filepath.
 * @param $replace
 *   Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The destination filepath, or FALSE if the file already exists
 *   and FILE_EXISTS_ERROR is specified.
 */
function file_destination($destination, $replace) {
  if (file_exists($destination)) {
    switch ($replace) {
      case FILE_EXISTS_REPLACE:
        // Do nothing here, we want to overwrite the existing file.
        break;

      case FILE_EXISTS_RENAME:
        $basename = drupal_basename($destination);
        $directory = drupal_dirname($destination);
        $destination = file_create_filename($basename, $directory);
        break;

      case FILE_EXISTS_ERROR:
        // Error reporting handled by calling function.
        return FALSE;
    }
  }
  return $destination;
}

/**
 * Moves a file to a new location and update the file's database entry.
 *
 * Moving a file is performed by copying the file to the new location and then
 * deleting the original.
 * - Checks if $source and $destination are valid and readable/writable.
 * - Performs a file move if $source is not equal to $destination.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - Adds the new file to the files database.
 *
 * @param $source
 *   A file object.
 * @param $destination
 *   A string containing the destination that $source should be moved to.
 *   This must be a stream wrapper URI.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file. If a managed file with
 *       the destination name exists then its database entry will be updated and
 *       file_delete() called on the source file after hook_file_move is called.
 *       If no database entry is found then the source files record will be
 *       updated.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   Resulting file object for success, or FALSE in the event of an error.
 *
 * @see file_unmanaged_move()
 * @see hook_file_move()
 */
function file_move(stdClass $source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  if (!file_valid_uri($destination)) {
    if (($realpath = drupal_realpath($source->uri)) !== FALSE) {
      watchdog('file', 'File %file (%realpath) could not be moved, because the destination %destination is invalid. This may be caused by improper use of file_move() or a missing stream wrapper.', array('%file' => $source->uri, '%realpath' => $realpath, '%destination' => $destination));
    }
    else {
      watchdog('file', 'File %file could not be moved, because the destination %destination is invalid. This may be caused by improper use of file_move() or a missing stream wrapper.', array('%file' => $source->uri, '%destination' => $destination));
    }
    drupal_set_message(t('The specified file %file could not be moved, because the destination is invalid. More information is available in the system log.', array('%file' => $source->uri)), 'error');
    return FALSE;
  }

  if ($uri = file_unmanaged_move($source->uri, $destination, $replace)) {
    $delete_source = FALSE;

    $file = clone $source;
    $file->uri = $uri;
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('uri' => $uri));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $delete_source = TRUE;
        $file->fid = $existing->fid;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
      $file->filename = drupal_basename($destination);
    }

    $file = file_save($file);

    // Inform modules that the file has been moved.
    module_invoke_all('file_move', $file, $source);

    if ($delete_source) {
      // Try a soft delete to remove original if it's not in use elsewhere.
      file_delete($source);
    }

    return $file;
  }
  return FALSE;
}

/**
 * Moves a file to a new location without database changes or hook invocation.
 *
 * @param $source
 *   A string specifying the filepath or URI of the original file.
 * @param $destination
 *   A string containing the destination that $source should be moved to.
 *   This must be a stream wrapper URI. If this value is omitted, Drupal's
 *   default files scheme will be used, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   The URI of the moved file, or FALSE in the event of an error.
 *
 * @see file_move()
 */
function file_unmanaged_move($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $filepath = file_unmanaged_copy($source, $destination, $replace);
  if ($filepath == FALSE || file_unmanaged_delete($source) == FALSE) {
    return FALSE;
  }
  return $filepath;
}

/**
 * Modifies a filename as needed for security purposes.
 *
 * Munging a file name prevents unknown file extensions from masking exploit
 * files. When web servers such as Apache decide how to process a URL request,
 * they use the file extension. If the extension is not recognized, Apache
 * skips that extension and uses the previous file extension. For example, if
 * the file being requested is exploit.php.pps, and Apache does not recognize
 * the '.pps' extension, it treats the file as PHP and executes it. To make
 * this file name safe for Apache and prevent it from executing as PHP, the
 * .php extension is "munged" into .php_, making the safe file name
 * exploit.php_.pps.
 *
 * Specifically, this function adds an underscore to all extensions that are
 * between 2 and 5 characters in length, internal to the file name, and not
 * included in $extensions.
 *
 * Function behavior is also controlled by the Drupal variable
 * 'allow_insecure_uploads'. If 'allow_insecure_uploads' evaluates to TRUE, no
 * alterations will be made, if it evaluates to FALSE, the filename is 'munged'.
 *
 * @param $filename
 *   File name to modify.
 * @param $extensions
 *   A space-separated list of extensions that should not be altered.
 * @param $alerts
 *   If TRUE, drupal_set_message() will be called to display a message if the
 *   file name was changed.
 *
 * @return
 *   The potentially modified $filename.
 */
function file_munge_filename($filename, $extensions, $alerts = TRUE) {
  $original = $filename;

  // Allow potentially insecure uploads for very savvy users and admin
  if (!variable_get('allow_insecure_uploads', 0)) {
    // Remove any null bytes. See http://php.net/manual/security.filesystem.nullbytes.php
    $filename = str_replace(chr(0), '', $filename);

    $whitelist = array_unique(explode(' ', strtolower(trim($extensions))));

    // Split the filename up by periods. The first part becomes the basename
    // the last part the final extension.
    $filename_parts = explode('.', $filename);
    $new_filename = array_shift($filename_parts); // Remove file basename.
    $final_extension = array_pop($filename_parts); // Remove final extension.

    // Loop through the middle parts of the name and add an underscore to the
    // end of each section that could be a file extension but isn't in the list
    // of allowed extensions.
    foreach ($filename_parts as $filename_part) {
      $new_filename .= '.' . $filename_part;
      if (!in_array(strtolower($filename_part), $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
        $new_filename .= '_';
      }
    }
    $filename = $new_filename . '.' . $final_extension;

    if ($alerts && $original != $filename) {
      drupal_set_message(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $filename)));
    }
  }

  return $filename;
}

/**
 * Undoes the effect of file_munge_filename().
 *
 * @param $filename
 *   String with the filename to be unmunged.
 *
 * @return
 *   An unmunged filename string.
 */
function file_unmunge_filename($filename) {
  return str_replace('_.', '.', $filename);
}

/**
 * Creates a full file path from a directory and filename.
 *
 * If a file with the specified name already exists, an alternative will be
 * used.
 *
 * @param $basename
 *   String filename
 * @param $directory
 *   String containing the directory or parent URI.
 *
 * @return
 *   File path consisting of $directory and a unique filename based off
 *   of $basename.
 */
function file_create_filename($basename, $directory) {
  // Strip control characters (ASCII value < 32). Though these are allowed in
  // some filesystems, not many applications handle them well.
  $basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
  if (substr(PHP_OS, 0, 3) == 'WIN') {
    // These characters are not allowed in Windows filenames
    $basename = str_replace(array(':', '*', '?', '"', '<', '>', '|'), '_', $basename);
  }

  // A URI or path may already have a trailing slash or look like "public://".
  if (substr($directory, -1) == '/') {
    $separator = '';
  }
  else {
    $separator = '/';
  }

  $destination = $directory . $separator . $basename;

  if (file_exists($destination)) {
    // Destination file already exists, generate an alternative.
    $pos = strrpos($basename, '.');
    if ($pos !== FALSE) {
      $name = substr($basename, 0, $pos);
      $ext = substr($basename, $pos);
    }
    else {
      $name = $basename;
      $ext = '';
    }

    $counter = 0;
    do {
      $destination = $directory . $separator . $name . '_' . $counter++ . $ext;
    } while (file_exists($destination));
  }

  return $destination;
}

/**
 * Deletes a file and its database record.
 *
 * If the $force parameter is not TRUE, file_usage_list() will be called to
 * determine if the file is being used by any modules. If the file is being
 * used the delete will be canceled.
 *
 * @param $file
 *   A file object.
 * @param $force
 *   Boolean indicating that the file should be deleted even if the file is
 *   reported as in use by the file_usage table.
 *
 * @return mixed
 *   TRUE for success, FALSE in the event of an error, or an array if the file
 *   is being used by any modules.
 *
 * @see file_unmanaged_delete()
 * @see file_usage_list()
 * @see file_usage_delete()
 * @see hook_file_delete()
 */
function file_delete(stdClass $file, $force = FALSE) {
  if (!file_valid_uri($file->uri)) {
    if (($realpath = drupal_realpath($file->uri)) !== FALSE) {
      watchdog('file', 'File %file (%realpath) could not be deleted because it is not a valid URI. This may be caused by improper use of file_delete() or a missing stream wrapper.', array('%file' => $file->uri, '%realpath' => $realpath));
    }
    else {
      watchdog('file', 'File %file could not be deleted because it is not a valid URI. This may be caused by improper use of file_delete() or a missing stream wrapper.', array('%file' => $file->uri));
    }
    drupal_set_message(t('The specified file %file could not be deleted, because it is not a valid URI. More information is available in the system log.', array('%file' => $file->uri)), 'error');
    return FALSE;
  }

  // If any module still has a usage entry in the file_usage table, the file
  // will not be deleted, but file_delete() will return a populated array
  // that tests as TRUE.
  if (!$force && ($references = file_usage_list($file))) {
    return $references;
  }

  // Let other modules clean up any references to the deleted file.
  module_invoke_all('file_delete', $file);
  module_invoke_all('entity_delete', $file, 'file');

  // Make sure the file is deleted before removing its row from the
  // database, so UIs can still find the file in the database.
  if (file_unmanaged_delete($file->uri)) {
    db_delete('file_managed')->condition('fid', $file->fid)->execute();
    db_delete('file_usage')->condition('fid', $file->fid)->execute();
    entity_get_controller('file')->resetCache();
    return TRUE;
  }
  return FALSE;
}

/**
 * Deletes a file without database changes or hook invocations.
 *
 * This function should be used when the file to be deleted does not have an
 * entry recorded in the files table.
 *
 * @param $path
 *   A string containing a file path or (streamwrapper) URI.
 *
 * @return
 *   TRUE for success or path does not exist, or FALSE in the event of an
 *   error.
 *
 * @see file_delete()
 * @see file_unmanaged_delete_recursive()
 */
function file_unmanaged_delete($path) {
  if (is_dir($path)) {
    watchdog('file', '%path is a directory and cannot be removed using file_unmanaged_delete().', array('%path' => $path), WATCHDOG_ERROR);
    return FALSE;
  }
  if (is_file($path)) {
    return drupal_unlink($path);
  }
  // Return TRUE for non-existent file, but log that nothing was actually
  // deleted, as the current state is the intended result.
  if (!file_exists($path)) {
    watchdog('file', 'The file %path was not deleted, because it does not exist.', array('%path' => $path), WATCHDOG_NOTICE);
    return TRUE;
  }
  // We cannot handle anything other than files and directories. Log an error
  // for everything else (sockets, symbolic links, etc).
  watchdog('file', 'The file %path is not of a recognized type so it was not deleted.', array('%path' => $path), WATCHDOG_ERROR);
  return FALSE;
}

/**
 * Deletes all files and directories in the specified filepath recursively.
 *
 * If the specified path is a directory then the function will call itself
 * recursively to process the contents. Once the contents have been removed the
 * directory will also be removed.
 *
 * If the specified path is a file then it will be passed to
 * file_unmanaged_delete().
 *
 * Note that this only deletes visible files with write permission.
 *
 * @param $path
 *   A string containing either an URI or a file or directory path.
 *
 * @return
 *   TRUE for success or if path does not exist, FALSE in the event of an
 *   error.
 *
 * @see file_unmanaged_delete()
 */
function file_unmanaged_delete_recursive($path) {
  if (is_dir($path)) {
    $dir = dir($path);
    while (($entry = $dir->read()) !== FALSE) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      $entry_path = $path . '/' . $entry;
      file_unmanaged_delete_recursive($entry_path);
    }
    $dir->close();

    return drupal_rmdir($path);
  }
  return file_unmanaged_delete($path);
}

/**
 * Determines total disk space used by a single user or the whole filesystem.
 *
 * @param $uid
 *   Optional. A user id, specifying NULL returns the total space used by all
 *   non-temporary files.
 * @param $status
 *   Optional. The file status to consider. The default is to only
 *   consider files in status FILE_STATUS_PERMANENT.
 *
 * @return
 *   An integer containing the number of bytes used.
 */
function file_space_used($uid = NULL, $status = FILE_STATUS_PERMANENT) {
  $query = db_select('file_managed', 'f');
  $query->condition('f.status', $status);
  $query->addExpression('SUM(f.filesize)', 'filesize');
  if (isset($uid)) {
    $query->condition('f.uid', $uid);
  }
  return $query->execute()->fetchField();
}

/**
 * Saves a file upload to a new location.
 *
 * The file will be added to the {file_managed} table as a temporary file.
 * Temporary files are periodically cleaned. To make the file a permanent file,
 * assign the status and use file_save() to save the changes.
 *
 * @param $form_field_name
 *   A string that is the associative array key of the upload form element in
 *   the form array.
 * @param $validators
 *   An optional, associative array of callback functions used to validate the
 *   file. See file_validate() for a full discussion of the array format.
 *   If no extension validator is provided it will default to a limited safe
 *   list of extensions which is as follows: "jpg jpeg gif png txt
 *   doc xls pdf ppt pps odt ods odp". To allow all extensions you must
 *   explicitly set the 'file_validate_extensions' validator to an empty array
 *   (Beware: this is not safe and should only be allowed for trusted users, if
 *   at all).
 * @param $destination
 *   A string containing the URI that the file should be copied to. This must
 *   be a stream wrapper URI. If this value is omitted, Drupal's temporary
 *   files scheme will be used ("temporary://").
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE: Replace the existing file.
 *   - FILE_EXISTS_RENAME: Append _{incrementing number} until the filename is
 *     unique.
 *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
 *
 * @return
 *   An object containing the file information if the upload succeeded, FALSE
 *   in the event of an error, or NULL if no file was uploaded. The
 *   documentation for the "File interface" group, which you can find under
 *   Related topics, or the header at the top of this file, documents the
 *   components of a file object. In addition to the standard components,
 *   this function adds:
 *   - source: Path to the file before it is moved.
 *   - destination: Path to the file after it is moved (same as 'uri').
 */
function file_save_upload($form_field_name, $validators = array(), $destination = FALSE, $replace = FILE_EXISTS_RENAME) {
  global $user;
  static $upload_cache;

  // Return cached objects without processing since the file will have
  // already been processed and the paths in _FILES will be invalid.
  if (isset($upload_cache[$form_field_name])) {
    return $upload_cache[$form_field_name];
  }

  // Make sure there's an upload to process.
  if (empty($_FILES['files']['name'][$form_field_name])) {
    return NULL;
  }

  // Check for file upload errors and return FALSE if a lower level system
  // error occurred. For a complete list of errors:
  // See http://php.net/manual/features.file-upload.errors.php.
  switch ($_FILES['files']['error'][$form_field_name]) {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      drupal_set_message(t('The file %file could not be saved, because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $_FILES['files']['name'][$form_field_name], '%maxsize' => format_size(file_upload_max_size()))), 'error');
      return FALSE;

    case UPLOAD_ERR_PARTIAL:
    case UPLOAD_ERR_NO_FILE:
      drupal_set_message(t('The file %file could not be saved, because the upload did not complete.', array('%file' => $_FILES['files']['name'][$form_field_name])), 'error');
      return FALSE;

    case UPLOAD_ERR_OK:
      // Final check that this is a valid upload, if it isn't, use the
      // default error handler.
      if (is_uploaded_file($_FILES['files']['tmp_name'][$form_field_name])) {
        break;
      }

    // Unknown error
    default:
      drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => $_FILES['files']['name'][$form_field_name])), 'error');
      return FALSE;
  }

  // Begin building file object.
  $file = new stdClass();
  $file->uid      = $user->uid;
  $file->status   = 0;
  $file->filename = trim(drupal_basename($_FILES['files']['name'][$form_field_name]), '.');
  $file->uri      = $_FILES['files']['tmp_name'][$form_field_name];
  $file->filemime = file_get_mimetype($file->filename);
  $file->filesize = $_FILES['files']['size'][$form_field_name];

  $extensions = '';
  if (isset($validators['file_validate_extensions'])) {
    if (isset($validators['file_validate_extensions'][0])) {
      // Build the list of non-munged extensions if the caller provided them.
      $extensions = $validators['file_validate_extensions'][0];
    }
    else {
      // If 'file_validate_extensions' is set and the list is empty then the
      // caller wants to allow any extension. In this case we have to remove the
      // validator or else it will reject all extensions.
      unset($validators['file_validate_extensions']);
    }
  }
  else {
    // No validator was provided, so add one using the default list.
    // Build a default non-munged safe list for file_munge_filename().
    $extensions = 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp';
    $validators['file_validate_extensions'] = array();
    $validators['file_validate_extensions'][0] = $extensions;
  }

  if (!empty($extensions)) {
    // Munge the filename to protect against possible malicious extension hiding
    // within an unknown file type (ie: filename.html.foo).
    $file->filename = file_munge_filename($file->filename, $extensions);
  }

  // Rename potentially executable files, to help prevent exploits (i.e. will
  // rename filename.php.foo and filename.php to filename.php.foo.txt and
  // filename.php.txt, respectively). Don't rename if 'allow_insecure_uploads'
  // evaluates to TRUE.
  if (!variable_get('allow_insecure_uploads', 0) && preg_match('/\.(php|pl|py|cgi|asp|js)(\.|$)/i', $file->filename) && (substr($file->filename, -4) != '.txt')) {
    $file->filemime = 'text/plain';
    $file->uri .= '.txt';
    $file->filename .= '.txt';
    // The .txt extension may not be in the allowed list of extensions. We have
    // to add it here or else the file upload will fail.
    if (!empty($extensions)) {
      $validators['file_validate_extensions'][0] .= ' txt';
      drupal_set_message(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $file->filename)));
    }
  }

  // If the destination is not provided, use the temporary directory.
  if (empty($destination)) {
    $destination = 'temporary://';
  }

  // Assert that the destination contains a valid stream.
  $destination_scheme = file_uri_scheme($destination);
  if (!$destination_scheme || !file_stream_wrapper_valid_scheme($destination_scheme)) {
    drupal_set_message(t('The file could not be uploaded, because the destination %destination is invalid.', array('%destination' => $destination)), 'error');
    return FALSE;
  }

  $file->source = $form_field_name;
  // A URI may already have a trailing slash or look like "public://".
  if (substr($destination, -1) != '/') {
    $destination .= '/';
  }
  $file->destination = file_destination($destination . $file->filename, $replace);
  // If file_destination() returns FALSE then $replace == FILE_EXISTS_ERROR and
  // there's an existing file so we need to bail.
  if ($file->destination === FALSE) {
    drupal_set_message(t('The file %source could not be uploaded because a file by that name already exists in the destination %directory.', array('%source' => $form_field_name, '%directory' => $destination)), 'error');
    return FALSE;
  }

  // Add in our check of the file name length.
  $validators['file_validate_name_length'] = array();

  // Call the validation functions specified by this function's caller.
  $errors = file_validate($file, $validators);

  // Check for errors.
  if (!empty($errors)) {
    $message = t('The specified file %name could not be uploaded.', array('%name' => $file->filename));
    if (count($errors) > 1) {
      $message .= theme('item_list', array('items' => $errors));
    }
    else {
      $message .= ' ' . array_pop($errors);
    }
    form_set_error($form_field_name, $message);
    return FALSE;
  }

  // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary
  // directory. This overcomes open_basedir restrictions for future file
  // operations.
  $file->uri = $file->destination;
  if (!drupal_move_uploaded_file($_FILES['files']['tmp_name'][$form_field_name], $file->uri)) {
    form_set_error($form_field_name, t('File upload error. Could not move uploaded file.'));
    watchdog('file', 'Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $file->filename, '%destination' => $file->uri));
    return FALSE;
  }

  // Set the permissions on the new file.
  drupal_chmod($file->uri);

  // If we are replacing an existing file re-use its database record.
  if ($replace == FILE_EXISTS_REPLACE) {
    $existing_files = file_load_multiple(array(), array('uri' => $file->uri));
    if (count($existing_files)) {
      $existing = reset($existing_files);
      $file->fid = $existing->fid;
    }
  }

  // If we made it this far it's safe to record this file in the database.
  if ($file = file_save($file)) {
    // Add file to the cache.
    $upload_cache[$form_field_name] = $file;
    return $file;
  }
  return FALSE;
}

/**
 * Moves an uploaded file to a new location.
 *
 * PHP's move_uploaded_file() does not properly support streams if safe_mode
 * or open_basedir are enabled, so this function fills that gap.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * @param $filename
 *   The filename of the uploaded file.
 * @param $uri
 *   A string containing the destination URI of the file.
 *
 * @return
 *   TRUE on success, or FALSE on failure.
 *
 * @see move_uploaded_file()
 * @see http://drupal.org/node/515192
 * @ingroup php_wrappers
 */
function drupal_move_uploaded_file($filename, $uri) {
  $result = @move_uploaded_file($filename, $uri);
  // PHP's move_uploaded_file() does not properly support streams if safe_mode
  // or open_basedir are enabled so if the move failed, try finding a real path
  // and retry the move operation.
  if (!$result) {
    if ($realpath = drupal_realpath($uri)) {
      $result = move_uploaded_file($filename, $realpath);
    }
    else {
      $result = move_uploaded_file($filename, $uri);
    }
  }

  return $result;
}

/**
 * Checks that a file meets the criteria specified by the validators.
 *
 * After executing the validator callbacks specified hook_file_validate() will
 * also be called to allow other modules to report errors about the file.
 *
 * @param $file
 *   A Drupal file object.
 * @param $validators
 *   An optional, associative array of callback functions used to validate the
 *   file. The keys are function names and the values arrays of callback
 *   parameters which will be passed in after the file object. The
 *   functions should return an array of error messages; an empty array
 *   indicates that the file passed validation. The functions will be called in
 *   the order specified.
 *
 * @return
 *   An array containing validation error messages.
 *
 * @see hook_file_validate()
 */
function file_validate(stdClass &$file, $validators = array()) {
  // Call the validation functions specified by this function's caller.
  $errors = array();
  foreach ($validators as $function => $args) {
    if (function_exists($function)) {
      array_unshift($args, $file);
      $errors = array_merge($errors, call_user_func_array($function, $args));
    }
  }

  // Let other modules perform validation on the new file.
  return array_merge($errors, module_invoke_all('file_validate', $file));
}

/**
 * Checks for files with names longer than we can store in the database.
 *
 * @param $file
 *   A Drupal file object.
 *
 * @return
 *   An array. If the file name is too long, it will contain an error message.
 */
function file_validate_name_length(stdClass $file) {
  $errors = array();

  if (empty($file->filename)) {
    $errors[] = t("The file's name is empty. Please give a name to the file.");
  }
  if (strlen($file->filename) > 240) {
    $errors[] = t("The file's name exceeds the 240 characters limit. Please rename the file and try again.");
  }
  return $errors;
}

/**
 * Checks that the filename ends with an allowed extension.
 *
 * @param $file
 *   A Drupal file object.
 * @param $extensions
 *   A string with a space separated list of allowed extensions.
 *
 * @return
 *   An array. If the file extension is not allowed, it will contain an error
 *   message.
 *
 * @see hook_file_validate()
 */
function file_validate_extensions(stdClass $file, $extensions) {
  $errors = array();

  $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
  if (!preg_match($regex, $file->filename)) {
    $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
  }
  return $errors;
}

/**
 * Checks that the file's size is below certain limits.
 *
 * @param $file
 *   A Drupal file object.
 * @param $file_limit
 *   An integer specifying the maximum file size in bytes. Zero indicates that
 *   no limit should be enforced.
 * @param $user_limit
 *   An integer specifying the maximum number of bytes the user is allowed.
 *   Zero indicates that no limit should be enforced.
 *
 * @return
 *   An array. If the file size exceeds limits, it will contain an error
 *   message.
 *
 * @see hook_file_validate()
 */
function file_validate_size(stdClass $file, $file_limit = 0, $user_limit = 0) {
  global $user;
  $errors = array();

  if ($file_limit && $file->filesize > $file_limit) {
    $errors[] = t('The file is %filesize exceeding the maximum file size of %maxsize.', array('%filesize' => format_size($file->filesize), '%maxsize' => format_size($file_limit)));
  }

  // Save a query by only calling file_space_used() when a limit is provided.
  if ($user_limit && (file_space_used($user->uid) + $file->filesize) > $user_limit) {
    $errors[] = t('The file is %filesize which would exceed your disk quota of %quota.', array('%filesize' => format_size($file->filesize), '%quota' => format_size($user_limit)));
  }

  return $errors;
}

/**
 * Checks that the file is recognized by image_get_info() as an image.
 *
 * @param $file
 *   A Drupal file object.
 *
 * @return
 *   An array. If the file is not an image, it will contain an error message.
 *
 * @see hook_file_validate()
 */
function file_validate_is_image(stdClass $file) {
  $errors = array();

  $info = image_get_info($file->uri);
  if (!$info || empty($info['extension'])) {
    $errors[] = t('Only JPEG, PNG and GIF images are allowed.');
  }

  return $errors;
}

/**
 * Verifies that image dimensions are within the specified maximum and minimum.
 *
 * Non-image files will be ignored. If an image toolkit is available the image
 * will be scaled to fit within the desired maximum dimensions.
 *
 * @param $file
 *   A Drupal file object. This function may resize the file affecting its
 *   size.
 * @param $maximum_dimensions
 *   An optional string in the form WIDTHxHEIGHT e.g. '640x480' or '85x85'. If
 *   an image toolkit is installed the image will be resized down to these
 *   dimensions. A value of 0 indicates no restriction on size, so resizing
 *   will be attempted.
 * @param $minimum_dimensions
 *   An optional string in the form WIDTHxHEIGHT. This will check that the
 *   image meets a minimum size. A value of 0 indicates no restriction.
 *
 * @return
 *   An array. If the file is an image and did not meet the requirements, it
 *   will contain an error message.
 *
 * @see hook_file_validate()
 */
function file_validate_image_resolution(stdClass $file, $maximum_dimensions = 0, $minimum_dimensions = 0) {
  $errors = array();

  // Check first that the file is an image.
  if ($info = image_get_info($file->uri)) {
    if ($maximum_dimensions) {
      // Check that it is smaller than the given dimensions.
      list($width, $height) = explode('x', $maximum_dimensions);
      if ($info['width'] > $width || $info['height'] > $height) {
        // Try to resize the image to fit the dimensions.
        if ($image = image_load($file->uri)) {
          image_scale($image, $width, $height);
          image_save($image);
          $file->filesize = $image->info['file_size'];
          drupal_set_message(t('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', array('%dimensions' => $maximum_dimensions)));
        }
        else {
          $errors[] = t('The image is too large; the maximum dimensions are %dimensions pixels.', array('%dimensions' => $maximum_dimensions));
        }
      }
    }

    if ($minimum_dimensions) {
      // Check that it is larger than the given dimensions.
      list($width, $height) = explode('x', $minimum_dimensions);
      if ($info['width'] < $width || $info['height'] < $height) {
        $errors[] = t('The image is too small; the minimum dimensions are %dimensions pixels.', array('%dimensions' => $minimum_dimensions));
      }
    }
  }

  return $errors;
}

/**
 * Saves a file to the specified destination and creates a database entry.
 *
 * @param $data
 *   A string containing the contents of the file.
 * @param $destination
 *   A string containing the destination URI. This must be a stream wrapper URI.
 *   If no value is provided, a randomized name will be generated and the file
 *   will be saved using Drupal's default files scheme, usually "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file. If a managed file with
 *       the destination name exists then its database entry will be updated. If
 *       no database entry is found then a new one will be created.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   A file object, or FALSE on error.
 *
 * @see file_unmanaged_save_data()
 */
function file_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  global $user;

  if (empty($destination)) {
    $destination = file_default_scheme() . '://';
  }
  if (!file_valid_uri($destination)) {
    watchdog('file', 'The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.', array('%destination' => $destination));
    drupal_set_message(t('The data could not be saved, because the destination is invalid. More information is available in the system log.'), 'error');
    return FALSE;
  }

  if ($uri = file_unmanaged_save_data($data, $destination, $replace)) {
    // Create a file object.
    $file = new stdClass();
    $file->fid = NULL;
    $file->uri = $uri;
    $file->filename = drupal_basename($uri);
    $file->filemime = file_get_mimetype($file->uri);
    $file->uid      = $user->uid;
    $file->status   = FILE_STATUS_PERMANENT;
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('uri' => $uri));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->fid;
        $file->filename = $existing->filename;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
      $file->filename = drupal_basename($destination);
    }

    return file_save($file);
  }
  return FALSE;
}

/**
 * Saves a string to the specified destination without invoking file API.
 *
 * This function is identical to file_save_data() except the file will not be
 * saved to the {file_managed} table and none of the file_* hooks will be
 * called.
 *
 * @param $data
 *   A string containing the contents of the file.
 * @param $destination
 *   A string containing the destination location. This must be a stream wrapper
 *   URI. If no value is provided, a randomized name will be generated and the
 *   file will be saved using Drupal's default files scheme, usually
 *   "public://".
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *                          unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return
 *   A string with the path of the resulting file, or FALSE on error.
 *
 * @see file_save_data()
 */
function file_unmanaged_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  // Write the data to a temporary file.
  $temp_name = drupal_tempnam('temporary://', 'file');
  if (file_put_contents($temp_name, $data) === FALSE) {
    drupal_set_message(t('The file could not be created.'), 'error');
    return FALSE;
  }

  // Move the file to its final destination.
  return file_unmanaged_move($temp_name, $destination, $replace);
}

/**
 * Transfers a file to the client using HTTP.
 *
 * Pipes a file through Drupal to the client.
 *
 * @param $uri
 *   String specifying the file URI to transfer.
 * @param $headers
 *   An array of HTTP headers to send along with file.
 */
function file_transfer($uri, $headers) {
  if (ob_get_level()) {
    ob_end_clean();
  }

  foreach ($headers as $name => $value) {
    drupal_add_http_header($name, $value);
  }
  drupal_send_headers();
  $scheme = file_uri_scheme($uri);
  // Transfer file in 1024 byte chunks to save memory usage.
  if ($scheme && file_stream_wrapper_valid_scheme($scheme) && $fd = fopen($uri, 'rb')) {
    while (!feof($fd)) {
      print fread($fd, 1024);
    }
    fclose($fd);
  }
  else {
    drupal_not_found();
  }
  drupal_exit();
}

/**
 * Menu handler for private file transfers.
 *
 * Call modules that implement hook_file_download() to find out if a file is
 * accessible and what headers it should be transferred with. If one or more
 * modules returned headers the download will start with the returned headers.
 * If a module returns -1 drupal_access_denied() will be returned. If the file
 * exists but no modules responded drupal_access_denied() will be returned.
 * If the file does not exist drupal_not_found() will be returned.
 *
 * @see system_menu()
 */
function file_download() {
  // Merge remainder of arguments from GET['q'], into relative file path.
  $args = func_get_args();
  $scheme = array_shift($args);
  $target = implode('/', $args);
  $uri = $scheme . '://' . $target;
  if (file_stream_wrapper_valid_scheme($scheme) && file_exists($uri)) {
    $headers = file_download_headers($uri);
    if (count($headers)) {
      file_transfer($uri, $headers);
    }
    drupal_access_denied();
  }
  else {
    drupal_not_found();
  }
  drupal_exit();
}

/**
 * Retrieves headers for a private file download.
 *
 * Calls all module implementations of hook_file_download() to retrieve headers
 * for files by the module that originally provided the file. The presence of
 * returned headers indicates the current user has access to the file.
 *
 * @param $uri
 *   The URI for the file whose headers should be retrieved.
 *
 * @return
 *   If access is allowed, headers for the file, suitable for passing to
 *   file_transfer(). If access is not allowed, an empty array will be returned.
 *
 * @see file_transfer()
 * @see file_download_access()
 * @see hook_file_download()
 */
function file_download_headers($uri) {
  // Let other modules provide headers and control access to the file.
  // module_invoke_all() uses array_merge_recursive() which merges header
  // values into a new array. To avoid that and allow modules to override
  // headers instead, use array_merge() to merge the returned arrays.
  $headers = array();
  foreach (module_implements('file_download') as $module) {
    $function = $module . '_file_download';
    $result = $function($uri);
    if ($result == -1) {
      // Throw away the headers received so far.
      $headers = array();
      break;
    }
    if (isset($result) && is_array($result)) {
      $headers = array_merge($headers, $result);
    }
  }
  return $headers;
}

/**
 * Checks that the current user has access to a particular file.
 *
 * The return value of this function hinges on the return value from
 * file_download_headers(), which is the function responsible for collecting
 * access information through hook_file_download().
 *
 * If immediately transferring the file to the browser and the headers will
 * need to be retrieved, the return value of file_download_headers() should be
 * used to determine access directly, so that access checks will not be run
 * twice.
 *
 * @param $uri
 *   The URI for the file whose access should be retrieved.
 *
 * @return
 *   Boolean TRUE if access is allowed. FALSE if access is not allowed.
 *
 * @see file_download_headers()
 * @see hook_file_download()
 */
function file_download_access($uri) {
  return count(file_download_headers($uri)) > 0;
}

/**
 * Finds all files that match a given mask in a given directory.
 *
 * Directories and files beginning with a period are excluded; this
 * prevents hidden files and directories (such as SVN working directories)
 * from being scanned.
 *
 * @param $dir
 *   The base directory or URI to scan, without trailing slash.
 * @param $mask
 *   The preg_match() regular expression of the files to find.
 * @param $options
 *   An associative array of additional options, with the following elements:
 *   - 'nomask': The preg_match() regular expression of the files to ignore.
 *     Defaults to '/(\.\.?|CVS)$/'.
 *   - 'callback': The callback function to call for each match. There is no
 *     default callback.
 *   - 'recurse': When TRUE, the directory scan will recurse the entire tree
 *     starting at the provided directory. Defaults to TRUE.
 *   - 'key': The key to be used for the returned associative array of files.
 *     Possible values are 'uri', for the file's URI; 'filename', for the
 *     basename of the file; and 'name' for the name of the file without the
 *     extension. Defaults to 'uri'.
 *   - 'min_depth': Minimum depth of directories to return files from. Defaults
 *     to 0.
 * @param $depth
 *   Current depth of recursion. This parameter is only used internally and
 *   should not be passed in.
 *
 * @return
 *   An associative array (keyed on the chosen key) of objects with 'uri',
 *   'filename', and 'name' members corresponding to the matching files.
 */
function file_scan_directory($dir, $mask, $options = array(), $depth = 0) {
  // Merge in defaults.
  $options += array(
    'nomask' => '/(\.\.?|CVS)$/',
    'callback' => 0,
    'recurse' => TRUE,
    'key' => 'uri',
    'min_depth' => 0,
  );

  $options['key'] = in_array($options['key'], array('uri', 'filename', 'name')) ? $options['key'] : 'uri';
  $files = array();
  if (is_dir($dir) && $handle = opendir($dir)) {
    while (FALSE !== ($filename = readdir($handle))) {
      if (!preg_match($options['nomask'], $filename) && $filename[0] != '.') {
        $uri = "$dir/$filename";
        $uri = file_stream_wrapper_uri_normalize($uri);
        if (is_dir($uri) && $options['recurse']) {
          // Give priority to files in this folder by merging them in after any subdirectory files.
          $files = array_merge(file_scan_directory($uri, $mask, $options, $depth + 1), $files);
        }
        elseif ($depth >= $options['min_depth'] && preg_match($mask, $filename)) {
          // Always use this match over anything already set in $files with the
          // same $$options['key'].
          $file = new stdClass();
          $file->uri = $uri;
          $file->filename = $filename;
          $file->name = pathinfo($filename, PATHINFO_FILENAME);
          $key = $options['key'];
          $files[$file->$key] = $file;
          if ($options['callback']) {
            $options['callback']($uri);
          }
        }
      }
    }

    closedir($handle);
  }

  return $files;
}

/**
 * Determines the maximum file upload size by querying the PHP settings.
 *
 * @return
 *   A file size limit in bytes based on the PHP upload_max_filesize and
 *   post_max_size
 */
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    // Start with post_max_size.
    $max_size = parse_size(ini_get('post_max_size'));

    // If upload_max_size is less, then reduce. Except if upload_max_size is
    // zero, which indicates no limit.
    $upload_max = parse_size(ini_get('upload_max_filesize'));
    if ($upload_max > 0 && $upload_max < $max_size) {
      $max_size = $upload_max;
    }
  }
  return $max_size;
}

/**
 * Determines an Internet Media Type or MIME type from a filename.
 *
 * @param $uri
 *   A string containing the URI, path, or filename.
 * @param $mapping
 *   An optional map of extensions to their mimetypes, in the form:
 *    - 'mimetypes': a list of mimetypes, keyed by an identifier,
 *    - 'extensions': the mapping itself, an associative array in which
 *      the key is the extension (lowercase) and the value is the mimetype
 *      identifier. If $mapping is NULL file_mimetype_mapping() is called.
 *
 * @return
 *   The internet media type registered for the extension or
 *   application/octet-stream for unknown extensions.
 *
 * @see file_default_mimetype_mapping()
 */
function file_get_mimetype($uri, $mapping = NULL) {
  if ($wrapper = file_stream_wrapper_get_instance_by_uri($uri)) {
    return $wrapper->getMimeType($uri, $mapping);
  }
  else {
    // getMimeType() is not implementation specific, so we can directly
    // call it without an instance.
    return DrupalLocalStreamWrapper::getMimeType($uri, $mapping);
  }
}

/**
 * Sets the permissions on a file or directory.
 *
 * This function will use the 'file_chmod_directory' and 'file_chmod_file'
 * variables for the default modes for directories and uploaded/generated
 * files. By default these will give everyone read access so that users
 * accessing the files with a user account without the webserver group (e.g.
 * via FTP) can read these files, and give group write permissions so webserver
 * group members (e.g. a vhost account) can alter files uploaded and owned by
 * the webserver.
 *
 * PHP's chmod does not support stream wrappers so we use our wrapper
 * implementation which interfaces with chmod() by default. Contrib wrappers
 * may override this behavior in their implementations as needed.
 *
 * @param $uri
 *   A string containing a URI file, or directory path.
 * @param $mode
 *   Integer value for the permissions. Consult PHP chmod() documentation for
 *   more information.
 *
 * @return
 *   TRUE for success, FALSE in the event of an error.
 *
 * @ingroup php_wrappers
 */
function drupal_chmod($uri, $mode = NULL) {
  if (!isset($mode)) {
    if (is_dir($uri)) {
      $mode = variable_get('file_chmod_directory', 0775);
    }
    else {
      $mode = variable_get('file_chmod_file', 0664);
    }
  }

  // If this URI is a stream, pass it off to the appropriate stream wrapper.
  // Otherwise, attempt PHP's chmod. This allows use of drupal_chmod even
  // for unmanaged files outside of the stream wrapper interface.
  if ($wrapper = file_stream_wrapper_get_instance_by_uri($uri)) {
    if ($wrapper->chmod($mode)) {
      return TRUE;
    }
  }
  else {
    if (@chmod($uri, $mode)) {
      return TRUE;
    }
  }

  watchdog('file', 'The file permissions could not be set on %uri.', array('%uri' => $uri), WATCHDOG_ERROR);
  return FALSE;
}

/**
 * Deletes a file.
 *
 * PHP's unlink() is broken on Windows, as it can fail to remove a file
 * when it has a read-only flag set.
 *
 * @param $uri
 *   A URI or pathname.
 * @param $context
 *   Refer to http://php.net/manual/ref.stream.php
 *
 * @return
 *   Boolean TRUE on success, or FALSE on failure.
 *
 * @see unlink()
 * @ingroup php_wrappers
 */
function drupal_unlink($uri, $context = NULL) {
  $scheme = file_uri_scheme($uri);
  if ((!$scheme || !file_stream_wrapper_valid_scheme($scheme)) && (substr(PHP_OS, 0, 3) == 'WIN')) {
    chmod($uri, 0600);
  }
  if ($context) {
    return unlink($uri, $context);
  }
  else {
    return unlink($uri);
  }
}

/**
 * Resolves the absolute filepath of a local URI or filepath.
 *
 * The use of drupal_realpath() is discouraged, because it does not work for
 * remote URIs. Except in rare cases, URIs should not be manually resolved.
 *
 * Only use this function if you know that the stream wrapper in the URI uses
 * the local file system, and you need to pass an absolute path to a function
 * that is incompatible with stream URIs.
 *
 * @param string $uri
 *   A stream wrapper URI or a filepath, possibly including one or more symbolic
 *   links.
 *
 * @return string|false
 *   The absolute local filepath (with no symbolic links), or FALSE on failure.
 *
 * @see DrupalStreamWrapperInterface::realpath()
 * @see http://php.net/manual/function.realpath.php
 * @ingroup php_wrappers
 */
function drupal_realpath($uri) {
  // If this URI is a stream, pass it off to the appropriate stream wrapper.
  // Otherwise, attempt PHP's realpath. This allows use of drupal_realpath even
  // for unmanaged files outside of the stream wrapper interface.
  if ($wrapper = file_stream_wrapper_get_instance_by_uri($uri)) {
    return $wrapper->realpath();
  }
  // Check that the URI has a value. There is a bug in PHP 5.2 on *BSD systems
  // that makes realpath not return FALSE as expected when passing an empty
  // variable.
  // @todo Remove when Drupal drops support for PHP 5.2.
  elseif (!empty($uri)) {
    return realpath($uri);
  }
  return FALSE;
}

/**
 * Gets the name of the directory from a given path.
 *
 * PHP's dirname() does not properly pass streams, so this function fills
 * that gap. It is backwards compatible with normal paths and will use
 * PHP's dirname() as a fallback.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * @param $uri
 *   A URI or path.
 *
 * @return
 *   A string containing the directory name.
 *
 * @see dirname()
 * @see http://drupal.org/node/515192
 * @ingroup php_wrappers
 */
function drupal_dirname($uri) {
  $scheme = file_uri_scheme($uri);

  if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
    return file_stream_wrapper_get_instance_by_scheme($scheme)->dirname($uri);
  }
  else {
    return dirname($uri);
  }
}

/**
 * Gets the filename from a given path.
 *
 * PHP's basename() does not properly support streams or filenames beginning
 * with a non-US-ASCII character.
 *
 * @see http://bugs.php.net/bug.php?id=37738
 * @see basename()
 *
 * @ingroup php_wrappers
 */
function drupal_basename($uri, $suffix = NULL) {
  $separators = '/';
  if (DIRECTORY_SEPARATOR != '/') {
    // For Windows OS add special separator.
    $separators .= DIRECTORY_SEPARATOR;
  }
  // Remove right-most slashes when $uri points to directory.
  $uri = rtrim($uri, $separators);
  // Returns the trailing part of the $uri starting after one of the directory
  // separators.
  $filename = preg_match('@[^' . preg_quote($separators, '@') . ']+$@', $uri, $matches) ? $matches[0] : '';
  // Cuts off a suffix from the filename.
  if ($suffix) {
    $filename = preg_replace('@' . preg_quote($suffix, '@') . '$@', '', $filename);
  }
  return $filename;
}

/**
 * Creates a directory using Drupal's default mode.
 *
 * PHP's mkdir() does not respect Drupal's default permissions mode. If a mode
 * is not provided, this function will make sure that Drupal's is used.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * @param $uri
 *   A URI or pathname.
 * @param $mode
 *   By default the Drupal mode is used.
 * @param $recursive
 *   Default to FALSE.
 * @param $context
 *   Refer to http://php.net/manual/ref.stream.php
 *
 * @return
 *   Boolean TRUE on success, or FALSE on failure.
 *
 * @see mkdir()
 * @see http://drupal.org/node/515192
 * @ingroup php_wrappers
 */
function drupal_mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
  if (!isset($mode)) {
    $mode = variable_get('file_chmod_directory', 0775);
  }

  if (!isset($context)) {
    return mkdir($uri, $mode, $recursive);
  }
  else {
    return mkdir($uri, $mode, $recursive, $context);
  }
}

/**
 * Removes a directory.
 *
 * PHP's rmdir() is broken on Windows, as it can fail to remove a directory
 * when it has a read-only flag set.
 *
 * @param $uri
 *   A URI or pathname.
 * @param $context
 *   Refer to http://php.net/manual/ref.stream.php
 *
 * @return
 *   Boolean TRUE on success, or FALSE on failure.
 *
 * @see rmdir()
 * @ingroup php_wrappers
 */
function drupal_rmdir($uri, $context = NULL) {
  $scheme = file_uri_scheme($uri);
  if ((!$scheme || !file_stream_wrapper_valid_scheme($scheme)) && (substr(PHP_OS, 0, 3) == 'WIN')) {
    chmod($uri, 0700);
  }
  if ($context) {
    return rmdir($uri, $context);
  }
  else {
    return rmdir($uri);
  }
}

/**
 * Creates a file with a unique filename in the specified directory.
 *
 * PHP's tempnam() does not return a URI like we want. This function
 * will return a URI if given a URI, or it will return a filepath if
 * given a filepath.
 *
 * Compatibility: normal paths and stream wrappers.
 *
 * @param $directory
 *   The directory where the temporary filename will be created.
 * @param $prefix
 *   The prefix of the generated temporary filename.
 *   Note: Windows uses only the first three characters of prefix.
 *
 * @return
 *   The new temporary filename, or FALSE on failure.
 *
 * @see tempnam()
 * @see http://drupal.org/node/515192
 * @ingroup php_wrappers
 */
function drupal_tempnam($directory, $prefix) {
  $scheme = file_uri_scheme($directory);

  if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
    $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);

    if ($filename = tempnam($wrapper->getDirectoryPath(), $prefix)) {
      return $scheme . '://' . drupal_basename($filename);
    }
    else {
      return FALSE;
    }
  }
  else {
    // Handle as a normal tempnam() call.
    return tempnam($directory, $prefix);
  }
}

/**
 * Gets the path of system-appropriate temporary directory.
 */
function file_directory_temp() {
  $temporary_directory = variable_get('file_temporary_path', NULL);

  if (empty($temporary_directory)) {
    $directories = array();

    // Has PHP been set with an upload_tmp_dir?
    if (ini_get('upload_tmp_dir')) {
      $directories[] = ini_get('upload_tmp_dir');
    }

    // Operating system specific dirs.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      $directories[] = 'c:\\windows\\temp';
      $directories[] = 'c:\\winnt\\temp';
    }
    else {
      $directories[] = '/tmp';
    }
    // PHP may be able to find an alternative tmp directory.
    // This function exists in PHP 5 >= 5.2.1, but Drupal
    // requires PHP 5 >= 5.2.0, so we check for it.
    if (function_exists('sys_get_temp_dir')) {
      $directories[] = sys_get_temp_dir();
    }

    foreach ($directories as $directory) {
      if (is_dir($directory) && is_writable($directory)) {
        $temporary_directory = $directory;
        break;
      }
    }

    if (empty($temporary_directory)) {
      // If no directory has been found default to 'files/tmp'.
      $temporary_directory = variable_get('file_public_path', conf_path() . '/files') . '/tmp';

      // Windows accepts paths with either slash (/) or backslash (\), but will
      // not accept a path which contains both a slash and a backslash. Since
      // the 'file_public_path' variable may have either format, we sanitize
      // everything to use slash which is supported on all platforms.
      $temporary_directory = str_replace('\\', '/', $temporary_directory);
    }
    // Save the path of the discovered directory.
    variable_set('file_temporary_path', $temporary_directory);
  }

  return $temporary_directory;
}

/**
 * Examines a file object and returns appropriate content headers for download.
 *
 * @param $file
 *   A file object.
 *
 * @return
 *   An associative array of headers, as expected by file_transfer().
 */
function file_get_content_headers($file) {
  $type = mime_header_encode($file->filemime);

  return array(
    'Content-Type' => $type,
    'Content-Length' => $file->filesize,
    'Cache-Control' => 'private',
  );
}

/**
 * @} End of "defgroup file".
 */
