<?php

/**
 * @file
 * Definition of Drupal\Core\SystemListing.
 */

namespace Drupal\Core;

/**
 * Returns information about system object files (modules, themes, etc.).
 *
 * This class requires the list of profiles to be scanned (see
 * \Drupal\Core\SystemListing::scan) to be passed into the constructor. Also,
 * info files are not parsed.
 */
class SystemListing {

  /**
   * Construct this listing object.
   *
   * @param array $profiles
   *   A list of profiles to search their directories for in addition to the
   *   default directories.
   */
  function __construct($profiles = array()) {
    $this->profiles = $profiles;
  }

  /**
   * Returns information about system object files (modules, themes, etc.).
   *
   * This function is used to find all or some system object files (module
   * files, theme files, etc.) that exist on the site. It searches in several
   * locations, depending on what type of object you are looking for. For
   * instance, if you are looking for modules and call:
   * @code
   * $scanner = new SystemListing();
   * $all_modules = $scanner->scan('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.module$/', 'modules');
   * @endcode
   * this function will search:
   * - the core modules directory; i.e., /core/modules
   * - the profiles directories as defined by the profiles() method.
   * - the site-wide modules directory; i.e., /modules
   * - the all-sites directory; i.e., /sites/all/modules
   * - the site-specific directory; i.e., /sites/example.com/modules
   * in that order, and return information about all of the files ending in
   * .module in those directories.
   *
   * The information is returned in an associative array, which can be keyed
   * on the file name ($key = 'filename'), the file name without the extension
   * ($key = 'name'), or the full file stream URI ($key = 'uri'). If you use a
   * key of 'filename' or 'name', files found later in the search will take
   * precedence over files found earlier (unless they belong to a module or
   * theme not compatible with Drupal core); if you choose a key of 'uri',
   * you will get all files found.
   *
   * @param string $mask
   *   The preg_match() regular expression for the files to find. The
   *   expression must be anchored and use DRUPAL_PHP_FUNCTION_PATTERN for the
   *   file name part before the extension, since the results could contain
   *   matches that do not present valid Drupal extensions otherwise.
   * @param string $directory
   *   The subdirectory name in which the files are found. For example,
   *   'modules' will search all 'modules' directories and their
   *   sub-directories as explained above.
   * @param string $key
   *   (optional) The key to be used for the associative array returned.
   *   Possible values are:
   *   - 'uri' for the file's URI.
   *   - 'filename' for the basename of the file.
   *   - 'name' for the name of the file without the extension.
   *   For 'name' and 'filename' only the highest-precedence file is returned.
   *   Defaults to 'name'.
   *
   * @return array
   *   An associative array of file objects, keyed on the chosen key. Each
   *   element in the array is an object containing file information, with
   *   properties:
   *   - 'uri': Full URI of the file.
   *   - 'filename': File name.
   *   - 'name': Name of file without the extension.
   */
  function scan($mask, $directory, $key = 'name') {
    if (!in_array($key, array('uri', 'filename', 'name'))) {
      $key = 'uri';
    }
    $config = conf_path();

    // Search for the directory in core.
    $searchdir = array('core/' . $directory);
    foreach ($this->profiles($directory) as $profile) {
      $searchdir[] = $profile;
    }

    // Always search for contributed and custom extensions in top-level
    // directories as well as sites/all/* directories. If the same extension is
    // located in both directories, then the latter wins for legacy/historical
    // reasons.
    $searchdir[] = $directory;
    $searchdir[] = 'sites/all/' . $directory;

    if (file_exists("$config/$directory")) {
      $searchdir[] = "$config/$directory";
    }
    // @todo Find a way to skip ./config directories (but not modules/config).
    $nomask = '/^(CVS|lib|templates|css|js)$/';
    $files = array();
    // Get current list of items.
    foreach ($searchdir as $dir) {
      $files = array_merge($files, $this->process($files, $this->scanDirectory($dir, $key, $mask, $nomask)));
    }
    return $files;
  }

  /**
   * List the profiles for this directory.
   *
   * This version only returns those passed to the constructor.
   *
   * @param string $directory
   *   The current search directory like 'modules' or 'themes'.
   *
   * @return array
   *   A list of profiles.
   */
  protected function profiles($directory) {
    return $this->profiles;
  }

  /**
   * Process the files to add before adding them.
   *
   * @param array $files
   *   Every file found so far.
   * @param array $files_to_add
   *   The files found in a single directory.
   *
   * @return array
   *   The processed list of file objects. For example, the SystemListingInfo
   *   class removes files not compatible with the current core version.
   */
  protected function process(array $files, array $files_to_add) {
    return $files_to_add;
  }

  /**
   * Abbreviated version of file_scan_directory().
   *
   * @param $dir
   *   The base directory or URI to scan, without trailing slash.
   * @param $key
   *   The key to be used for the returned associative array of files.
   *     Possible values are 'uri', for the file's URI; 'filename', for the
   *     basename of the file; and 'name' for the name of the file without the
   *     extension.
   * @param $mask
   *   The preg_match() regular expression of the files to find.
   * @param $nomask
   *   The preg_match() regular expression of the files to ignore.
   *
   * @return array
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' members corresponding to the matching files.
   */
  protected function scanDirectory($dir, $key, $mask, $nomask) {
    $files = array();
    if (is_dir($dir)) {
      // Avoid warnings when opendir does not have the permissions to open a
      // directory.
      if ($handle = @opendir($dir)) {
        while (FALSE !== ($filename = readdir($handle))) {
          // Skip this file if it matches the nomask or starts with a dot.
          if ($filename[0] != '.' && !preg_match($nomask, $filename)) {
            $uri = "$dir/$filename";
            if (is_dir($uri)) {
              // Give priority to files in this folder by merging them in after
              // any subdirectory files.
              $files = array_merge($this->scanDirectory($uri, $key, $mask, $nomask), $files);
            }
            elseif (preg_match($mask, $filename)) {
              // Always use this match over anything already set in $files with
              // the same $options['key'].
              $file = new \stdClass();
              $file->uri = $uri;
              $file->filename = $filename;
              $file->name = pathinfo($filename, PATHINFO_FILENAME);
              $this->processFile($file);
              $files[$file->$key] = $file;
            }
          }
        }
        closedir($handle);
      }
    }
    return $files;
  }

  /**
   * Process each file object as it is found by scanDirectory().
   *
   * @param $file
   *   A file object.
   */
  protected function processFile($file) {
  }

}
