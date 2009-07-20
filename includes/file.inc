<?php
// $Id$

/**
 * @file
 * API for handling file uploads and server file management.
 */

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 *
 * Fields on the file object:
 * - fid - File ID
 * - uid - The {users}.uid of the user who is associated with the file.
 * - filename - Name of the file with no path components. This may differ from
 *   the basename of the filepath if the file is renamed to avoid overwriting
 *   an existing file.
 * - filepath - Path of the file relative to Drupal root.
 * - filemime - The file's MIME type.
 * - filesize - The size of the file in bytes.
 * - status - A bitmapped field indicating the status of the file. The first 8
 *   bits are reserved for Drupal core. The least sigifigant bit indicates
 *   temporary (0) or permanent (1). Temporary files older than
 *   DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed during cron runs.
 * - timestamp - UNIX timestamp for the date the file was added to the database.
 */

/**
 * Flag to indicate that the 'public' file download method is enabled.
 *
 * When using this method, files are available from a regular HTTP request,
 * which provides no additional access restrictions.
 */
define('FILE_DOWNLOADS_PUBLIC', 1);

/**
 * Flag to indicate that the 'private' file download method is enabled.
 *
 * When using this method, all file requests are served by Drupal, during which
 * access-control checking can be performed.
 */
define('FILE_DOWNLOADS_PRIVATE', 2);

/**
 * Flag used by file_check_directory() -- create directory if not present.
 */
define('FILE_CREATE_DIRECTORY', 1);

/**
 * Flag used by file_check_directory() -- file permissions may be changed.
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
 * File status -- This bit in the status indicates that the file is permanent
 * and should not be deleted during file garbage collection process. Temporary
 * files older than DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed during cron
 * runs.
 */
define('FILE_STATUS_PERMANENT', 1);

/**
 * Create the download path to a file.
 *
 * @param $path A string containing the path of the file to generate URL for.
 * @return A string containing a URL that can be used to download the file.
 */
function file_create_url($path) {
  // Strip file_directory_path from $path. We only include relative paths in
  // URLs.
  $path = file_directory_strip($path);
  switch (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC)) {
    case FILE_DOWNLOADS_PUBLIC:
      return $GLOBALS['base_url'] . '/' . file_directory_path() . '/' . str_replace('\\', '/', $path);
    case FILE_DOWNLOADS_PRIVATE:
      return url('system/files/' . $path, array('absolute' => TRUE));
  }
}

/**
 * Make sure the destination is a complete path and resides in the file system
 * directory, if it is not prepend the file system directory.
 *
 * @param $destination
 *   A string containing the path to verify. If this value is omitted, Drupal's
 *   'files' directory will be used.
 * @return
 *   A string containing the path to file, with file system directory appended
 *   if necessary, or FALSE if the path is invalid (i.e. outside the configured
 *   'files' or temp directories).
 */
function file_create_path($destination = NULL) {
  $file_path = file_directory_path();
  if (is_null($destination)) {
    return $file_path;
  }
  // file_check_location() checks whether the destination is inside the Drupal
  // files directory.
  if (file_check_location($destination, $file_path)) {
    return $destination;
  }
  // Check if the destination is instead inside the Drupal temporary files
  // directory.
  elseif (file_check_location($destination, file_directory_temp())) {
    return $destination;
  }
  // Not found, try again with prefixed directory path.
  elseif (file_check_location($file_path . '/' . $destination, $file_path)) {
    return $file_path . '/' . $destination;
  }
  // File not found.
  return FALSE;
}

/**
 * Check that the directory exists and is writable.
 *
 * Directories need to have execute permissions to be considered a directory by
 * FTP servers, etc.
 *
 * @param $directory
 *   A string containing the name of a directory path.
 * @param $mode
 *   A bitmask to indicate if the directory should be created if it does
 *   not exist (FILE_CREATE_DIRECTORY) or made writable if it is read-only
 *   (FILE_MODIFY_PERMISSIONS).
 * @param $form_item
 *   An optional string containing the name of a form item that any errors will
 *   be attached to. This is useful for settings forms that require the user to
 *   specify a writable directory. If it can't be made to work, a form error
 *   will be set preventing them from saving the settings.
 * @return
 *   FALSE when directory not found, or TRUE when directory exists.
 */
function file_check_directory(&$directory, $mode = 0, $form_item = NULL) {
  $directory = rtrim($directory, '/\\');

  // Check if directory exists.
  if (!is_dir($directory)) {
    // Let mkdir() recursively create directories and use the default directory
    // permissions.
    if (($mode & FILE_CREATE_DIRECTORY) && @mkdir($directory, variable_get('file_chmod_directory', 0775), TRUE)) {
      drupal_chmod($directory);
    }
    else {
      if ($form_item) {
        form_set_error($form_item, t('The directory %directory does not exist.', array('%directory' => $directory)));
        watchdog('file system', 'The directory %directory does not exist.', array('%directory' => $directory), WATCHDOG_ERROR);
      }
      return FALSE;
    }
  }

  // Check to see if the directory is writable.
  if (!is_writable($directory)) {
    // If not able to modify permissions, or if able to, but chmod
    // fails, return false.
    if (!$mode || (($mode & FILE_MODIFY_PERMISSIONS) && !drupal_chmod($directory))) {
      if ($form_item) {
        form_set_error($form_item, t('The directory %directory is not writable', array('%directory' => $directory)));
        watchdog('file system', 'The directory %directory is not writable, because it does not have the correct permissions set.', array('%directory' => $directory), WATCHDOG_ERROR);
      }
      return FALSE;
    }
  }

  if ((file_directory_path() == $directory || file_directory_temp() == $directory) && !is_file("$directory/.htaccess")) {
    $htaccess_lines = "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nOptions None\nOptions +FollowSymLinks";
    if (file_put_contents("$directory/.htaccess", $htaccess_lines)) {
      drupal_chmod("$directory/.htaccess");
    }
    else {
      $variables = array('%directory' => $directory, '!htaccess' => '<br />' . nl2br(check_plain($htaccess_lines)));
      form_set_error($form_item, t("Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>!htaccess</code>", $variables));
      watchdog('security', "Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>!htaccess</code>", $variables, WATCHDOG_ERROR);
    }
  }

  return TRUE;
}

/**
 * Checks path to see if it is a directory, or a directory/file.
 *
 * @param $path
 *   A string containing a file path. This will be set to the directory's path.
 * @return
 *   If the directory is not in a Drupal writable directory, FALSE is returned.
 *   Otherwise, the base name of the path is returned.
 */
function file_check_path(&$path) {
  // Check if path is a directory.
  if (file_check_directory($path)) {
    return '';
  }

  // Check if path is a possible dir/file.
  $filename = basename($path);
  $path = dirname($path);
  if (file_check_directory($path)) {
    return $filename;
  }

  return FALSE;
}

/**
 * Check if a file is really located inside $directory.
 *
 * This should be used to make sure a file specified is really located within
 * the directory to prevent exploits. Note that the file or path being checked
 * does not actually need to exist yet.
 *
 * @code
 *   // Returns FALSE:
 *   file_check_location('/www/example.com/files/../../../etc/passwd', '/www/example.com/files');
 * @endcode
 *
 * @param $source
 *   A string set to the file to check.
 * @param $directory
 *   A string where the file should be located.
 * @return
 *   FALSE if the path does not exist in the directory; otherwise, the real
 *   path of the source.
 */
function file_check_location($source, $directory = '') {
  $check = realpath($source);
  if ($check) {
    $source = $check;
  }
  else {
    // This file does not yet exist.
    $source = realpath(dirname($source)) . '/' . basename($source);
  }
  $directory = realpath($directory);
  if ($directory && strpos($source, $directory) !== 0) {
    return FALSE;
  }
  return $source;
}

/**
 * Load file objects from the database.
 *
 * @param $fids
 *   An array of file IDs.
 * @param $conditions
 *   An array of conditions to match against the {files} table. These
 *   should be supplied in the form array('field_name' => 'field_value').
 * @return
 *  An array of file objects, indexed by fid.
 *
 * @see hook_file_load()
 * @see file_load()
 */
function file_load_multiple($fids = array(), $conditions = array()) {
  $query = db_select('files', 'f')->fields('f');

  // If the $fids array is populated, add those to the query.
  if ($fids) {
    $query->condition('f.fid', $fids, 'IN');
  }

  // If the conditions array is populated, add those to the query.
  if ($conditions) {
    foreach ($conditions as $field => $value) {
      $query->condition('f.' . $field, $value);
    }
  }
  $files = $query->execute()->fetchAllAssoc('fid');

  // Invoke hook_file_load() on the terms loaded from the database
  // and add them to the static cache.
  if (!empty($files)) {
    foreach (module_implements('file_load') as $module) {
      $function = $module . '_file_load';
      $function($files);
    }
  }
  return $files;
}

/**
 * Load a file object from the database.
 *
 * @param $fid
 *  A file ID.
 * @return
 *   A file object.
 *
 * @see hook_file_load()
 * @see file_load_multiple()
 */
function file_load($fid) {
  $files = file_load_multiple(array($fid), array());
  return reset($files);
}

/**
 * Save a file object to the database.
 *
 * If the $file->fid is not set a new record will be added. Re-saving an
 * existing file will not change its status.
 *
 * @param $file
 *   A file object returned by file_load().
 * @return
 *   The updated file object.
 *
 * @see hook_file_insert()
 * @see hook_file_update()
 */
function file_save($file) {
  $file = (object)$file;
  $file->timestamp = REQUEST_TIME;
  $file->filesize = filesize($file->filepath);

  if (empty($file->fid)) {
    drupal_write_record('files', $file);
    // Inform modules about the newly added file.
    module_invoke_all('file_insert', $file);
  }
  else {
    drupal_write_record('files', $file, 'fid');
    // Inform modules that the file has been updated.
    module_invoke_all('file_update', $file);
  }

  return $file;
}

/**
 * Copy a file to a new location and adds a file record to the database.
 *
 * This function should be used when manipulating files that have records
 * stored in the database. This is a powerful function that in many ways
 * performs like an advanced version of copy().
 * - Checks if $source and $destination are valid and readable/writable.
 * - Checks that $source is not equal to $destination; if they are an error
 *   is reported.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 * - Adds the new file to the files database. If the source file is a
 *   temporary file, the resulting file will also be a temporary file.
 *   @see file_save_upload() for details on temporary files.
 *
 * @param $source
 *   A file object.
 * @param $destination
 *   A string containing the destination that $source should be copied to. This
 *   can be a complete file path, a directory path or, if this value is omitted,
 *   Drupal's 'files' directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file. If a managed file with
 *       the destination name exists then its database entry will be updated. If
 *       no database entry is found then a new one will be created.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   File object if the copy is successful, or FALSE in the event of an error.
 *
 * @see file_unmanaged_copy()
 * @see hook_file_copy()
 */
function file_copy($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $source = (object)$source;

  if ($filepath = file_unmanaged_copy($source->filepath, $destination, $replace)) {
    $file = clone $source;
    $file->fid = NULL;
    $file->filepath = $filepath;
    $file->filename = basename($filepath);
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('filepath' => $filepath));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->fid;
        $file->filename = $existing->filename;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    else if ($replace == FILE_EXISTS_RENAME && is_file(file_create_path($destination))) {
      $file->filename = basename($destination);
    }

    $file = file_save($file);

    // Inform modules that the file has been copied.
    module_invoke_all('file_copy', $file, $source);

    return $file;
  }
  return FALSE;
}

/**
 * Copy a file to a new location without calling any hooks or making any
 * changes to the database.
 *
 * This is a powerful function that in many ways performs like an advanced
 * version of copy().
 * - Checks if $source and $destination are valid and readable/writable.
 * - Checks that $source is not equal to $destination; if they are an error
 *   is reported.
 * - If file already exists in $destination either the call will error out,
 *   replace the file or rename the file based on the $replace parameter.
 *
 * @param $source
 *   A string specifying the file location of the original file.
 * @param $destination
 *   A string containing the destination that $source should be copied to. This
 *   can be a complete file path, a directory path or, if this value is omitted,
 *   Drupal's 'files' directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   The path to the new file, or FALSE in the event of an error.
 *
 * @see file_copy()
 */
function file_unmanaged_copy($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $original_source = $source;
  $original_destination = $destination;

  $source = realpath($source);
  if (!file_exists($source)) {
    drupal_set_message(t('The specified file %file could not be copied, because no file by that name exists. Please check that you supplied the correct filename.', array('%file' => $original_source)), 'error');
    return FALSE;
  }

  $proposed_destination = file_create_path($destination);
  $directory = $proposed_destination;
  $basename = file_check_path($directory);

  // Make sure we at least have a valid directory.
  if ($basename === FALSE) {
    drupal_set_message(t('The specified file %file could not be copied, because the destination %directory is not properly configured. This is often caused by a problem with file or directory permissions.', array('%file' => $original_source, '%directory' => empty($original_destination) ? $proposed_destination : $original_destination)), 'error');
    return FALSE;
  }

  // If the destination file is not specified then use the filename of the
  // source file.
  $basename = $basename ? $basename : basename($source);
  $destination = file_destination($directory . '/' . $basename, $replace);

  if ($destination === FALSE) {
    drupal_set_message(t('The specified file %file could not be copied because a file by that name already exists in the destination %directory.', array('%file' => $source, '%directory' => $proposed_destination)), 'error');
    return FALSE;
  }
  // Make sure source and destination filenames are not the same, makes no
  // sense to copy it if they are. In fact copying the file will most likely
  // result in a 0 byte file. Which is bad. Real bad.
  if ($source == realpath($destination)) {
    drupal_set_message(t('The specified file %file was not copied because it would overwrite itself.', array('%file' => $source)), 'error');
    return FALSE;
  }
  if (!@copy($source, $destination)) {
    drupal_set_message(t('The specified file %file could not be copied.', array('%file' => $source)), 'error');
    return FALSE;
  }

  // Set the permissions on the new file.
  drupal_chmod($destination);

  return $destination;
}

/**
 * Determines the destination path for a file depending on how replacement of
 * existing files should be handled.
 *
 * @param $destination
 *   A string specifying the desired path.
 * @param $replace
 *   Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   The destination file path or FALSE if the file already exists and
 *   FILE_EXISTS_ERROR was specified.
 */
function file_destination($destination, $replace) {
  if (file_exists($destination)) {
    switch ($replace) {
      case FILE_EXISTS_REPLACE:
        // Do nothing here, we want to overwrite the existing file.
        break;

      case FILE_EXISTS_RENAME:
        $basename = basename($destination);
        $directory = dirname($destination);
        $destination = file_create_filename($basename, $directory);
        break;

      case FILE_EXISTS_ERROR:
        drupal_set_message(t('The specified file %file could not be copied, because a file by that name already exists in the destination.', array('%file' => $destination)), 'error');
        return FALSE;
    }
  }
  return $destination;
}

/**
 * Move a file to a new location and update the file's database entry.
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
 *   A string containing the destination that $source should be moved to. This
 *   can be a complete file path, a directory path or, if this value is omitted,
 *   Drupal's 'files' directory will be used.
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
 * @return
 *   Resulting file object for success, or FALSE in the event of an error.
 *
 * @see file_unmanaged_move()
 * @see hook_file_move()
 */
function file_move($source, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  $source = (object)$source;

  if ($filepath = file_unmanaged_move($source->filepath, $destination, $replace)) {
    $delete_source = FALSE;

    $file = clone $source;
    $file->filepath = $filepath;
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('filepath' => $filepath));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $delete_source = TRUE;
        $file->fid = $existing->fid;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    else if ($replace == FILE_EXISTS_RENAME && is_file(file_create_path($destination))) {
      $file->filename = basename($destination);
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
 * Move a file to a new location without calling any hooks or making any
 * changes to the database.
 *
 * @param $source
 *   A string specifying the file location of the original file.
 * @param $destination
 *   A string containing the destination that $source should be moved to. This
 *   can be a complete file path, a directory name or, if this value is omitted,
 *   Drupal's 'files' directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   The filepath of the moved file, or FALSE in the event of an error.
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
 * Munge the filename as needed for security purposes.
 *
 * For instance the file name "exploit.php.pps" would become "exploit.php_.pps".
 *
 * @param $filename
 *   The name of a file to modify.
 * @param $extensions
 *   A space separated list of extensions that should not be altered.
 * @param $alerts
 *   Whether alerts (watchdog, drupal_set_message()) should be displayed.
 * @return
 *   $filename The potentially modified $filename.
 */
function file_munge_filename($filename, $extensions, $alerts = TRUE) {
  $original = $filename;

  // Allow potentially insecure uploads for very savvy users and admin
  if (!variable_get('allow_insecure_uploads', 0)) {
    $whitelist = array_unique(explode(' ', trim($extensions)));

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
      if (!in_array($filename_part, $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
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
 * Undo the effect of upload_munge_filename().
 *
 * @param $filename
 *   String with the filename to be unmunged.
 * @return
 *   An unmunged filename string.
 */
function file_unmunge_filename($filename) {
  return str_replace('_.', '.', $filename);
}

/**
 * Create a full file path from a directory and filename.
 *
 * If a file with the specified name already exists, an alternative will be
 * used.
 *
 * @param $basename
 *   String filename
 * @param $directory
 *   String directory
 * @return
 *   File path consisting of $directory and a unique filename based off
 *   of $basename.
 */
function file_create_filename($basename, $directory) {
  $destination = $directory . '/' . $basename;

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
      $destination = $directory . '/' . $name . '_' . $counter++ . $ext;
    } while (file_exists($destination));
  }

  return $destination;
}

/**
 * Delete a file and its database record.
 *
 * If the $force parameter is not TRUE hook_file_references() will be called
 * to determine if the file is being used by any modules. If the file is being
 * used is the delete will be canceled.
 *
 * @param $file
 *   A file object.
 * @param $force
 *   Boolean indicating that the file should be deleted even if
 *   hook_file_references() reports that the file is in use.
 * @return mixed
 *   TRUE for success, FALSE in the event of an error, or an array if the file
 *   is being used by another module. The array keys are the module's name and
 *   the values are the number of references.
 *
 * @see file_unmanaged_delete()
 * @see hook_file_references()
 * @see hook_file_delete()
 */
function file_delete($file, $force = FALSE) {
  $file = (object)$file;

  // If any module returns a value from the reference hook, the file will not
  // be deleted from Drupal, but file_delete will return a populated array that
  // tests as TRUE.
  if (!$force && ($references = module_invoke_all('file_references', $file))) {
    return $references;
  }

  // Let other modules clean up any references to the deleted file.
  module_invoke_all('file_delete', $file);

  // Make sure the file is deleted before removing its row from the
  // database, so UIs can still find the file in the database.
  if (file_unmanaged_delete($file->filepath)) {
    db_delete('files')->condition('fid', $file->fid)->execute();
    return TRUE;
  }
  return FALSE;
}

/**
 * Delete a file without calling any hooks or making any changes to the
 * database.
 *
 * This function should be used when the file to be deleted does not have an
 * entry recorded in the files table.
 *
 * @param $path
 *   A string containing a file path.
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
    return unlink($path);
  }
  // Return TRUE for non-existent file, but log that nothing was actually
  // deleted, as the current state is the indended result.
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
 * Recursively delete all files and directories in the specified filepath.
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
 *   A string containing a file or directory path.
 * @return
 *   TRUE for success or path does not exist, or FALSE in the event of an
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
    return rmdir($path);
  }
  return file_unmanaged_delete($path);
}

/**
 * Determine total disk space used by a single user or the whole filesystem.
 *
 * @param $uid
 *   Optional. A user id, specifying NULL returns the total space used by all
 *   non-temporary files.
 * @param $status
 *   Optional. File Status to return. Combine with a bitwise OR(|) to return
 *   multiple statuses. The default status is FILE_STATUS_PERMANENT.
 * @return
 *   An integer containing the number of bytes used.
 */
function file_space_used($uid = NULL, $status = FILE_STATUS_PERMANENT) {
  $query = db_select('files', 'f');
  // Use separate placeholders for the status to avoid a bug in some versions
  // of PHP. @see http://drupal.org/node/352956
  $query->where('f.status & :status1 = :status2', array(':status1' => $status, ':status2' => $status));
  $query->addExpression('SUM(f.filesize)', 'filesize');
  if (!is_null($uid)) {
    $query->condition('f.uid', $uid);
  }
  return $query->execute()->fetchField();
}

/**
 * Saves a file upload to a new location.
 *
 * The file will be added to the files table as a temporary file. Temporary
 * files are periodically cleaned. To make the file a permanent file call
 * assign the status and use file_save() to save it.
 *
 * @param $source
 *   A string specifying the name of the upload field to save.
 * @param $validators
 *   An optional, associative array of callback functions used to validate the
 *   file. See file_validate() for a full discussion of the array format.
 * @param $destination
 *   A string containing the directory $source should be copied to. If this is
 *   not provided or is not writable, the temporary directory will be used.
 * @param $replace
 *   A boolean indicating whether an existing file of the same name in the
 *   destination directory should overwritten. A false value will generate a
 *   new, unique filename in the destination directory.
 * @return
 *   An object containing the file information if the upload succeeded, FALSE
 *   in the event of an error, or NULL if no file was uploaded.
 */
function file_save_upload($source, $validators = array(), $destination = FALSE, $replace = FILE_EXISTS_RENAME) {
  global $user;
  static $upload_cache;

  // Return cached objects without processing since the file will have
  // already been processed and the paths in _FILES will be invalid.
  if (isset($upload_cache[$source])) {
    return $upload_cache[$source];
  }

  // Make sure there's an upload to process.
  if (empty($_FILES['files']['name'][$source])) {
    return NULL;
  }

  // Check for file upload errors and return FALSE if a lower level system
  // error occurred. For a complete list of errors:
  // @see http://php.net/manual/en/features.file-upload.errors.php
  switch ($_FILES['files']['error'][$source]) {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      drupal_set_message(t('The file %file could not be saved, because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $_FILES['files']['name'][$source], '%maxsize' => format_size(file_upload_max_size()))), 'error');
      return FALSE;

    case UPLOAD_ERR_PARTIAL:
    case UPLOAD_ERR_NO_FILE:
      drupal_set_message(t('The file %file could not be saved, because the upload did not complete.', array('%file' => $_FILES['files']['name'][$source])), 'error');
      return FALSE;

    case UPLOAD_ERR_OK:
      // Final check that this is a valid upload, if it isn't, use the
      // default error handler.
      if (is_uploaded_file($_FILES['files']['tmp_name'][$source])) {
        break;
      }

    // Unknown error
    default:
      drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => $_FILES['files']['name'][$source])), 'error');
      return FALSE;
  }

  // Build the list of non-munged extensions.
  // @todo: this should not be here. we need to figure out the right place.
  $extensions = '';
  foreach ($user->roles as $rid => $name) {
    $extensions .= ' ' . variable_get("upload_extensions_$rid",
    variable_get('upload_extensions_default', 'jpg jpeg gif png txt html doc xls pdf ppt pps odt ods odp'));
  }

  // Begin building file object.
  $file = new stdClass();
  $file->uid      = $user->uid;
  $file->status   = 0;
  $file->filename = file_munge_filename(trim(basename($_FILES['files']['name'][$source]), '.'), $extensions);
  $file->filepath = $_FILES['files']['tmp_name'][$source];
  $file->filemime = file_get_mimetype($file->filename);
  $file->filesize = $_FILES['files']['size'][$source];

  // Rename potentially executable files, to help prevent exploits.
  if (preg_match('/\.(php|pl|py|cgi|asp|js)$/i', $file->filename) && (substr($file->filename, -4) != '.txt')) {
    $file->filemime = 'text/plain';
    $file->filepath .= '.txt';
    $file->filename .= '.txt';
  }

  // If the destination is not provided, or is not writable, then use the
  // temporary directory.
  if (empty($destination) || file_check_path($destination) === FALSE) {
    $destination = file_directory_temp();
  }

  $file->source = $source;
  $file->destination = file_destination(file_create_path($destination . '/' . $file->filename), $replace);
  // If file_destination() returns FALSE then $replace == FILE_EXISTS_ERROR and
  // there's an existing file so we need to bail.
  if ($file->destination === FALSE) {
    return FALSE;
  }

  // Add in our check of the the file name length.
  $validators['file_validate_name_length'] = array();

  // Call the validation functions specified by this function's caller.
  $errors = file_validate($file, $validators);

  // Check for errors.
  if (!empty($errors)) {
    $message = t('The specified file %name could not be uploaded.', array('%name' => $file->filename));
    if (count($errors) > 1) {
      $message .= theme('item_list', $errors);
    }
    else {
      $message .= ' ' . array_pop($errors);
    }
    form_set_error($source, $message);
    return FALSE;
  }

  // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary
  // directory. This overcomes open_basedir restrictions for future file
  // operations.
  $file->filepath = $file->destination;
  if (!move_uploaded_file($_FILES['files']['tmp_name'][$source], $file->filepath)) {
    form_set_error($source, t('File upload error. Could not move uploaded file.'));
    watchdog('file', 'Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $file->filename, '%destination' => $file->filepath));
    return FALSE;
  }

  // Set the permissions on the new file.
  drupal_chmod($file->filepath);

  // If we are replacing an existing file re-use its database record.
  if ($replace == FILE_EXISTS_REPLACE) {
    $existing_files = file_load_multiple(array(), array('filepath' => $file->filepath));
    if (count($existing_files)) {
      $existing = reset($existing_files);
      $file->fid = $existing->fid;
    }
  }

  // If we made it this far it's safe to record this file in the database.
  if ($file = file_save($file)) {
    // Add file to the cache.
    $upload_cache[$source] = $file;
    return $file;
  }
  return FALSE;
}


/**
 * Check that a file meets the criteria specified by the validators.
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
 * @return
 *   An array contaning validation error messages.
 *
 * @see hook_file_validate()
 */
function file_validate(&$file, $validators = array()) {
  // Call the validation functions specified by this function's caller.
  $errors = array();
  foreach ($validators as $function => $args) {
    if (drupal_function_exists($function)) {
      array_unshift($args, $file);
      $errors = array_merge($errors, call_user_func_array($function, $args));
    }
  }

  // Let other modules perform validation on the new file.
  return array_merge($errors, module_invoke_all('file_validate', $file));
}

/**
 * Check for files with names longer than we can store in the database.
 *
 * @param $file
 *   A Drupal file object.
 * @return
 *   An array. If the file name is too long, it will contain an error message.
 */
function file_validate_name_length($file) {
  $errors = array();

  if (empty($file->filename)) {
    $errors[] = t("The file's name is empty. Please give a name to the file.");
  }
  if (strlen($file->filename) > 255) {
    $errors[] = t("The file's name exceeds the 255 characters limit. Please rename the file and try again.");
  }
  return $errors;
}

/**
 * Check that the filename ends with an allowed extension.
 *
 * @param $file
 *   A Drupal file object.
 * @param $extensions
 *   A string with a space separated list of allowed extensions.
 * @return
 *   An array. If the file extension is not allowed, it will contain an error
 *   message.
 *
 * @see hook_file_validate()
 */
function file_validate_extensions($file, $extensions) {
  $errors = array();

  $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
  if (!preg_match($regex, $file->filename)) {
    $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
  }
  return $errors;
}

/**
 * Check that the file's size is below certain limits.
 *
 * This check is not enforced for the user #1.
 *
 * @param $file
 *   A Drupal file object.
 * @param $file_limit
 *   An integer specifying the maximum file size in bytes. Zero indicates that
 *   no limit should be enforced.
 * @param $user_limit
 *   An integer specifying the maximum number of bytes the user is allowed.
 *   Zero indicates that no limit should be enforced.
 * @return
 *   An array. If the file size exceeds limits, it will contain an error
 *   message.
 *
 * @see hook_file_validate()
 */
function file_validate_size($file, $file_limit = 0, $user_limit = 0) {
  global $user;

  $errors = array();

  // Bypass validation for uid  = 1.
  if ($user->uid != 1) {
    if ($file_limit && $file->filesize > $file_limit) {
      $errors[] = t('The file is %filesize exceeding the maximum file size of %maxsize.', array('%filesize' => format_size($file->filesize), '%maxsize' => format_size($file_limit)));
    }

    // Save a query by only calling file_space_used() when a limit is provided.
    if ($user_limit && (file_space_used($user->uid) + $file->filesize) > $user_limit) {
      $errors[] = t('The file is %filesize which would exceed your disk quota of %quota.', array('%filesize' => format_size($file->filesize), '%quota' => format_size($user_limit)));
    }
  }
  return $errors;
}

/**
 * Check that the file is recognized by image_get_info() as an image.
 *
 * @param $file
 *   A Drupal file object.
 * @return
 *   An array. If the file is not an image, it will contain an error message.
 *
 * @see hook_file_validate()
 */
function file_validate_is_image($file) {
  $errors = array();

  $info = image_get_info($file->filepath);
  if (!$info || empty($info['extension'])) {
    $errors[] = t('Only JPEG, PNG and GIF images are allowed.');
  }

  return $errors;
}

/**
 * If the file is an image verify that its dimensions are within the specified
 * maximum and minimum dimensions.
 *
 * Non-image files will be ignored. If a image toolkit is available the image
 * will be scalled to fit within the desired maximum dimensions.
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
 * @return
 *   An array. If the file is an image and did not meet the requirements, it
 *   will contain an error message.
 *
 * @see hook_file_validate()
 */
function file_validate_image_resolution($file, $maximum_dimensions = 0, $minimum_dimensions = 0) {
  $errors = array();

  // Check first that the file is an image.
  if ($info = image_get_info($file->filepath)) {
    if ($maximum_dimensions) {
      // Check that it is smaller than the given dimensions.
      list($width, $height) = explode('x', $maximum_dimensions);
      if ($info['width'] > $width || $info['height'] > $height) {
        // Try to resize the image to fit the dimensions.
        if ($image = image_load($file->filepath)) {
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
 * Save a string to the specified destination and create a database file entry.
 *
 * @param $data
 *   A string containing the contents of the file.
 * @param $destination
 *   A string containing the destination location. If no value is provided
 *   then a randomly name will be generated and the file saved in Drupal's
 *   files directory.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file. If a managed file with
 *       the destination name exists then its database entry will be updated. If
 *       no database entry is found then a new one will be created.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *       unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   A file object, or FALSE on error.
 *
 * @see file_unmanaged_save_data()
 */
function file_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  global $user;

  if ($filepath = file_unmanaged_save_data($data, $destination, $replace)) {
    // Create a file object.
    $file = new stdClass();
    $file->fid = NULL;
    $file->filepath = $filepath;
    $file->filename = basename($filepath);
    $file->filemime = file_get_mimetype($file->filepath);
    $file->uid      = $user->uid;
    $file->status  |= FILE_STATUS_PERMANENT;
    // If we are replacing an existing file re-use its database record.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = file_load_multiple(array(), array('filepath' => $filepath));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing->fid;
        $file->filename = $existing->filename;
      }
    }
    // If we are renaming around an existing file (rather than a directory),
    // use its basename for the filename.
    else if ($replace == FILE_EXISTS_RENAME && is_file(file_create_path($destination))) {
      $file->filename = basename($destination);
    }

    return file_save($file);
  }
  return FALSE;
}

/**
 * Save a string to the specified destination without calling any hooks or
 * making any changes to the database.
 *
 * This function is identical to file_save_data() except the file will not be
 * saved to the files table and none of the file_* hooks will be called.
 *
 * @param $data
 *   A string containing the contents of the file.
 * @param $destination
 *   A string containing the destination location. If no value is provided
 *   then a randomly name will be generated and the file saved in Drupal's
 *   files directory.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE - Replace the existing file.
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *                          unique.
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return
 *   A string with the path of the resulting file, or FALSE on error.
 *
 * @see file_save_data()
 */
function file_unmanaged_save_data($data, $destination = NULL, $replace = FILE_EXISTS_RENAME) {
  // Write the data to a temporary file.
  $temp_name = tempnam(file_directory_temp(), 'file');
  if (file_put_contents($temp_name, $data) === FALSE) {
    drupal_set_message(t('The file could not be created.'), 'error');
    return FALSE;
  }

  // Move the file to its final destination.
  return file_unmanaged_move($temp_name, $destination, $replace);
}

/**
 * Transfer file using HTTP to client. Pipes a file through Drupal to the
 * client.
 *
 * @param $source
 *   String specifying the file path to transfer.
 * @param $headers
 *   An array of HTTP headers to send along with file.
 */
function file_transfer($source, $headers) {
  if (ob_get_level()) {
    ob_end_clean();
  }

  foreach ($headers as $name => $value) {
    drupal_set_header($name, $value);
  }
  drupal_send_headers();

  $source = file_create_path($source);

  // Transfer file in 1024 byte chunks to save memory usage.
  if ($fd = fopen($source, 'rb')) {
    while (!feof($fd)) {
      print fread($fd, 1024);
    }
    fclose($fd);
  }
  else {
    drupal_not_found();
  }
  exit();
}

/**
 * Menu handler for private file transfers.
 *
 * Call modules that implement hook_file_download() to find out if a file is
 * accessible and what headers it should be transferred with. If a module
 * returns -1 drupal_access_denied() will be returned. If one or more modules
 * returned headers the download will start with the returned headers. If no
 * modules respond drupal_not_found() will be returned.
 *
 * @see hook_file_download()
 */
function file_download() {
  // Merge remainder of arguments from GET['q'], into relative file path.
  $args = func_get_args();
  $filepath = implode('/', $args);

  // Maintain compatibility with old ?file=paths saved in node bodies.
  if (isset($_GET['file'])) {
    $filepath =  $_GET['file'];
  }

  if (file_exists(file_create_path($filepath))) {
    // Let other modules provide headers and controls access to the file.
    $headers = module_invoke_all('file_download', $filepath);
    if (in_array(-1, $headers)) {
      return drupal_access_denied();
    }
    if (count($headers)) {
      file_transfer($filepath, $headers);
    }
  }
  return drupal_not_found();
}


/**
 * Finds all files that match a given mask in a given directory.
 *
 * Directories and files beginning with a period are excluded; this
 * prevents hidden files and directories (such as SVN working directories)
 * from being scanned.
 *
 * @param $dir
 *   The base directory for the scan, without trailing slash.
 * @param $mask
 *   The preg_match() regular expression of the files to find.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - 'nomask'
 *     The preg_match() regular expression of the files to ignore. Defaults to
 *     '/(\.\.?|CVS)$/'.
 *   - 'callback'
 *     The callback function to call for each match. There is no default
 *     callback.
 *   - 'recurse'
 *     When TRUE, the directory scan will recurse the entire tree starting at
 *     the provided directory. Defaults to TRUE.
 *   - 'key'
 *     The key to be used for the returned array of files. Possible values are
 *     'filepath', for the path starting with $dir, 'filename', for the
 *     basename of the file, and 'name' for the name of the file without an
 *     extension. Defaults to 'filepath'.
 *   - 'min_depth'
 *     Minimum depth of directories to return files from. Defaults to 0.
 * @param $depth
 *   Current depth of recursion. This parameter is only used internally and
 *   should not be passed.
 * @return
 *   An associative array (keyed on the provided key) of objects with
 *   'filepath', 'filename', and 'name' members corresponding to the
 *   matching files.
 */
function file_scan_directory($dir, $mask, $options = array(), $depth = 0) {
  // Merge in defaults.
  $options += array(
    'nomask' => '/(\.\.?|CVS)$/',
    'callback' => 0,
    'recurse' => TRUE,
    'key' => 'filepath',
    'min_depth' => 0,
  );

  $options['key'] = in_array($options['key'], array('filepath', 'filename', 'name')) ? $options['key'] : 'filepath';
  $files = array();
  if (is_dir($dir) && $handle = opendir($dir)) {
    while (FALSE !== ($filename = readdir($handle))) {
      if (!preg_match($options['nomask'], $filename) && $filename[0] != '.') {
        $filepath = "$dir/$filename";
        if (is_dir($filepath) && $options['recurse']) {
          // Give priority to files in this folder by merging them in after any subdirectory files.
          $files = array_merge(file_scan_directory($filepath, $mask, $options, $depth + 1), $files);
        }
        elseif ($depth >= $options['min_depth'] && preg_match($mask, $filename)) {
          // Always use this match over anything already set in $files with the
          // same $$options['key'].
          $file = (object) array(
            'filepath' => $filepath,
            'filename' => $filename,
            'name' => pathinfo($filename, PATHINFO_FILENAME),
          );
          $key = $options['key'];
          $files[$file->$key] = $file;
          if ($options['callback']) {
            $options['callback']($filepath);
          }
        }
      }
    }

    closedir($handle);
  }

  return $files;
}

/**
 * Determine the default temporary directory.
 *
 * @return
 *   A string containing a temp directory.
 */
function file_directory_temp() {
  $temporary_directory = variable_get('file_directory_temp');

  if (is_null($temporary_directory)) {
    $directories = array();

    // Has PHP been set with an upload_tmp_dir?
    if (ini_get('upload_tmp_dir')) {
      $directories[] = ini_get('upload_tmp_dir');
    }

    // Operating system specific dirs.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      $directories[] = 'c:/windows/temp';
      $directories[] = 'c:/winnt/temp';
    }
    else {
      $directories[] = '/tmp';
    }

    foreach ($directories as $directory) {
      if (!$temporary_directory && is_dir($directory)) {
        $temporary_directory = $directory;
      }
    }

    // if a directory has been found, use it, otherwise default to 'files/tmp'
    $temporary_directory = $temporary_directory ? $temporary_directory : file_directory_path() . '/tmp';
    variable_set('file_directory_temp', $temporary_directory);
  }

  return $temporary_directory;
}

/**
 * Determine the default 'files' directory.
 *
 * @return
 *   A string containing the path to Drupal's 'files' directory.
 */
function file_directory_path() {
  return variable_get('file_directory_path', conf_path() . '/files');
}

/**
 * Remove a possible leading file directory path from the given path.
 *
 * @param $path
 *   Path to a file that may be in Drupal's files directory.
 * @return
 *   String with Drupal's files directory removed from it.
 */
function file_directory_strip($path) {
  // Strip file_directory_path from $path. We only include relative paths in
  // URLs.
  if (strpos($path, file_directory_path() . '/') === 0) {
    $path = trim(substr($path, strlen(file_directory_path())), '\\/');
  }
  return $path;
}

/**
 * Determine the maximum file upload size by querying the PHP settings.
 *
 * @return
 *   A file size limit in bytes based on the PHP upload_max_filesize and
 *   post_max_size
 */
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    $upload_max = parse_size(ini_get('upload_max_filesize'));
    $post_max = parse_size(ini_get('post_max_size'));
    $max_size = ($upload_max < $post_max) ? $upload_max : $post_max;
  }
  return $max_size;
}

/**
 * Determine an Internet Media Type, or MIME type from a filename.
 *
 * @param $filename
 *   Name of the file, including extension.
 * @param $mapping
 *   An optional map of extensions to their mimetypes, in the form:
 *    - 'mimetypes': a list of mimetypes, keyed by an identifier,
 *    - 'extensions': the mapping itself, an associative array in which
 *      the key is the extension (lowercase) and the value is the mimetype identifier.
 *    If $mapping is omitted, the drupal variable mime_extension_mapping is checked
 *    for a value and if that fails then file_default_mimetype_mapping() is called
 *
 * @return
 *   The internet media type registered for the extension or application/octet-stream for unknown extensions.
 * @see
 *   file_default_mimetype_mapping()
 */
function file_get_mimetype($filename, $mapping = NULL) {
  if (!isset($mapping)) {
    $mapping = variable_get('mime_extension_mapping', NULL);
    if (!isset($mapping) && drupal_function_exists('file_default_mimetype_mapping')) {
      // The default file map, defined in file.mimetypes.inc is quite big.
      // We only load it when necessary.
      $mapping = file_default_mimetype_mapping();
    }
  }

  $extension = '';
  $file_parts = explode('.', $filename);

  // Remove the first part: a full filename should not match an extension.
  array_shift($file_parts);

  // Iterate over the file parts, trying to find a match.
  // For my.awesome.image.jpeg, we try:
  //   - jpeg
  //   - image.jpeg, and
  //   - awesome.image.jpeg
  while ($additional_part = array_pop($file_parts)) {
    $extension = strtolower($additional_part . ($extension ? '.' . $extension : ''));
    if (isset($mapping['extensions'][$extension])) {
      return $mapping['mimetypes'][$mapping['extensions'][$extension]];
    }
  }

  return 'application/octet-stream';
}

/**
 * Set the permissions on a file or directory.
 *
 * This function will use the 'file_chmod_directory' and 'file_chmod_file'
 * variables for the default modes for directories and uploaded/generated files.
 * By default these will give everyone read access so that users accessing the
 * files with a user account without the webserver group (e.g. via FTP) can read
 * these files, and give group write permissions so webserver group members
 * (e.g. a vhost account) can alter files uploaded and owned by the webserver.
 *
 * @param $path
 *   String containing the path to a file or directory.
 * @param $mode
 *   Integer value for the permissions. Consult PHP chmod() documentation for
 *   more information.
 * @return
 *   TRUE for success, FALSE in the event of an error.
 */
function drupal_chmod($path, $mode = NULL) {
  if (!isset($mode)) {
    if (is_dir($path)) {
      $mode = variable_get('file_chmod_directory', 0775);
    }
    else {
      $mode = variable_get('file_chmod_file', 0664);
    }
  }

  if (@chmod($path, $mode)) {
    return TRUE;
  }

  watchdog('file', 'The file permissions could not be set on %path.', array('%path' => $path), WATCHDOG_ERROR);
  return FALSE;
}

/**
 * @} End of "defgroup file".
 */
