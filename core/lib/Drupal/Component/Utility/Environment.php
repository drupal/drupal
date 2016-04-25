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
    return ((!$memory_limit) || ($memory_limit == -1) || (Bytes::toInt($memory_limit) >= Bytes::toInt($required)));
  }

}
