<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\OpCodeCache.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helpers to handle PHP opcode caches.
 *
 * @ingroup utility
 */
class OpCodeCache {

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

    if (extension_loaded('Zend OPcache')) {
      opcache_invalidate($pathname, TRUE);
    }
    if (extension_loaded('apc')) {
      // apc_delete_file() throws a PHP warning in case the specified file was
      // not compiled yet.
      // @see http://php.net/manual/en/function.apc-delete-file.php
      @apc_delete_file($pathname);
    }
  }

}
