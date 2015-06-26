<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\CacheTestBase.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\simpletest\WebTestBase;

/**
 * Provides helper methods for cache tests.
 */
abstract class CacheTestBase extends WebTestBase {

  protected $defaultBin = 'render';
  protected $defaultCid = 'test_temporary';
  protected $defaultValue = 'CacheTest';

  /**
   * Checks whether or not a cache entry exists.
   *
   * @param $cid
   *   The cache id.
   * @param $var
   *   The variable the cache should contain.
   * @param $bin
   *   The bin the cache item was stored in.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function checkCacheExists($cid, $var, $bin = NULL) {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }

    $cached = \Drupal::cache($bin)->get($cid);

    return isset($cached->data) && $cached->data == $var;
  }

  /**
   * Asserts that a cache entry exists.
   *
   * @param $message
   *   Message to display.
   * @param $var
   *   The variable the cache should contain.
   * @param $cid
   *   The cache id.
   * @param $bin
   *   The bin the cache item was stored in.
   */
  protected function assertCacheExists($message, $var = NULL, $cid = NULL, $bin = NULL) {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }
    if ($cid == NULL) {
      $cid = $this->defaultCid;
    }
    if ($var == NULL) {
      $var = $this->defaultValue;
    }

    $this->assertTrue($this->checkCacheExists($cid, $var, $bin), $message);
  }

  /**
   * Asserts that a cache entry has been removed.
   *
   * @param $message
   *   Message to display.
   * @param $cid
   *   The cache id.
   * @param $bin
   *   The bin the cache item was stored in.
   */
  function assertCacheRemoved($message, $cid = NULL, $bin = NULL) {
    if ($bin == NULL) {
      $bin = $this->defaultBin;
    }
    if ($cid == NULL) {
      $cid = $this->defaultCid;
    }

    $cached = \Drupal::cache($bin)->get($cid);
    $this->assertFalse($cached, $message);
  }
}
