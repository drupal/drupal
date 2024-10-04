<?php

namespace Drupal\Component\Utility;

/**
 * Provides helpers to handle PHP opcode caches.
 *
 * @ingroup utility
 */
class OpCodeCache {

  /**
   * Checks if OpCodeCache is enabled.
   *
   * @return bool
   *   TRUE if OPcache is enabled, FALSE otherwise.
   */
  public static function isEnabled() {
    return extension_loaded('Zend OPcache') && ini_get('opcache.enable');
  }

  /**
   * Invalidates a PHP file from a possibly active opcode cache.
   *
   * In case the opcode cache does not support to invalidate an individual file,
   * the entire cache will be flushed.
   *
   * @param string $pathname
   *   The absolute pathname of the PHP file to invalidate.
   */
  public static function invalidate($pathname) {
    clearstatcache(TRUE, $pathname);

    // Check if the Zend OPcache is enabled and if so invalidate the file.
    if (function_exists('opcache_invalidate')) {
      opcache_invalidate($pathname, TRUE);
    }
  }

}
