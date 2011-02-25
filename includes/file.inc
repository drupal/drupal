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
 * Create the download path to a file.
 *
 * @param $path A string containing the path of the file to generate URL for.
 * @return A string containing a URL that can be used to download the file.
 */
function file_create_url($path) {
  // Strip file_directory_path from $path. We only include relative paths in urls.
  if (strpos($path, file_directory_path() . '/') === 0) {
    $path = trim(substr($path, strlen(file_directory_path())), '\\/');
  }
  switch (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC)) {
    case FILE_DOWNLOADS_PUBLIC:
      return $GLOBALS['base_url'] .'/'. file_directory_path() .'/'. str_replace('\\', '/', $path);
    case FILE_DOWNLOADS_PRIVATE:
      return url('system/files/'. $path, NULL, NULL, TRUE);
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
  else if (file_check_location($file_path . '/' . $dest, $file_path)) {
    return $file_path . '/' . $dest;
  }
  // File not found.
  return FALSE;
}

/**
 * Check that the directory exists and is writable. Directories need to
 * have execute permissions to be considered a directory by FTP servers, etc.
 *
 * @param $directory A string containing the name of a directory path.
 * @param $mode A Boolean value to indicate if the directory should be created
 *   if it does not exist or made writable if it is read-only.
 * @param $form_item An optional string containing the name of a form item that
 *   any errors will be attached to. This is useful for settings forms that
 *   require the user to specify a writable directory. If it can't be made to
 *   work, a form error will be set preventing them from saving the settings.
 * @return False when directory not found, or true when directory exists.
 */
function file_check_directory(&$directory, $mode = 0, $form_item = NULL) {
  $directory = rtrim($directory, '/\\');

  // Check if directory exists.
  if (!is_dir($directory)) {
    if (($mode & FILE_CREATE_DIRECTORY) && @mkdir($directory)) {
      drupal_set_message(t('The directory %directory has been created.', array('%directory' => theme('placeholder', $directory))));
      @chmod($directory, 0775); // Necessary for non-webserver users.
    }
    else {
      if ($form_item) {
        form_set_error($form_item, t('The directory %directory does not exist.', array('%directory' => theme('placeholder', $directory))));
      }
      return false;
    }
  }

  // Check to see if the directory is writable.
  if (!is_writable($directory)) {
    if (($mode & FILE_MODIFY_PERMISSIONS) && @chmod($directory, 0775)) {
      drupal_set_message(t('The permissions of directory %directory have been changed to make it writable.', array('%directory' => theme('placeholder', $directory))));
    }
    else {
      form_set_error($form_item, t('The directory %directory is not writable', array('%directory' => theme('placeholder', $directory))));
      watchdog('file system', t('The directory %directory is not writable, because it does not have the correct permissions set.', array('%directory' => theme('placeholder', $directory))), WATCHDOG_ERROR);
      return false;
    }
  }

  if ((file_directory_path() == $directory || file_directory_temp() == $directory) && !is_file("$directory/.htaccess")) {
    $htaccess_lines = "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nOptions None\nOptions +FollowSymLinks";
    if (($fp = fopen("$directory/.htaccess", 'w')) && fputs($fp, $htaccess_lines)) {
      fclose($fp);
      chmod($directory .'/.htaccess', 0664);
    }
    else {
      $message = t("Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <code>%htaccess</code>", array('%directory' => theme('placeholder', $directory), '%htaccess' => '<br />'. str_replace("\n", '<br />', check_plain($htaccess_lines))));
      form_set_error($form_item, $message);
      watchdog('security', $message, WATCHDOG_ERROR);
    }
  }

  return true;
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

  return false;
}


/**
 * Check if $source is a valid file upload. If so, move the file to Drupal's tmp dir
 * and return it as an object.
 *
 * The use of SESSION['file_uploads'] should probably be externalized to upload.module
 *
 * @todo Rename file_check_upload to file_prepare upload.
 * @todo Refactor or merge file_save_upload.
 * @todo Extenalize SESSION['file_uploads'] to modules.
 *
 * @param $source An upload source (the name of the upload form item), or a file
 * @return false for an invalid file or upload. A file object for valid uploads/files.
 *
 */

function file_check_upload($source = 'upload') {
  // Cache for uploaded files. Since the data in _FILES is modified
  // by this function, we cache the result.
  static $upload_cache;

  // Test source to see if it is an object.
  if (is_object($source)) {

    // Validate the file path if an object was passed in instead of
    // an upload key.
    if (is_file($source->filepath)) {
      return $source;
    }
    else {
      return false;
    }
  }

  // Return cached objects without processing since the file will have
  // already been processed and the paths in _FILES will be invalid.
  if (isset($upload_cache[$source])) {
    return $upload_cache[$source];
  }

  // If a file was uploaded, process it.
  if ($_FILES["edit"]["name"][$source] && is_uploaded_file($_FILES["edit"]["tmp_name"][$source])) {

    // Check for file upload errors and return false if a
    // lower level system error occurred.
    switch ($_FILES["edit"]["error"][$source]) {

      // @see http://php.net/manual/en/features.file-upload.errors.php
      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        drupal_set_message(t('The file %file could not be saved, because it exceeds the maximum allowed size for uploads.', array('%file' => theme('placeholder', $source))), 'error');
        return 0;

      case UPLOAD_ERR_PARTIAL:
      case UPLOAD_ERR_NO_FILE:
        drupal_set_message(t('The file %file could not be saved, because the upload did not complete.', array('%file' => theme('placeholder', $source))), 'error');
        return 0;

      // Unknown error
      default:
        drupal_set_message(t('The file %file could not be saved. An unknown error has occurred.', array('%file' => theme('placeholder', $source))),'error');
        return 0;
    }

    // Begin building file object.
    $file = new StdClass();
    $file->filename = trim(basename($_FILES["edit"]["name"][$source]), '.');

    // Create temporary name/path for newly uploaded files.
    $file->filepath = tempnam(file_directory_temp(), 'tmp_');

    $file->filemime = $_FILES["edit"]["type"][$source];

    // Rename potentially executable files, to help prevent exploits.
    if (((substr($file->filemime, 0, 5) == 'text/' || strpos($file->filemime, 'javascript')) && (substr($file->filename, -4) != '.txt')) || preg_match('/\.(php|pl|py|cgi|asp)$/i', $file->filename)) {
      $file->filemime = 'text/plain';
      $file->filepath .= '.txt';
      $file->filename .= '.txt';
    }

    // Move uploaded files from php's upload_tmp_dir to Drupal's file temp.
    // This overcomes open_basedir restrictions for future file operations.
    if (!move_uploaded_file($_FILES["edit"]["tmp_name"][$source], $file->filepath)) {
      drupal_set_message(t('File upload error. Could not move uploaded file.'));
      watchdog('file', t('Upload Error. Could not move uploaded file(%file) to destination(%destination).', array('%file' => theme('placeholder', $_FILES["edit"]["tmp_name"][$source]), '%destination' => theme('placeholder', $file->filepath))));
      return false;
    }

    $file->filesize = $_FILES["edit"]["size"][$source];
    $file->source = $source;

    // Add processed file to the cache.
    $upload_cache[$source] = $file;
    return $file;
  }

  else {
    // In case of previews return previous file object.
    if (file_exists($_SESSION['file_uploads'][$source]->filepath)) {
      return $_SESSION['file_uploads'][$source];
    }
  }
  // If nothing was done, return false.
  return false;
}

/**
 * Check if a file is really located inside $directory. Should be used to make
 * sure a file specified is really located within the directory to prevent
 * exploits.
 *
 * @code
 *   // Returns false:
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
 * Copies a file to a new location. This is a powerful function that in many ways
 * performs like an advanced version of copy().
 * - Checks if $source and $dest are valid and readable/writable.
 * - Performs a file copy if $source is not equal to $dest.
 * - If file already exists in $dest either the call will error out, replace the
 *   file or rename the file based on the $replace parameter.
 *
 * @param $source A string specifying the file location of the original file.
 *   This parameter will contain the resulting destination filename in case of
 *   success.
 * @param $dest A string containing the directory $source should be copied to.
 *   If this value is omitted, Drupal's 'files' directory will be used.
 * @param $replace Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is unique
 *   - FILE_EXISTS_ERROR - Do nothing and return false.
 * @return True for success, false for failure.
 */
function file_copy(&$source, $dest = 0, $replace = FILE_EXISTS_RENAME) {
  $dest = file_create_path($dest);

  $directory = $dest;
  $basename = file_check_path($directory);

  // Make sure we at least have a valid directory.
  if ($basename === false) {
    $source = is_object($source) ? $source->filepath : $source;
    drupal_set_message(t('The selected file %file could not be uploaded, because the destination %directory is not properly configured.', array('%file' => theme('placeholder', $source), '%directory' => theme('placeholder', $dest))), 'error');
    watchdog('file system', t('The selected file %file could not be uploaded, because the destination %directory could not be found, or because its permissions do not allow the file to be written.', array('%file' => theme('placeholder', $source), '%directory' => theme('placeholder', $dest))), WATCHDOG_ERROR);
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
    drupal_set_message(t('The selected file %file could not be copied, because no file by that name exists.  Please check that you supplied the correct filename.', array('%file' => theme('placeholder', $source))), 'error');
    return 0;
  }

  // If the destination file is not specified then use the filename of the source file.
  $basename = $basename ? $basename : basename($source);
  $dest = $directory .'/'. $basename;

  // Make sure source and destination filenames are not the same, makes no sense
  // to copy it if they are. In fact copying the file will most likely result in
  // a 0 byte file. Which is bad. Real bad.
  if ($source != realpath($dest)) {
    if (file_exists($dest)) {
      switch ($replace) {
        case FILE_EXISTS_RENAME:
          // Destination file already exists and we can't replace is so we try and
          // and find a new filename.
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
          break;

        case FILE_EXISTS_ERROR:
          drupal_set_message(t('The selected file %file could not be copied, because a file by that name already exists in the destination.', array('%file' => theme('placeholder', $source))), 'error');
          return 0;
      }
    }

    if (!@copy($source, $dest)) {
      drupal_set_message(t('The selected file %file could not be copied.', array('%file' => theme('placeholder', $source))), 'error');
      return 0;
    }

    // Give everyone read access so that FTP'd users or
    // non-webserver users can see/read these files.
    @chmod($dest, 0664);
  }

  if (is_object($file)) {
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
 * Moves a file to a new location.
 * - Checks if $source and $dest are valid and readable/writable.
 * - Performs a file move if $source is not equal to $dest.
 * - If file already exists in $dest either the call will error out, replace the
 *   file or rename the file based on the $replace parameter.
 *
 * @param $source A string specifying the file location of the original file.
 *   This parameter will contain the resulting destination filename in case of
 *   success.
 * @param $dest A string containing the directory $source should be copied to.
 *   If this value is omitted, Drupal's 'files' directory will be used.
 * @param $replace Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is unique
 *   - FILE_EXISTS_ERROR - Do nothing and return false.
 * @return True for success, false for failure.
 */
function file_move(&$source, $dest = 0, $replace = FILE_EXISTS_RENAME) {

  $path_original = is_object($source) ? $source->filepath : $source;

  if (file_copy($source, $dest, $replace)) {
    $path_current = is_object($source) ? $source->filepath : $source;

    if ($path_original == $path_current || file_delete($path_original)) {
      return 1;
    }
    drupal_set_message(t('The removal of the original file %file has failed.', array('%file' => theme('placeholder', $path_original))), 'error');
  }
  return 0;
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
 * @return True for success, false for failure.
 */
function file_delete($path) {
  if (is_file($path)) {
    return unlink($path);
  }
}

/**
 * Saves a file upload to a new location. The source file is validated as a
 * proper upload and handled as such.
 *
 * @param $source A string specifying the name of the upload field to save.
 *   This parameter will contain the resulting destination filename in case of
 *   success.
 * @param $dest A string containing the directory $source should be copied to,
 *   will use the temporary directory in case no other value is set.
 * @param $replace A boolean, set to true if the destination should be replaced
 *   when in use, but when false append a _X to the filename.
 * @return An object containing file info or 0 in case of error.
 */
function file_save_upload($source, $dest = false, $replace = FILE_EXISTS_RENAME) {
  // Make sure $source exists && is valid.
  if ($file = file_check_upload($source)) {

    // This should be refactored, file_check_upload has already
    // moved the file to the temporary folder.
    if (!$dest) {
      $dest = file_directory_temp();
      $temporary = 1;
      if (is_file($file->filepath)) {
        // If this file was uploaded by this user before replace the temporary copy.
        $replace = FILE_EXISTS_REPLACE;
      }
    }

    unset($_SESSION['file_uploads'][is_object($source) ? $source->source : $source]);
    if (file_move($file, $dest, $replace)) {
      if ($temporary) {
        $_SESSION['file_uploads'][is_object($source) ? $source->source : $source] = $file;
      }
      return $file;
    }
    return 0;
  }
  return 0;
}

/**
 * Save a string to the specified destination.
 *
 * @param $data A string containing the contents of the file.
 * @param $dest A string containing the destination location.
 * @param $replace Replace behavior when the destination file already exists.
 *   - FILE_EXISTS_REPLACE - Replace the existing file
 *   - FILE_EXISTS_RENAME - Append _{incrementing number} until the filename is unique
 *   - FILE_EXISTS_ERROR - Do nothing and return false.
 *
 * @return A string containing the resulting filename or 0 on error
 */
function file_save_data($data, $dest, $replace = FILE_EXISTS_RENAME) {
  $temp = file_directory_temp();
  $file = tempnam($temp, 'file');
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
 * Transfer file using http to client. Pipes a file through Drupal to the
 * client.
 *
 * @param $source File to transfer.
 * @param $headers An array of http headers to send along with file.
 */
function file_transfer($source, $headers) {
  ob_end_clean();

  foreach ($headers as $header) {
    // To prevent HTTP header injection, we delete new lines that are
    // not followed by a space or a tab.
    // See http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
    $header = preg_replace('/\r?\n(?!\t| )/', '', $header);
    header($header);
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
 * Finds all files that match a given mask in a given
 * directory. Directories and files beginning with a period are excluded.
 *
 * @param $dir
 *   The base directory for the scan.
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
 *   The key to be used for the returned array of files.  Possible
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
    while ($file = readdir($handle)) {
      if (!in_array($file, $nomask) && $file[0] != '.') {
        if (is_dir("$dir/$file") && $recurse) {
          $files = array_merge($files, file_scan_directory("$dir/$file", $mask, $nomask, $callback, $recurse, $key, $min_depth, $depth + 1));
        }
        elseif ($depth >= $min_depth && ereg($mask, $file)) {
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
    $temporary_directory = $temporary_directory ? $temporary_directory : file_directory_path() . $path_delimiter . 'tmp';
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
  return variable_get('file_directory_path', 'files');
}

/**
 * Helper function for file_upload_max_size().
 */
function _file_convert_to_mb($val){
  $val = trim($val);
  $last = strtolower($val[strlen($val) - 1]);
  switch($last) {
    // The 'G' modifier is available since PHP 5.1.0
    case 'g':
      $size = $val * 1024;
      break;
    case 'k':
      $size = $val / 1024;
      break;
    default:
      $size = (int) $val;
  }
  return $size;
}

/**
 * Determine the maximum file upload size by querying the PHP settings.
 *
 * @return
 * A file size limit in MB based on the PHP upload_max_filesize and post_max_size
 */
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    $upload_max = _file_convert_to_mb(ini_get('upload_max_filesize'));
    // sanity check- a single upload should not be more than 50% the size limit of the total post
    $post_max = _file_convert_to_mb(ini_get('post_max_size')) / 2;
    $max_size = ($upload_max < $post_max) ? $upload_max : $post_max;
  }
  return $max_size;
}
