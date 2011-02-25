<?php

/**
 * @defgroup file File interface
 *   Common file handling functions.
 * @{
 */

define('FILE_DOWNLOADS_PUBLIC', 1);
define('FILE_DOWNLOADS_PRIVATE', 2);
define('FILE_SEPARATOR', PHP_OS == 'WINNT' ? '\\' : '/');

/**
 * Create the download path to a file.
 */
function file_create_url($path) {
  if (strpos($path, variable_get('file_directory_path', 'files')) !== false) {
    $path = trim(substr($path, strlen(variable_get('file_directory_path', 'files'))), '\\/');
  }
  switch (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC)) {
    case FILE_DOWNLOADS_PUBLIC:
      global $base_url;
      return $base_url .'/'. variable_get('file_directory_path', 'files') .'/'. str_replace('\\', '/', $path);
    case FILE_DOWNLOADS_PRIVATE:
      return url('system/files', 'file='. $path);
  }
}

/**
 * Make sure the destination is a complete path, if it is not prepend the
 * file system directory.
 */
function file_create_path($dest = 0) {
  if (!$dest) {
    return variable_get('file_directory_path', 'files');
  }

  $regex = (PHP_OS == 'WINNT' ? '.?:\\\\' : '/');
  if (!file_check_location($dest, variable_get('file_directory_path', 'files')) && !preg_match("|^$regex|", $dest)) {
    return variable_get('file_directory_path', 'files') . FILE_SEPARATOR . trim($dest, '\\/');
  }
  else {
    return $dest;
  }
}

/**
 * Check that directory exists and is writable.
 *
 * @param $directory Path to extract and verify directory for.
 * @return False when directory not found, or true when directory exists.
 */
function file_check_directory(&$directory) {
  $directory = rtrim($directory, '/\\');
  return is_dir($directory) && is_writable($directory);
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
    if (is_file($source->path)) {
      return $source;
    }
  }
  elseif ($_FILES["edit"]["name"][$source] && is_uploaded_file($_FILES["edit"]["tmp_name"][$source])) {
    $file->name = trim(basename($_FILES["edit"]["name"][$source]), '.');
    $file->type = $_FILES["edit"]["type"][$source];
    $file->path = $_FILES["edit"]["tmp_name"][$source];
    $file->error = $_FILES["edit"]["error"][$source];
    $file->size = $_FILES["edit"]["size"][$source];
    $file->source = $_FILES["edit"]["size"][$source];
    return $file;
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
  $source = realpath($source);
  $directory = realpath($directory);
  if ($directory && strpos($source, $directory) !== 0) {
    return 0;
  }
  return $source;
}

/**
 * Saves a file to a new location. This is a powerful function that in many ways
 * performs like an advanced version of copy().
 * - Checks if $source and $dest are valid and readable/writable.
 * - Performs a file copy if $source is not equal to $dest.
 * - If file already exists in $dest it will append a number to the end of the
 *   filename, but before the file extension. It will increment the number until
 *   it finds a filename that is not already in use.
 *
 * @param $source A string specifying the file location of the original file.
 *   This parameter will contain the resulting destination filename in case of
 *   success.
 * @param $dest A string containing the directory $source should be copied to.
 * @param $replace A boolean that when true will overwrite any existing files,
 *   but when false append a _X to the filename.
 * @return True for success, false for failure.
 */
function file_copy(&$source, $dest = 0, $replace = 0) {
  $dest = file_create_path($dest);

  $directory = $dest;
  $basename = file_check_path($directory);

  // Make sure we at least have a valid directory.
  if ($basename === false) {
    drupal_set_message(t('file copy failed: no directory configured, or it could not be accessed.'), 'error');
    return 0;
  }

  // Process a file upload object.
  if (is_object($source)) {
    $file = $source;
    $source = $file->path;
    if (!$basename) {
      $basename = $file->name;
    }
  }

  $source = realpath($source);
  if (!file_exists($source)) {
    drupal_set_message(t('file copy failed: source file does not exist.'), 'error');
    return 0;
  }

  // If destination file is not specified then use filename of source file.
  $basename = $basename ? $basename : basename($source);
  $dest = $directory . FILE_SEPARATOR . $basename;

  if (file_exists($dest) && !$replace) {
    // Destination file already exists and we can't replace is so we try and
    // and find a new filename.
    $pos = strrpos($basename, '.');
    $name = substr($basename, 0, $pos);
    if ($pos = strrpos($basename, '.')) {
      $name = substr($basename, 0, $pos);
      $ext = substr($basename, $pos);
    }
    else {
      $name = $basename;
    }

    $counter = 0;
    do {
      $dest = $directory . FILE_SEPARATOR . $name .'_'. $counter++ . $ext;
    } while (file_exists($dest));
  }

  if (!copy($source, $dest)) {
    drupal_set_message(t('file copy failed.'), 'error');
    return 0;
  }

  if (is_object($file)) {
    $file->name = $basename;
    $file->path = $dest;
    $source = $file;
  }
  else {
    $source = $dest;
  }
  return 1; // Everything went ok.
}

function file_move(&$source, $dest = 0, $replace = 0) {
  $path = is_object($source) ? $source->path : $source;
  if (file_copy($source, $dest, $replace)) {
    if (unlink($path)) {
      return 1;
    }
    drupal_set_message(t('removing original file failed.'), 'error');
  }
  return 0;
}

function file_delete($path) {
  if (is_file($path)) {
    unlink($path);
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
function file_save_upload($source, $dest = 0, $replace = 0) {
  // Make sure $source exists in $_FILES.
  if ($file = file_check_upload($source)) {
    if (!$dest) {
      $dest = variable_get('file_directory_temp', (PHP_OS == 'WINNT' ? 'c:\\windows\\temp' : '/tmp'));
      $temporary = 1;
      if (is_file($_SESSION['file_uploads'][$source]->path)) {
        // If this file was uploaded by this user before replace the temporary copy.
        $replace = 1;
      }
    }

    if (!valid_input_data($file)) {
      watchdog('error', t('Possible exploit abuse: invalid data.'));
      drupal_set_message(t("file upload failed: invalid data."), 'error');
      return 0;
    }

    // Check for file upload errors.
    switch ($file->error) {
      case 0: // UPLOAD_ERR_OK
        break;
      case 1: // UPLOAD_ERR_INI_SIZE
      case 2: // UPLOAD_ERR_FORM_SIZE
        drupal_set_message(t("file upload failed: file size too big."), 'error');
        return 0;
      case 3: // UPLOAD_ERR_PARTIAL
      case 4: // UPLOAD_ERR_NO_FILE
        drupal_set_message(t("file upload failed: incomplete upload."), 'error');
        return 0;
      default: // Unknown error
        drupal_set_message(t("file upload failed: unkown error."), 'error');
        return 0;
    }

    unset($_SESSION['file_uploads'][is_object($source) ? $source->source : $source]);
    if (file_move($file, $dest, $replace)) {
      if ($temporary) {
        $_SESSION['file_uploads'][$source] = $file;
      }
      return $file;
    }
    return 0;
  }
  else {
    // In case of previews return previous file object.
    if (file_exists($_SESSION['file_uploads'][$source]->path)) {
      return $_SESSION['file_uploads'][$source];
    }
  }
  return 0;
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
    header($header);
  }

  $source = file_create_path($source);

  // Transfer file in 1024 byte chunks to save memory usage.
  $fd = fopen($source, 'rb');
  while (!feof($fd)) {
    print fgets($fd, 1024);
  }
  fclose($fd);
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
        print theme('page', message_access());
      }
      elseif (is_array($headers)) {
        file_transfer($file, $headers);
      }
    }
  }
  drupal_not_found();
}

/**
 * Finds all files that match a given mask in a given directory. Searches
 * recursively.
 */
function file_scan_directory($dir, $mask, $nomask = array('.', '..', 'CVS'), $callback = 0) {
  $files = array();
  if (is_dir($dir) && $handle = opendir($dir)) {
    while ($file = readdir($handle)) {
      if (!in_array($file, $nomask)) {
        if (is_dir("$dir/$file")) {
          $files = array_merge($files, file_scan_directory("$dir/$file", $mask, $nomask, $callback));
        }
        elseif (ereg($mask, $file)) {
          $name = basename($file);
          $files["$dir/$file"]->filename = "$dir/$file";
          $files["$dir/$file"]->name = substr($name, 0, strrpos($name, '.'));
          if ($callback) {
            $callback("$dir/$file");
          }
        }
      }
    }
    closedir($handle);
  }
  return $files;
}

?>
