<?php

/**
 * @file
 * API for handling file uploads and server file management.
 */

/**
 * @defgroup file File interface
 * @{
 * Common file handling functions.
 */

define('FILE_DOWNLOADS_PUBLIC', 1);
define('FILE_DOWNLOADS_PRIVATE', 2);
define('FILE_CREATE_DIRECTORY', 1);
define('FILE_MODIFY_PERMISSIONS', 2);
define('FILE_EXISTS_RENAME', 0);
define('FILE_EXISTS_REPLACE', 1);
define('FILE_EXISTS_ERROR', 2);

/**
 * A files status can be one of two values: temporary or permanent. The status
 * for each file Drupal manages is stored in the {files} tables. If the status
 * is temporary Drupal's file garbage collection will delete the file and
 * remove it from the files table after a set period of time.
 *
 * If you wish to add custom statuses for use by contrib modules please expand as
 * binary flags and consider the first 8 bits reserved. (0,1,2,4,8,16,32,64,128)
 */
define('FILE_STATUS_TEMPORARY', 0);
define('FILE_STATUS_PERMANENT', 1);

/**
 * Create the download path to a file.
 *
 * @param $path A string containing the path of the file to generate URL for.
 * @return A string containing a URL that can be used to download the file.
 */
function file_create_url($path) {
  // Strip file_directory_path from $path. We only include relative paths in urls.
  if (strpos($path, file_directory_path() .'/') === 0) {
    $path = trim(substr($path, strlen(file_directory_path())), '\\/');
  }
  switch (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC)) {
    case FILE_DOWNLOADS_PUBLIC:
      return $GLOBALS['base_url'] .'/'. file_directory_path() .'/'. str_replace('\\', '/', $path);
    case FILE_DOWNLOADS_PRIVATE:
      return url('system/files/'. $path, array('absolute' => TRUE));
  }
}

/**
 * Make sure the destination is a complete path and resides in the file system
 * directory, if it is not prepend the file system directory.
 *
 * @param $dest A string containing the path to verify. If this value is
 *   omitted, Drupal's 'files' directory will be used.
 * @return A string containing the path to file, with file system directory
 *   appended if necessary, or FALSE if the path is invalid (i.e. outside the
 *   configured 'files' or temp directories).
 */
function file_create_path($dest = 0) {
  $file_path = file_directory_path();
  if (!$dest) {
    return $file_path;
  }
  // file_check_location() checks whether the destination is inside the Drupal files directory.
  if (file_check_location($dest, $file_path)) {
    return $dest;
  }
  // check if the destination is instead inside the Drupal temporary files directory.
  else if (file_check_location($dest, file_directory_temp())) {
    return $dest;
  }
  // Not found, try again with prefixed directory path.
  else if (file_check_location($file_path .'/'. $dest, $file_path)) {
    return $file_path .'/'. $dest;
  }
  // File not found.
  return FALSE;
}

/**
 * Checks whether a directory exists and is writable.
 *
 * Furthermore, the directory can optionally be created if it does not exist,
 * and/or be set to writable if it is currently not. Directories need to have
 * execute permission to be considered a directory by FTP servers.
 *
 * @param $directory
 *   A string representing the directory path.
 * @param $mode
 *   An optional bitmask containing the actions, if any, to be carried out on
 *   the directory. Any combination of the actions FILE_CREATE_DIRECTORY and
 *   FILE_MODIFY_PERMISSIONS is allowed.
 * @param $form_item
 *   An optional string containing the name of a form item that any errors
 *   will be attached to. Useful when the function validates a directory path
 *   entered as a form value. An error will consequently prevent form submit
 *   handlers from running, and instead display the form along with the
 *   error messages.
 *
 * @return
 *   FALSE if the directory does not exist or is not writable, even after
 *   any optional actions have been carried out. Otherwise, TRUE is returned.
 */
function file_check_directory(&$directory, $mode = 0, $form_item = NULL) {
  $directory = rtrim($directory, '/\\');

  // Check if directory exists.
  if (!is_dir($directory)) {
    if (($mode & FILE_CREATE_DIRECTORY) && @mkdir($directory)) {
      drupal_set_message(t('The directory %directory has been created.', array('%directory' => $directory)));
      @chmod($directory, 0775); // Necessary for non-webserver users.
    }
    else {
      if ($form_item) {
        form_set_error($form_item, t('The directory %directory does not exist.', array('%directory' => $directory)));
      }
      return FALSE;
    }
  }

  // Check to see if the directory is writable.
  if (!is_writable($directory)) {
    if (($mode & FILE_MODIFY_PERMISSIONS) && @chmod($directory, 0775)) {
      drupal_set_message(t('The permissions of directory %directory have been changed to make it writable.', array('%directory' => $directory)));
    }
    else {
      form_set_error($form_item, t('The directory %directory is not writable', array('%directory' => $directory)));
      watchdog('file system', 'The directory %directory is not writable, because it does not have the correct permissions set.', array('%directory' => $directory), WATCHDOG_ERROR);
      return FALSE;
    }
  }

  if ((file_directory_path() == $directory || file_directory_temp() == $directory) && !is_file("$directory/.htaccess")) {
    $htaccess_lines = "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nOptions None\nOptions +FollowSymLinks";
    if (($fp = fopen("$directory/.htaccess", 'w')) && fputs($fp, $htaccess_lines)) {
      fclose($fp);
      chmod($directory .'/.htaccess', 0664);
    }
    else {
      $variables = array('%directory' => $directory, '!htaccess' => '<br />'. nl2br(check_plain($htaccess_lines)));
      form_set_error($form_item, t("Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>!htaccess</code>", $variables));
      watchdog('security', "Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>!htaccess</code>", $variables, WATCHDOG_ERROR);
    }
  }

  return TRUE;
}

/**
 * Checks path to see if it is a directory, or a dir/file.
 *
 * @param $path A string containing a file path. This will be set to the
 *   directory's path.
 * @return If the directory is not in a Drupal writable directory, FALSE is
 *   returned. Otherwise, the base name of the path is returned.
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
 * Check if a file is really located inside $directory. Should be used to make
 * sure a file specified is really located within the directory to prevent
 * exploits.
 *
 * @code
 *   // Returns FALSE:
 *   file_check_location('/www/example.com/files/../../../etc/passwd', '/www/example.com/files');
 * @endcode
 *
 * @param $source A string set to the file to check.
 * @param $directory A string where the file should be located.
 * @return 0 for invalid path or the real path of the source.
 */
function file_check_location($source, $directory = '') {
  $check = realpath($source);
  if ($check) {
    $source = $check;
  }
  else {
    // This file does not yet exist
    $source = realpath(dirname($source)) .'/'. basename($source);
  }
  $directory = realpath($directory);
  if ($directory && strpos($source, $directory) !== 0) {
    return 0;
  }
  return $source;
}

/**
 * Copies a file to a new location.
 *
 * This is a powerful function that in many ways performs like an advanced
 * version of copy().
 * - Checks if $source and $dest are valid and readable/writable.
 * - Performs a file copy if $source is not equal to $dest.
 * - If file already exists in $dest either the call will error out, replace the
 *   file or rename the file based on the $replace parameter.
 *
 * @param $source
 *   Either a string specifying the file location of the original file or an
 *   object containing a 'filepath' property. This parameter is passed by
 *   reference and will contain the resulting destination filename in case of
 *   success.
 * @param $dest
 *   A string containing the directory $source should be copied to. If this
 *   value is omitted, Drupal's 'files' directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE: Replace the existing file.
 *   - FILE_EXISTS_RENAME: Append _{incrementing number} until the filename is
 *     unique.
 *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
 * @return
 *   TRUE for success, FALSE for failure.
 */
function file_copy(&$source, $dest = 0, $replace = FILE_EXISTS_RENAME) {
  $dest = file_create_path($dest);

  $directory = $dest;
  $basename = file_check_path($directory);

  // Make sure we at least have a valid directory.
  if ($basename === FALSE) {
    $source = is_object($source) ? $source->filepath : $source;
    drupal_set_message(t('The selected file %file could not be uploaded, because the destination %directory is not properly configured.', array('%file' => $source, '%directory' => $dest)), 'error');
    watchdog('file system', 'The selected file %file could not be uploaded, because the destination %directory could not be found, or because its permissions do not allow the file to be written.', array('%file' => $source, '%directory' => $dest), WATCHDOG_ERROR);
    return 0;
  }

  // Process a file upload object.
  if (is_object($source)) {
    $file = $source;
    $source = $file->filepath;
    if (!$basename) {
      $basename = $file->filename;
    }
  }

  $source = realpath($source);
  if (!file_exists($source)) {
    drupal_set_message(t('The selected file %file could not be copied, because no file by that name exists. Please check that you supplied the correct filename.', array('%file' => $source)), 'error');
    return 0;
  }

  // If the destination file is not specified then use the filename of the source file.
  $basename = $basename ? $basename : basename($source);
  $dest = $directory .'/'. $basename;

  // Make sure source and destination filenames are not the same, makes no sense
  // to copy it if they are. In fact copying the file will most likely result in
  // a 0 byte file. Which is bad. Real bad.
  if ($source != realpath($dest)) {
    if (!$dest = file_destination($dest, $replace)) {
      drupal_set_message(t('The selected file %file could not be copied, because a file by that name already exists in the destination.', array('%file' => $source)), 'error');
      return FALSE;
    }

    if (!@copy($source, $dest)) {
      drupal_set_message(t('The selected file %file could not be copied.', array('%file' => $source)), 'error');
      return 0;
    }

    // Give everyone read access so that FTP'd users or
    // non-webserver users can see/read these files,
    // and give group write permissions so group members
    // can alter files uploaded by the webserver.
    @chmod($dest, 0664);
  }

  if (isset($file) && is_object($file)) {
    $file->filename = $basename;
    $file->filepath = $dest;
    $source = $file;
  }
  else {
    $source = $dest;
  }

  return 1; // Everything went ok.
}

/**
 * Determines the destination path for a file depending on how replacement of
 * existing files should be handled.
 *
 * @param $destination A string specifying the desired path.
 * @param $replace Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is
 *     unique
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 * @return The destination file path or FALSE if the file already exists and
 *   FILE_EXISTS_ERROR was specified.
 */
function file_destination($destination, $replace) {
  if (file_exists($destination)) {
    switch ($replace) {
      case FILE_EXISTS_RENAME:
        $basename = basename($destination);
        $directory = dirname($destination);
        $destination = file_create_filename($basename, $directory);
        break;

      case FILE_EXISTS_ERROR:
        drupal_set_message(t('The selected file %file could not be copied, because a file by that name already exists in the destination.', array('%file' => $destination)), 'error');
        return FALSE;
    }
  }
  return $destination;
}

/**
 * Moves a file to a new location.
 *
 * - Checks if $source and $dest are valid and readable/writable.
 * - Performs a file move if $source is not equal to $dest.
 * - If file already exists in $dest either the call will error out, replace the
 *   file or rename the file based on the $replace parameter.
 *
 * @param $source
 *   Either a string specifying the file location of the original file or an
 *   object containing a 'filepath' property. This parameter is passed by
 *   reference and will contain the resulting destination filename in case of
 *   success.
 * @param $dest
 *   A string containing the directory $source should be copied to. If this
 *   value is omitted, Drupal's 'files' directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE: Replace the existing file.
 *   - FILE_EXISTS_RENAME: Append _{incrementing number} until the filename is
 *     unique.
 *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
 * @return
 *   TRUE for success, FALSE for failure.
 */
function file_move(&$source, $dest = 0, $replace = FILE_EXISTS_RENAME) {
  $path_original = is_object($source) ? $source->filepath : $source;

  if (file_copy($source, $dest, $replace)) {
    $path_current = is_object($source) ? $source->filepath : $source;

    if ($path_original == $path_current || file_delete($path_original)) {
      return 1;
    }
    drupal_set_message(t('The removal of the original file %file has failed.', array('%file' => $path_original)), 'error');
  }
  return 0;
}

/**
 * Modify a filename as needed for security purposes.
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
      $new_filename .= '.'. $filename_part;
      if (!in_array($filename_part, $whitelist) && preg_match("/^[a-zA-Z]{2,5}\d?$/", $filename_part)) {
        $new_filename .= '_';
      }
    }
    $filename = $new_filename .'.'. $final_extension;

    if ($alerts && $original != $filename) {
      drupal_set_message(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $filename)));
    }
  }

  return $filename;
}

/**
 * Undo the effect of upload_munge_filename().
 *
 * @param $filename string filename
 * @return string
 */
function file_unmunge_filename($filename) {
  return str_replace('_.', '.', $filename);
}

/**
 * Create a full file path from a directory and filename. If a file with the
 * specified name already exists, an alternative will be used.
 *
 * @param $basename string filename
 * @param $directory string directory
 * @return
 */
function file_create_filename($basename, $directory) {
  $dest = $directory .'/'. $basename;

  if (file_exists($dest)) {
    // Destination file already exists, generate an alternative.
    if ($pos = strrpos($basename, '.')) {
      $name = substr($basename, 0, $pos);
      $ext = substr($basename, $pos);
    }
    else {
      $name = $basename;
    }

    $counter = 0;
    do {
      $dest = $directory .'/'. $name .'_'. $counter++ . $ext;
    } while (file_exists($dest));
  }

  return $dest;
}

/**
 * Delete a file.
 *
 * @param $path A string containing a file path.
 * @return TRUE for success, FALSE for failure.
 */
function file_delete($path) {
  if (is_file($path)) {
    return unlink($path);
  }
}

/**
 * Determine total disk space used by a single user or the whole filesystem.
 *
 * @param $uid
 *   An optional user id. A NULL value returns the total space used
 *   by all files.
 */
function file_space_used($uid = NULL) {
  if (isset($uid)) {
    return (int) db_result(db_query('SELECT SUM(filesize) FROM {files} WHERE uid = %d', $uid));
  }
  return (int) db_result(db_query('SELECT SUM(filesize) FROM {files}'));
}

/**
 * Saves a file upload to a new location.
 *
 * The source file is validated as a proper upload and handled as such.
 * The file will be added to the files table as a temporary file. Temporary
 * files are periodically cleaned. To make the file permanent file call
 * file_set_status() to change its status.
 *
 * @param $source
 *   A string specifying the name of the upload field to save.
 * @param $validators
 *   (optional) An associative array of callback functions used to validate the
 *   file. The keys are function names and the values arrays of callback
 *   parameters which will be passed in after the file object. The
 *   functions should return an array of error messages; an empty array
 *   indicates that the file passed validation. The functions will be called in
 *   the order specified.
 * @param $dest
 *   A string containing the directory $source should be copied to. If this is
 *   not provided or is not writable, the temporary directory will be used.
 * @param $replace
 *   Replace behavior when the destination file already exists:
 *   - FILE_EXISTS_REPLACE: Replace the existing file.
 *   - FILE_EXISTS_RENAME: Append _{incrementing number} until the filename
 *     is unique.
 *   - FILE_EXISTS_ERROR: Do nothing and return FALSE.
 *
 * @return
 *   An object containing the file information, or 0 in the event of an error.
 */
function file_save_upload($source, $validators = array(), $dest = FALSE, $replace = FILE_EXISTS_RENAME) {
  global $user;
  static $upload_cache;

  // Add in our check of the the file name length.
  $validators['file_validate_name_length'] = array();

  // Return cached objects without processing since the file will have
  // already been processed and the paths in _FILES will be invalid.
  if (isset($upload_cache[$source])) {
    return $upload_cache[$source];
  }

  // If a file was uploaded, process it.
  if (isset($_FILES['files']) && $_FILES['files']['name'][$source] && is_uploaded_file($_FILES['files']['tmp_name'][$source])) {
    // Check for file upload errors and return FALSE if a
    // lower level system error occurred.
    switch ($_FILES['files']['error'][$source]) {
      // @see http://php.net/manual/en/features.file-upload.errors.php
      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        drupal_set_message(t('The file %file could not be saved, because it exceeds %maxsize, the maximum allowed size for uploads.', array('%file' => $source, '%maxsize' => format_size(file_upload_max_size()))), 'error');
        return 0;

      case UPLOAD_ERR_PARTIAL:
      case UPLOAD_ERR_NO_FILE:
        drupal_set_message(t('The file %file could not be saved, because the upload did not complete.', array('%file' => $source)), 'error');
        return 0;

        // Unknown error
      default:
        drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => $source)), 'error');
        return 0;
    }

    // Build the list of non-munged extensions.
    // @todo: this should not be here. we need to figure out the right place.
    $extensions = '';
    foreach ($user->roles as $rid => $name) {
      $extensions .= ' '. variable_get("upload_extensions_$rid",
      variable_get('upload_extensions_default', 'jpg jpeg gif png txt html doc xls pdf ppt pps odt ods odp'));
    }

    // Begin building file object.
    $file = new stdClass();
    $file->filename = file_munge_filename(trim(basename($_FILES['files']['name'][$source]), '.'), $extensions);
    $file->filepath = $_FILES['files']['tmp_name'][$source];
    $file->filemime = file_get_mimetype($file->filename);

    // If the destination is not provided, or is not writable, then use the
    // temporary directory.
    if (empty($dest) || file_check_path($dest) === FALSE) {
      $dest = file_directory_temp();
    }

    $file->source = $source;
    $file->destination = file_destination(file_create_path($dest .'/'. $file->filename), $replace);
    $file->filesize = $_FILES['files']['size'][$source];

    // Call the validation functions.
    $errors = array();
    foreach ($validators as $function => $args) {
      array_unshift($args, $file);
      // Make sure $file is passed around by reference.
      $args[0] = &$file;
      $errors = array_merge($errors, call_user_func_array($function, $args));
    }

    // Rename potentially executable files, to help prevent exploits.
    if (preg_match('/\.(php|pl|py|cgi|asp|js)$/i', $file->filename) && (substr($file->filename, -4) != '.txt')) {
      $file->filemime = 'text/plain';
      $file->filepath .= '.txt';
      $file->filename .= '.txt';
      // As the file may be named example.php.txt, we need to munge again to
      // convert to example.php_.txt, then create the correct destination.
      $file->filename = file_munge_filename($file->filename, $extensions);
      $file->destination = file_destination(file_create_path($dest .'/'. $file->filename), $replace);
    }


    // Check for validation errors.
    if (!empty($errors)) {
      $message = t('The selected file %name could not be uploaded.', array('%name' => $file->filename));
      if (count($errors) > 1) {
        $message .= '<ul><li>'. implode('</li><li>', $errors) .'</li></ul>';
      }
      else {
        $message .= ' '. array_pop($errors);
      }
      form_set_error($source, $message);
      return 0;
    }

    // Move uploaded files from PHP's upload_tmp_dir to Drupal's temporary directory.
    // This overcomes open_basedir restrictions for future file operations.
    $file->filepath = $file->destination;
    if (!move_uploaded_file($_FILES['files']['tmp_name'][$source], $file->filepath)) {
      form_set_error($source, t('File upload error. Could not move uploaded file.'));
      watchdog('file', 'Upload error. Could not move uploaded file %file to destination %destination.', array('%file' => $file->filename, '%destination' => $file->filepath));
      return 0;
    }

    // If we made it this far it's safe to record this file in the database.
    $file->uid = $user->uid;
    $file->status = FILE_STATUS_TEMPORARY;
    $file->timestamp = time();
    drupal_write_record('files', $file);

    // Add file to the cache.
    $upload_cache[$source] = $file;
    return $file;
  }
  return 0;
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

  if (strlen($file->filename) > 255) {
    $errors[] = t('Its name exceeds the 255 characters limit. Please rename the file and try again.');
  }
  return $errors;
}

/**
 * Check that the filename ends with an allowed extension. This check is not
 * enforced for the user #1.
 *
 * @param $file
 *   A Drupal file object.
 * @param $extensions
 *   A string with a space separated list of allowed file extensions, not
 *   including the period. For example, 'bmp jpg gif png'.
 *
 * @return
 *   An array. If the file extension is not allowed, it will contain an error
 *   message.
 */
function file_validate_extensions($file, $extensions) {
  global $user;

  $errors = array();

  // Bypass validation for uid  = 1.
  if ($user->uid != 1) {
    $regex = '/\.('. ereg_replace(' +', '|', preg_quote($extensions)) .')$/i';
    if (!preg_match($regex, $file->filename)) {
      $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
    }
  }
  return $errors;
}

/**
 * Check that the file's size is below certain limits. This check is not
 * enforced for the user #1.
 *
 * @param $file
 *   A Drupal file object.
 * @param $file_limit
 *   An integer specifying the maximum file size in bytes. Zero indicates that
 *   no limit should be enforced.
 * @param $user_limit
 *   An integer specifying the maximum number of bytes the user is allowed. Zero
 *   indicates that no limit should be enforced.
 * @return
 *   An array. If the file size exceeds limits, it will contain an error message.
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
 */
function file_validate_is_image(&$file) {
  $errors = array();

  $info = image_get_info($file->filepath);
  if (!$info || empty($info['extension'])) {
    $errors[] = t('Only JPEG, PNG and GIF images are allowed.');
  }

  return $errors;
}

/**
 * If the file is an image verify that its dimensions are within the specified
 * maximum and minimum dimensions. Non-image files will be ignored.
 *
 * @param $file
 *   A Drupal file object. This function may resize the file affecting its size.
 * @param $maximum_dimensions
 *   An optional string in the form WIDTHxHEIGHT e.g. '640x480' or '85x85'. If
 *   an image toolkit is installed the image will be resized down to these
 *   dimensions. A value of 0 indicates no restriction on size, so resizing
 *   will be attempted.
 * @param $minimum_dimensions
 *   An optional string in the form WIDTHxHEIGHT. This will check that the image
 *   meets a minimum size. A value of 0 indicates no restriction.
 * @return
 *   An array. If the file is an image and did not meet the requirements, it
 *   will contain an error message.
 */
function file_validate_image_resolution(&$file, $maximum_dimensions = 0, $minimum_dimensions = 0) {
  $errors = array();

  // Check first that the file is an image.
  if ($info = image_get_info($file->filepath)) {
    if ($maximum_dimensions) {
      // Check that it is smaller than the given dimensions.
      list($width, $height) = explode('x', $maximum_dimensions);
      if ($info['width'] > $width || $info['height'] > $height) {
        // Try to resize the image to fit the dimensions.
        if (image_get_toolkit() && image_scale($file->filepath, $file->filepath, $width, $height)) {
          drupal_set_message(t('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', array('%dimensions' => $maximum_dimensions)));

          // Clear the cached filesize and refresh the image information.
          clearstatcache();
          $info = image_get_info($file->filepath);
          $file->filesize = $info['file_size'];
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
 * Save a string to the specified destination.
 *
 * @param $data A string containing the contents of the file.
 * @param $dest A string containing the destination location.
 * @param $replace Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is unique
 *   - FILE_EXISTS_ERROR - Do nothing and return FALSE.
 *
 * @return A string containing the resulting filename or 0 on error
 */
function file_save_data($data, $dest, $replace = FILE_EXISTS_RENAME) {
  $temp = file_directory_temp();
  // On Windows, tempnam() requires an absolute path, so we use realpath().
  $file = tempnam(realpath($temp), 'file');
  if (!$fp = fopen($file, 'wb')) {
    drupal_set_message(t('The file could not be created.'), 'error');
    return 0;
  }
  fwrite($fp, $data);
  fclose($fp);

  if (!file_move($file, $dest, $replace)) {
    return 0;
  }

  return $file;
}

/**
 * Set the status of a file.
 *
 * @param file A Drupal file object
 * @param status A status value to set the file to.
 * @return FALSE on failure, TRUE on success and $file->status will contain the
 *     status.
 */
function file_set_status(&$file, $status) {
  if (db_query('UPDATE {files} SET status = %d WHERE fid = %d', $status, $file->fid)) {
    $file->status = $status;
    return TRUE;
  }
  return FALSE;
}

/**
 * Transfer file using http to client. Pipes a file through Drupal to the
 * client.
 *
 * @param $source File to transfer.
 * @param $headers An array of http headers to send along with file.
 */
function file_transfer($source, $headers) {
  if (ob_get_level()) {
    ob_end_clean();
  }
  
  // IE cannot download private files because it cannot store files downloaded
  // over https in the browser cache. The problem can be solved by sending
  // custom headers to IE. See http://support.microsoft.com/kb/323308/en-us
  if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
    drupal_set_header('Cache-Control: private');
    drupal_set_header('Pragma: private');
  }

  foreach ($headers as $header) {
    // To prevent HTTP header injection, we delete new lines that are
    // not followed by a space or a tab.
    // See http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
    $header = preg_replace('/\r?\n(?!\t| )/', '', $header);
    drupal_set_header($header);
  }

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
 * Call modules that implement hook_file_download() to find out if a file is
 * accessible and what headers it should be transferred with. If a module
 * returns -1 drupal_access_denied() will be returned. If one or more modules
 * returned headers the download will start with the returned headers. If no
 * modules respond drupal_not_found() will be returned.
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
 * Directories and files beginning with a period are excluded; this
 * prevents hidden files and directories (such as SVN working directories)
 * from being scanned.
 *
 * @param $dir
 *   The base directory for the scan, without trailing slash.
 * @param $mask
 *   The regular expression of the files to find.
 * @param $nomask
 *   An array of files/directories to ignore.
 * @param $callback
 *   The callback function to call for each match.
 * @param $recurse
 *   When TRUE, the directory scan will recurse the entire tree
 *   starting at the provided directory.
 * @param $key
 *   The key to be used for the returned array of files. Possible
 *   values are "filename", for the path starting with $dir,
 *   "basename", for the basename of the file, and "name" for the name
 *   of the file without an extension.
 * @param $min_depth
 *   Minimum depth of directories to return files from.
 * @param $depth
 *   Current depth of recursion. This parameter is only used internally and should not be passed.
 *
 * @return
 *   An associative array (keyed on the provided key) of objects with
 *   "path", "basename", and "name" members corresponding to the
 *   matching files.
 */
function file_scan_directory($dir, $mask, $nomask = array('.', '..', 'CVS'), $callback = 0, $recurse = TRUE, $key = 'filename', $min_depth = 0, $depth = 0) {
  $key = (in_array($key, array('filename', 'basename', 'name')) ? $key : 'filename');
  $files = array();

  if (is_dir($dir) && $handle = opendir($dir)) {
    while (FALSE !== ($file = readdir($handle))) {
      if (!in_array($file, $nomask) && $file[0] != '.') {
        if (is_dir("$dir/$file") && $recurse) {
          // Give priority to files in this folder by merging them in after any subdirectory files.
          $files = array_merge(file_scan_directory("$dir/$file", $mask, $nomask, $callback, $recurse, $key, $min_depth, $depth + 1), $files);
        }
        elseif ($depth >= $min_depth && ereg($mask, $file)) {
          // Always use this match over anything already set in $files with the same $$key.
          $filename = "$dir/$file";
          $basename = basename($file);
          $name = substr($basename, 0, strrpos($basename, '.'));
          $files[$$key] = new stdClass();
          $files[$$key]->filename = $filename;
          $files[$$key]->basename = $basename;
          $files[$$key]->name = $name;
          if ($callback) {
            $callback($filename);
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
 * @return A string containing a temp directory.
 */
function file_directory_temp() {
  $temporary_directory = variable_get('file_directory_temp', NULL);

  if (is_null($temporary_directory)) {
    $directories = array();

    // Has PHP been set with an upload_tmp_dir?
    if (ini_get('upload_tmp_dir')) {
      $directories[] = ini_get('upload_tmp_dir');
    }

    // Operating system specific dirs.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      $directories[] = 'c:\\windows\\temp';
      $directories[] = 'c:\\winnt\\temp';
      $path_delimiter = '\\';
    }
    else {
      $directories[] = '/tmp';
      $path_delimiter = '/';
    }

    foreach ($directories as $directory) {
      if (!$temporary_directory && is_dir($directory)) {
        $temporary_directory = $directory;
      }
    }

    // if a directory has been found, use it, otherwise default to 'files/tmp' or 'files\\tmp';
    $temporary_directory = $temporary_directory ? $temporary_directory : file_directory_path() . $path_delimiter .'tmp';
    variable_set('file_directory_temp', $temporary_directory);
  }

  return $temporary_directory;
}

/**
 * Determine the default 'files' directory.
 *
 * @return A string containing the path to Drupal's 'files' directory.
 */
function file_directory_path() {
  return variable_get('file_directory_path', conf_path() .'/files');
}

/**
 * Determine the maximum file upload size by querying the PHP settings.
 *
 * @return
 *   A file size limit in bytes based on the PHP upload_max_filesize and post_max_size
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
 *   An optional array of extension to media type mappings in the form
 *   'extension1|extension2|...' => 'type'.
 *
 * @return
 *   The internet media type registered for the extension or application/octet-stream for unknown extensions.
 */
function file_get_mimetype($filename, $mapping = NULL) {
  if (!is_array($mapping)) {
    $mapping = variable_get('mime_extension_mapping', array(
      'ez' => 'application/andrew-inset',
      'atom' => 'application/atom',
      'atomcat' => 'application/atomcat+xml',
      'atomsrv' => 'application/atomserv+xml',
      'cap|pcap' => 'application/cap',
      'cu' => 'application/cu-seeme',
      'tsp' => 'application/dsptype',
      'spl' => 'application/x-futuresplash',
      'hta' => 'application/hta',
      'jar' => 'application/java-archive',
      'ser' => 'application/java-serialized-object',
      'class' => 'application/java-vm',
      'hqx' => 'application/mac-binhex40',
      'cpt' => 'image/x-corelphotopaint',
      'nb' => 'application/mathematica',
      'mdb' => 'application/msaccess',
      'doc|dot' => 'application/msword',
      'bin' => 'application/octet-stream',
      'oda' => 'application/oda',
      'ogg|ogx' => 'application/ogg',
      'pdf' => 'application/pdf',
      'key' => 'application/pgp-keys',
      'pgp' => 'application/pgp-signature',
      'prf' => 'application/pics-rules',
      'ps|ai|eps' => 'application/postscript',
      'rar' => 'application/rar',
      'rdf' => 'application/rdf+xml',
      'rss' => 'application/rss+xml',
      'rtf' => 'application/rtf',
      'smi|smil' => 'application/smil',
      'wpd' => 'application/wordperfect',
      'wp5' => 'application/wordperfect5.1',
      'xhtml|xht' => 'application/xhtml+xml',
      'xml|xsl' => 'application/xml',
      'zip' => 'application/zip',
      'cdy' => 'application/vnd.cinderella',
      'kml' => 'application/vnd.google-earth.kml+xml',
      'kmz' => 'application/vnd.google-earth.kmz',
      'xul' => 'application/vnd.mozilla.xul+xml',
      'xls|xlb|xlt' => 'application/vnd.ms-excel',
      'cat' => 'application/vnd.ms-pki.seccat',
      'stl' => 'application/vnd.ms-pki.stl',
      'ppt|pps' => 'application/vnd.ms-powerpoint',
      'odc' => 'application/vnd.oasis.opendocument.chart',
      'odb' => 'application/vnd.oasis.opendocument.database',
      'odf' => 'application/vnd.oasis.opendocument.formula',
      'odg' => 'application/vnd.oasis.opendocument.graphics',
      'otg' => 'application/vnd.oasis.opendocument.graphics-template',
      'odi' => 'application/vnd.oasis.opendocument.image',
      'odp' => 'application/vnd.oasis.opendocument.presentation',
      'otp' => 'application/vnd.oasis.opendocument.presentation-template',
      'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
      'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
      'odt' => 'application/vnd.oasis.opendocument.text',
      'odm' => 'application/vnd.oasis.opendocument.text-master',
      'ott' => 'application/vnd.oasis.opendocument.text-template',
      'oth' => 'application/vnd.oasis.opendocument.text-web',
      'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
      'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
      'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
      'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
      'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
      'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
      'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
      'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
      'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
      'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
      'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
      'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
      'cod' => 'application/vnd.rim.cod',
      'mmf' => 'application/vnd.smaf',
      'sdc' => 'application/vnd.stardivision.calc',
      'sds' => 'application/vnd.stardivision.chart',
      'sda' => 'application/vnd.stardivision.draw',
      'sdd' => 'application/vnd.stardivision.impress',
      'sdf' => 'application/vnd.stardivision.math',
      'sdw' => 'application/vnd.stardivision.writer',
      'sgl' => 'application/vnd.stardivision.writer-global',
      'sxc' => 'application/vnd.sun.xml.calc',
      'stc' => 'application/vnd.sun.xml.calc.template',
      'sxd' => 'application/vnd.sun.xml.draw',
      'std' => 'application/vnd.sun.xml.draw.template',
      'sxi' => 'application/vnd.sun.xml.impress',
      'sti' => 'application/vnd.sun.xml.impress.template',
      'sxm' => 'application/vnd.sun.xml.math',
      'sxw' => 'application/vnd.sun.xml.writer',
      'sxg' => 'application/vnd.sun.xml.writer.global',
      'stw' => 'application/vnd.sun.xml.writer.template',
      'sis' => 'application/vnd.symbian.install',
      'vsd' => 'application/vnd.visio',
      'wbxml' => 'application/vnd.wap.wbxml',
      'wmlc' => 'application/vnd.wap.wmlc',
      'wmlsc' => 'application/vnd.wap.wmlscriptc',
      'wk' => 'application/x-123',
      '7z' => 'application/x-7z-compressed',
      'abw' => 'application/x-abiword',
      'dmg' => 'application/x-apple-diskimage',
      'bcpio' => 'application/x-bcpio',
      'torrent' => 'application/x-bittorrent',
      'cab' => 'application/x-cab',
      'cbr' => 'application/x-cbr',
      'cbz' => 'application/x-cbz',
      'cdf' => 'application/x-cdf',
      'vcd' => 'application/x-cdlink',
      'pgn' => 'application/x-chess-pgn',
      'cpio' => 'application/x-cpio',
      'csh' => 'text/x-csh',
      'deb|udeb' => 'application/x-debian-package',
      'dcr|dir|dxr' => 'application/x-director',
      'dms' => 'application/x-dms',
      'wad' => 'application/x-doom',
      'dvi' => 'application/x-dvi',
      'rhtml' => 'application/x-httpd-eruby',
      'flac' => 'application/x-flac',
      'pfa|pfb|gsf|pcf|pcf.Z' => 'application/x-font',
      'mm' => 'application/x-freemind',
      'gnumeric' => 'application/x-gnumeric',
      'sgf' => 'application/x-go-sgf',
      'gcf' => 'application/x-graphing-calculator',
      'gtar|tgz|taz' => 'application/x-gtar',
      'hdf' => 'application/x-hdf',
      'phtml|pht|php' => 'application/x-httpd-php',
      'phps' => 'application/x-httpd-php-source',
      'php3' => 'application/x-httpd-php3',
      'php3p' => 'application/x-httpd-php3-preprocessed',
      'php4' => 'application/x-httpd-php4',
      'ica' => 'application/x-ica',
      'ins|isp' => 'application/x-internet-signup',
      'iii' => 'application/x-iphone',
      'iso' => 'application/x-iso9660-image',
      'jnlp' => 'application/x-java-jnlp-file',
      'js' => 'application/x-javascript',
      'jmz' => 'application/x-jmol',
      'chrt' => 'application/x-kchart',
      'kil' => 'application/x-killustrator',
      'skp|skd|skt|skm' => 'application/x-koan',
      'kpr|kpt' => 'application/x-kpresenter',
      'ksp' => 'application/x-kspread',
      'kwd|kwt' => 'application/x-kword',
      'latex' => 'application/x-latex',
      'lha' => 'application/x-lha',
      'lyx' => 'application/x-lyx',
      'lzh' => 'application/x-lzh',
      'lzx' => 'application/x-lzx',
      'frm|maker|frame|fm|fb|book|fbdoc' => 'application/x-maker',
      'mif' => 'application/x-mif',
      'wmd' => 'application/x-ms-wmd',
      'wmz' => 'application/x-ms-wmz',
      'com|exe|bat|dll' => 'application/x-msdos-program',
      'msi' => 'application/x-msi',
      'nc' => 'application/x-netcdf',
      'pac' => 'application/x-ns-proxy-autoconfig',
      'nwc' => 'application/x-nwc',
      'o' => 'application/x-object',
      'oza' => 'application/x-oz-application',
      'p7r' => 'application/x-pkcs7-certreqresp',
      'crl' => 'application/x-pkcs7-crl',
      'pyc|pyo' => 'application/x-python-code',
      'qtl' => 'application/x-quicktimeplayer',
      'rpm' => 'application/x-redhat-package-manager',
      'sh' => 'text/x-sh',
      'shar' => 'application/x-shar',
      'swf|swfl' => 'application/x-shockwave-flash',
      'sit|sitx' => 'application/x-stuffit',
      'sv4cpio' => 'application/x-sv4cpio',
      'sv4crc' => 'application/x-sv4crc',
      'tar' => 'application/x-tar',
      'tcl' => 'application/x-tcl',
      'gf' => 'application/x-tex-gf',
      'pk' => 'application/x-tex-pk',
      'texinfo|texi' => 'application/x-texinfo',
      '~|%|bak|old|sik' => 'application/x-trash',
      't|tr|roff' => 'application/x-troff',
      'man' => 'application/x-troff-man',
      'me' => 'application/x-troff-me',
      'ms' => 'application/x-troff-ms',
      'ustar' => 'application/x-ustar',
      'src' => 'application/x-wais-source',
      'wz' => 'application/x-wingz',
      'crt' => 'application/x-x509-ca-cert',
      'xcf' => 'application/x-xcf',
      'fig' => 'application/x-xfig',
      'xpi' => 'application/x-xpinstall',
      'au|snd' => 'audio/basic',
      'mid|midi|kar' => 'audio/midi',
      'mpga|mpega|mp2|mp3|m4a' => 'audio/mpeg',
      'f4a|f4b' => 'audio/mp4',
      'm3u' => 'audio/x-mpegurl',
      'oga|spx' => 'audio/ogg',
      'sid' => 'audio/prs.sid',
      'aif|aiff|aifc' => 'audio/x-aiff',
      'gsm' => 'audio/x-gsm',
      'wma' => 'audio/x-ms-wma',
      'wax' => 'audio/x-ms-wax',
      'ra|rm|ram' => 'audio/x-pn-realaudio',
      'ra' => 'audio/x-realaudio',
      'pls' => 'audio/x-scpls',
      'sd2' => 'audio/x-sd2',
      'wav' => 'audio/x-wav',
      'alc' => 'chemical/x-alchemy',
      'cac|cache' => 'chemical/x-cache',
      'csf' => 'chemical/x-cache-csf',
      'cbin|cascii|ctab' => 'chemical/x-cactvs-binary',
      'cdx' => 'chemical/x-cdx',
      'cer' => 'chemical/x-cerius',
      'c3d' => 'chemical/x-chem3d',
      'chm' => 'chemical/x-chemdraw',
      'cif' => 'chemical/x-cif',
      'cmdf' => 'chemical/x-cmdf',
      'cml' => 'chemical/x-cml',
      'cpa' => 'chemical/x-compass',
      'bsd' => 'chemical/x-crossfire',
      'csml|csm' => 'chemical/x-csml',
      'ctx' => 'chemical/x-ctx',
      'cxf|cef' => 'chemical/x-cxf',
      'emb|embl' => 'chemical/x-embl-dl-nucleotide',
      'spc' => 'chemical/x-galactic-spc',
      'inp|gam|gamin' => 'chemical/x-gamess-input',
      'fch|fchk' => 'chemical/x-gaussian-checkpoint',
      'cub' => 'chemical/x-gaussian-cube',
      'gau|gjc|gjf' => 'chemical/x-gaussian-input',
      'gal' => 'chemical/x-gaussian-log',
      'gcg' => 'chemical/x-gcg8-sequence',
      'gen' => 'chemical/x-genbank',
      'hin' => 'chemical/x-hin',
      'istr|ist' => 'chemical/x-isostar',
      'jdx|dx' => 'chemical/x-jcamp-dx',
      'kin' => 'chemical/x-kinemage',
      'mcm' => 'chemical/x-macmolecule',
      'mmd|mmod' => 'chemical/x-macromodel-input',
      'mol' => 'chemical/x-mdl-molfile',
      'rd' => 'chemical/x-mdl-rdfile',
      'rxn' => 'chemical/x-mdl-rxnfile',
      'sd|sdf' => 'chemical/x-mdl-sdfile',
      'tgf' => 'chemical/x-mdl-tgf',
      'mcif' => 'chemical/x-mmcif',
      'mol2' => 'chemical/x-mol2',
      'b' => 'chemical/x-molconn-Z',
      'gpt' => 'chemical/x-mopac-graph',
      'mop|mopcrt|mpc|dat|zmt' => 'chemical/x-mopac-input',
      'moo' => 'chemical/x-mopac-out',
      'mvb' => 'chemical/x-mopac-vib',
      'asn' => 'chemical/x-ncbi-asn1-spec',
      'prt|ent' => 'chemical/x-ncbi-asn1-ascii',
      'val|aso' => 'chemical/x-ncbi-asn1-binary',
      'pdb|ent' => 'chemical/x-pdb',
      'ros' => 'chemical/x-rosdal',
      'sw' => 'chemical/x-swissprot',
      'vms' => 'chemical/x-vamas-iso14976',
      'vmd' => 'chemical/x-vmd',
      'xtel' => 'chemical/x-xtel',
      'xyz' => 'chemical/x-xyz',
      'gif' => 'image/gif',
      'ief' => 'image/ief',
      'jpeg|jpg|jpe' => 'image/jpeg',
      'pcx' => 'image/pcx',
      'png' => 'image/png',
      'svg|svgz' => 'image/svg+xml',
      'tiff|tif' => 'image/tiff',
      'djvu|djv' => 'image/vnd.djvu',
      'wbmp' => 'image/vnd.wap.wbmp',
      'ras' => 'image/x-cmu-raster',
      'cdr' => 'image/x-coreldraw',
      'pat' => 'image/x-coreldrawpattern',
      'cdt' => 'image/x-coreldrawtemplate',
      'ico' => 'image/x-icon',
      'art' => 'image/x-jg',
      'jng' => 'image/x-jng',
      'bmp' => 'image/x-ms-bmp',
      'psd' => 'image/x-photoshop',
      'pnm' => 'image/x-portable-anymap',
      'pbm' => 'image/x-portable-bitmap',
      'pgm' => 'image/x-portable-graymap',
      'ppm' => 'image/x-portable-pixmap',
      'rgb' => 'image/x-rgb',
      'xbm' => 'image/x-xbitmap',
      'xpm' => 'image/x-xpixmap',
      'xwd' => 'image/x-xwindowdump',
      'eml' => 'message/rfc822',
      'igs|iges' => 'model/iges',
      'msh|mesh|silo' => 'model/mesh',
      'wrl|vrml' => 'model/vrml',
      'ics|icz' => 'text/calendar',
      'css' => 'text/css',
      'csv' => 'text/csv',
      '323' => 'text/h323',
      'html|htm|shtml' => 'text/html',
      'uls' => 'text/iuls',
      'mml' => 'text/mathml',
      'asc|txt|text|pot' => 'text/plain',
      'rtx' => 'text/richtext',
      'sct|wsc' => 'text/scriptlet',
      'tm|ts' => 'text/texmacs',
      'tsv' => 'text/tab-separated-values',
      'jad' => 'text/vnd.sun.j2me.app-descriptor',
      'wml' => 'text/vnd.wap.wml',
      'wmls' => 'text/vnd.wap.wmlscript',
      'bib' => 'text/x-bibtex',
      'boo' => 'text/x-boo',
      'h++|hpp|hxx|hh' => 'text/x-c++hdr',
      'c++|cpp|cxx|cc' => 'text/x-c++src',
      'h' => 'text/x-chdr',
      'htc' => 'text/x-component',
      'c' => 'text/x-csrc',
      'd' => 'text/x-dsrc',
      'diff|patch' => 'text/x-diff',
      'hs' => 'text/x-haskell',
      'java' => 'text/x-java',
      'lhs' => 'text/x-literate-haskell',
      'moc' => 'text/x-moc',
      'p|pas' => 'text/x-pascal',
      'gcd' => 'text/x-pcs-gcd',
      'pl|pm' => 'text/x-perl',
      'py' => 'text/x-python',
      'etx' => 'text/x-setext',
      'tcl|tk' => 'text/x-tcl',
      'tex|ltx|sty|cls' => 'text/x-tex',
      'vcs' => 'text/x-vcalendar',
      'vcf' => 'text/x-vcard',
      '3gp' => 'video/3gpp',
      'dl' => 'video/dl',
      'dif|dv' => 'video/dv',
      'fli' => 'video/fli',
      'gl' => 'video/gl',
      'mpeg|mpg|mpe' => 'video/mpeg',
      'mp4|f4v|f4p' => 'video/mp4',
      'flv' => 'video/x-flv',
      'ogv' => 'video/ogg',
      'qt|mov' => 'video/quicktime',
      'mxu' => 'video/vnd.mpegurl',
      'lsf|lsx' => 'video/x-la-asf',
      'mng' => 'video/x-mng',
      'asf|asx' => 'video/x-ms-asf',
      'wm' => 'video/x-ms-wm',
      'wmv' => 'video/x-ms-wmv',
      'wmx' => 'video/x-ms-wmx',
      'wvx' => 'video/x-ms-wvx',
      'avi' => 'video/x-msvideo',
      'movie' => 'video/x-sgi-movie',
      'ice' => 'x-conference/x-cooltalk',
      'sisx' => 'x-epoc/x-sisx-app',
      'vrm|vrml|wrl' => 'x-world/x-vrml',
      'xps' => 'application/vnd.ms-xpsdocument',
    ));
  }
  foreach ($mapping as $ext_preg => $mime_match) {
    if (preg_match('!\.('. $ext_preg .')$!i', $filename)) {
      return $mime_match;
    }
  }

  return 'application/octet-stream';
}

/**
 * @} End of "defgroup file".
 */
