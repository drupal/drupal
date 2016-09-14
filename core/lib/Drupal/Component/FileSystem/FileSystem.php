<?php

namespace Drupal\Component\FileSystem;

/**
 * Provides file system functions.
 */
class FileSystem {

  /**
   * Discovers a writable system-appropriate temporary directory.
   *
   * @return string|false
   *   A string containing the path to the temporary directory, or FALSE if no
   *   suitable temporary directory can be found.
   */
  public static function getOsTemporaryDirectory() {
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
    $directories[] = sys_get_temp_dir();

    foreach ($directories as $directory) {
      if (is_dir($directory) && is_writable($directory)) {
        // Both sys_get_temp_dir() and ini_get('upload_tmp_dir') can return paths
        // with a trailing directory separator.
        return rtrim($directory, DIRECTORY_SEPARATOR);
      }
    }
    return FALSE;
  }

}
