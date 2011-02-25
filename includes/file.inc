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

define('IS_WINDOWS', substr(PHP_OS, 0, 3) == 'WIN');
define('FILE_DOWNLOADS_PUBLIC', 1);
define('FILE_DOWNLOADS_PRIVATE', 2);
define('FILE_CREATE_DIRECTORY', 1);
define('FILE_MODIFY_PERMISSIONS', 2);
define('FILE_DIRECTORY_TEMP', IS_WINDOWS ? 'c:\\windows\\temp' : '/tmp');
define('FILE_EXISTS_RENAME', 0);
define('FILE_EXISTS_REPLACE', 1);
define('FILE_EXISTS_ERROR', 2);

/**
 * Create the download path to a file.
 *
 * @param $path Path to the file to generate URL for
 * @return URL pointing to the file
 */
function file_create_url($path) {
  if (strpos($path, variable_get('file_directory_path', 'files')) !== false) {
    $path = trim(substr($path, strlen(variable_get('file_directory_path', 'files'))), '\\/');
  }
  switch (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC)) {
    case FILE_DOWNLOADS_PUBLIC:
      return $GLOBALS['base_url'] .'/'. variable_get('file_directory_path', 'files') .'/'. str_replace('\\', '/', $path);
    case FILE_DOWNLOADS_PRIVATE:
      return url('system/files', 'file='. $path, NULL, TRUE);
  }
}

/**
 * Make sure the destination is a complete path and resides in the
 * file system directory, if it is not prepend the
 * file system directory.
 *
 * @param $dest Path to verify
 * @return Path to file with file system directory appended if necessary.
 *         Returns FALSE if the path is invalid (i.e. outside the configured 'files'-directory).
 */
function file_create_path($dest = 0) {
  $file_path = variable_get('file_directory_path', 'files');
  if (!$dest) {
    return $file_path;
  }
  // file_check_location() checks whether the destination is inside the Drupal files directory.
  if (file_check_location($dest, $file_path)) {
    return $dest;
  }
  // check if the destination is instead inside the Drupal temporary files directory.
  else if (file_check_location($dest, variable_get('file_directory_temp', FILE_DIRECTORY_TEMP))) {
    return $dest;
  }
  // Not found, try again with prefixed dirctory path.
  else if (file_check_location($file_path . '/' . $dest, $file_path)) {
    return $file_path . '/' . $dest;
  }
  // File not found.
  return FALSE;
}

/**
 * Check that directory exists and is writable.
 *
 * @param $directory Path to extract and verify directory for.
 * @param $mode Try to create the directory if it does not exist.
 * @param $form_item Optional name for a field item to attach potential errors to.
 * @return False when directory not found, or true when directory exists.
 */
function file_check_directory(&$directory, $mode = 0, $form_item = NULL) {
  $directory = rtrim($directory, '/\\');

  // Check if directory exists.
  if (!is_dir($directory)) {
    if (($mode & FILE_CREATE_DIRECTORY) && @mkdir($directory, 0760)) {
      drupal_set_message(t('Created directory %directory.', array('%directory' => theme('placeholder', $directory))));
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
    if (($mode & FILE_MODIFY_PERMISSIONS) && @chmod($directory, 0760)) {
      drupal_set_message(t('Modified permissions on directory %directory.', array('%directory' => theme('placeholder', $directory))));
    }
    else {
      form_set_error($form_item, t('The directory %directory is not writable.', array('%directory' => theme('placeholder', $directory))));
      return false;
    }
  }

  if ((variable_get('file_directory_temp', FILE_DIRECTORY_TEMP) == $directory || variable_get('file_directory_path', 'files') == $directory) && !is_file("$directory/.htaccess")) {
    $htaccess_lines = "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nOptions None\n<IfModule mod_rewrite.c>\n  RewriteEngine off\n</IfModule>";
    if (($fp = fopen("$directory/.htaccess", 'w')) && fputs($fp, $htaccess_lines)) {
      fclose($fp);
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
 * @param $path
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
 * Check if $source is a valid file upload.
 *
 * @param $source
 */
function file_check_upload($source) {
  if (is_object($source)) {
    if (is_file($source->filepath)) {
      return $source;
    }
  }
  elseif ($_FILES["edit"]["name"][$source] && is_uploaded_file($_FILES["edit"]["tmp_name"][$source])) {
    $file = new StdClass();
    $file->filename = trim(basename($_FILES["edit"]["name"][$source]), '.');
    $file->filepath = $_FILES["edit"]["tmp_name"][$source];
    $file->filemime = $_FILES["edit"]["type"][$source];

    if (((substr($file->filemime, 0, 5) == 'text/' || strpos($file->filemime, 'javascript')) && (substr($file->filename, -4) != '.txt')) || preg_match('/\.(php|pl|py|cgi|asp)$/i', $file->filename)) {
      $file->filemime = 'text/plain';
      rename($file->filepath, $file->filepath .'.txt');
      $file->filepath .= '.txt';
      $file->filename .= '.txt';
    }

    $file->error = $_FILES["edit"]["error"][$source];
    $file->filesize = $_FILES["edit"]["size"][$source];
    $file->source = $source;
    return $file;
  }
  else {
    // In case of previews return previous file object.
    if (file_exists($_SESSION['file_uploads'][$source]->filepath)) {
      return $_SESSION['file_uploads'][$source];
    }
  }
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
function file_check_location($source, $directory = 0) {
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
    drupal_set_message(t('File copy failed: no directory configured, or it could not be accessed.'), 'error');
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
    drupal_set_message(t('File copy failed: source file does not exist.'), 'error');
    return 0;
  }

  // If destination file is not specified then use filename of source file.
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
          drupal_set_message(t('File copy failed. File already exists.'), 'error');
          return 0;

        case FILE_EXISTS_REPLACE:
          // Leave $dest where it is for replace.
      }
    }

    if (!@copy($source, $dest)) {
      drupal_set_message(t('File copy failed.'), 'error');
      return 0;
    }
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
    drupal_set_message(t('Removing original file failed.'), 'error');
  }
  return 0;
}

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
function file_save_upload($source, $dest = 0, $replace = FILE_EXISTS_RENAME) {
  // Make sure $source exists in $_FILES.
  if ($file = file_check_upload($source)) {
    if (!$dest) {
      $dest = variable_get('file_directory_temp', FILE_DIRECTORY_TEMP);
      $temporary = 1;
      if (is_file($file->filepath)) {
        // If this file was uploaded by this user before replace the temporary copy.
        $replace = 1;
      }
    }

    // Check for file upload errors.
    switch ($file->error) {
      case 0: // UPLOAD_ERR_OK
        break;
      case 1: // UPLOAD_ERR_INI_SIZE
      case 2: // UPLOAD_ERR_FORM_SIZE
        drupal_set_message(t('File upload failed: file size too big.'), 'error');
        return 0;
      case 3: // UPLOAD_ERR_PARTIAL
      case 4: // UPLOAD_ERR_NO_FILE
        drupal_set_message(t('File upload failed: incomplete upload.'), 'error');
        return 0;
      default: // Unknown error
        drupal_set_message(t('File upload failed: unknown error.'), 'error');
        return 0;
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
 * Save a string to the specified destination
 *
 * @param $data A string containing the contents of the file
 * @param $dest A string containing the destination location
 *
 * @return A string containing the resulting filename or 0 on error
 */
function file_save_data($data, $dest, $replace = FILE_EXISTS_RENAME) {

  $temp = variable_get('file_directory_temp', FILE_DIRECTORY_TEMP);
  $file = tempnam($temp, 'file');
  if (!$fp = fopen($file, 'wb')) {
    drupal_set_message(t('Unable to create file.'), 'error');
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
 * Call modules to find out if a file is accessible for a given user.
 */
function file_download() {
  $file = $_GET['file'];
  if (file_exists(file_create_path($file))) {
    $list = module_list();
    foreach ($list as $module) {
      $headers = module_invoke($module, 'file_download', $file);
      if ($headers === -1) {
        drupal_access_denied();
      }
      elseif (is_array($headers)) {
        file_transfer($file, $headers);
      }
    }
  }
  drupal_not_found();
}

/**
 * Finds all files that match a given mask in a given
 * directory.
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
      if (!in_array($file, $nomask)) {
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

?>
