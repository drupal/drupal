<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\FileCache\StaticFileCacheBackend.
 */

namespace Drupal\Tests\Component\FileCache;

use Drupal\Component\FileCache\FileCacheBackendInterface;

/**
 * Allows to cache data based on file modification dates in a static cache.
 */
class StaticFileCacheBackend implements FileCacheBackendInterface {

  /**
   * Internal static cache.
   *
   * @var array
   */
  protected static $cache = [];

  /**
   * Bin used for storing the data in the static cache.
   *
   * @var string
   */
  protected $bin;

  /**
   * Constructs a PHP Storage FileCache backend.
   *
   * @param array $configuration
   *   (optional) Configuration used to configure this object.
   */
  public function __construct($configuration) {
    $this->bin = isset($configuration['bin']) ? $configuration['bin'] : 'file_cache';
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    $result = [];
    foreach ($cids as $cid) {
      if (isset(static::$cache[$this->bin][$cid])) {
        $result[$cid] = static::$cache[$this->bin][$cid];
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    static::$cache[$this->bin][$cid] = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    unset(static::$cache[$this->bin][$cid]);
  }

  /**
   * Allows tests to reset the static cache to avoid side effects.
   */
  public static function reset() {
    static::$cache = [];
  }

}
