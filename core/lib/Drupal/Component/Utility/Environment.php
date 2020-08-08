<?php

namespace Drupal\Component\Utility;

/**
 * Provides PHP environment helper methods.
 */
class Environment {

  /**
   * Compares the memory required for an operation to the available memory.
   *
   * @param string $required
   *   The memory required for the operation, expressed as a number of bytes with
   *   optional SI or IEC binary unit prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8bytes,
   *   9mbytes).
   * @param $memory_limit
   *   (optional) The memory limit for the operation, expressed as a number of
   *   bytes with optional SI or IEC binary unit prefix (e.g. 2, 3K, 5MB, 10G,
   *   6GiB, 8bytes, 9mbytes). If no value is passed, the current PHP
   *   memory_limit will be used. Defaults to NULL.
   *
   * @return bool
   *   TRUE if there is sufficient memory to allow the operation, or FALSE
   *   otherwise.
   */
  public static function checkMemoryLimit($required, $memory_limit = NULL) {
    if (!isset($memory_limit)) {
      $memory_limit = ini_get('memory_limit');
    }

    // There is sufficient memory if:
    // - No memory limit is set.
    // - The memory limit is set to unlimited (-1).
    // - The memory limit is greater than or equal to the memory required for
    //   the operation.
    return ((!$memory_limit) || ($memory_limit == -1) || (Bytes::toNumber($memory_limit) >= Bytes::toNumber($required)));
  }

  /**
   * Attempts to set the PHP maximum execution time.
   *
   * This function is a wrapper around the PHP function set_time_limit(). When
   * called, set_time_limit() restarts the timeout counter from zero. In other
   * words, if the timeout is the default 30 seconds, and 25 seconds into script
   * execution a call such as set_time_limit(20) is made, the script will run
   * for a total of 45 seconds before timing out.
   *
   * If the current time limit is not unlimited it is possible to decrease the
   * total time limit if the sum of the new time limit and the current time
   * spent running the script is inferior to the original time limit. It is
   * inherent to the way set_time_limit() works, it should rather be called with
   * an appropriate value every time you need to allocate a certain amount of
   * time to execute a task than only once at the beginning of the script.
   *
   * Before calling set_time_limit(), we check if this function is available
   * because it could be disabled by the server administrator.
   *
   * @param int $time_limit
   *   An integer time limit in seconds, or 0 for unlimited execution time.
   *
   * @return bool
   *   Whether set_time_limit() was successful or not.
   */
  public static function setTimeLimit($time_limit) {
    if (function_exists('set_time_limit')) {
      $current = ini_get('max_execution_time');
      // Do not set time limit if it is currently unlimited.
      if ($current != 0) {
        return set_time_limit($time_limit);
      }
    }
    return FALSE;
  }

  /**
   * Determines the maximum file upload size by querying the PHP settings.
   *
   * @return int
   *   A file size limit in bytes based on the PHP upload_max_filesize and
   *   post_max_size settings.
   */
  public static function getUploadMaxSize() {
    static $max_size = -1;

    if ($max_size < 0) {
      // Start with post_max_size.
      $max_size = Bytes::toNumber(ini_get('post_max_size'));

      // If upload_max_size is less, then reduce. Except if upload_max_size is
      // zero, which indicates no limit.
      $upload_max = Bytes::toNumber(ini_get('upload_max_filesize'));
      if ($upload_max > 0 && $upload_max < $max_size) {
        $max_size = $upload_max;
      }
    }
    return $max_size;
  }

}
