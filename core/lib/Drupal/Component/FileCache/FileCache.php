<?php

/**
 * @file
 * Contains \Drupal\Component\FileCache\FileCache.
 */

namespace Drupal\Component\FileCache;

/**
 * Allows to cache data based on file modification dates.
 */
class FileCache implements FileCacheInterface {

  /**
   * Prefix that is used for cache entries.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Static cache that contains already loaded cache entries.
   *
   * @var array
   */
  protected static $cached = [];

  /**
   * The collection identifier of this cache.
   *
   * @var string
   */
  protected $collection;

  /**
   * The cache backend backing this FileCache object.
   *
   * @var \Drupal\Component\FileCache\FileCacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a FileCache object.
   *
   * @param string $prefix
   *   The cache prefix.
   * @param string $collection
   *   A collection identifier to ensure that the same files could be cached for
   *   different purposes without clashing.
   * @param string|null $cache_backend_class
   *   (optional) The class that should be used as cache backend.
   * @param array $cache_backend_configuration
   *   (optional) The configuration for the backend class.
   */
  public function __construct($prefix, $collection, $cache_backend_class = NULL, array $cache_backend_configuration = []) {

    if (empty($prefix)) {
      throw new \InvalidArgumentException('Required prefix configuration is missing');
    }

    $this->prefix = $prefix;
    $this->collection = $collection;

    if (isset($cache_backend_class)) {
      $this->cache = new $cache_backend_class($cache_backend_configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($filepath) {
    $filepaths = [$filepath];
    $cached = $this->getMultiple($filepaths);
    return isset($cached[$filepath]) ? $cached[$filepath] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $filepaths) {
    $file_data = [];
    $remaining_cids = [];

    // First load from the static cache what we can.
    foreach ($filepaths as $filepath) {
      if (!file_exists($filepath)) {
        continue;
      }

      $realpath = realpath($filepath);
      // If the file exists but realpath returns nothing, it is using a stream
      // wrapper, those are not supported.
      if (empty($realpath)) {
        continue;
      }

      $cid = $this->prefix . ':' . $this->collection . ':' . $realpath;
      if (isset(static::$cached[$cid]) && static::$cached[$cid]['mtime'] == filemtime($filepath)) {
        $file_data[$filepath] = static::$cached[$cid]['data'];
      }
      else {
        // Collect a list of cache IDs that we still need to fetch from cache
        // backend.
        $remaining_cids[$cid] = $filepath;
      }
    }

    // If there are any cache IDs left to fetch from the cache backend.
    if ($remaining_cids && $this->cache) {
      $cache_results = $this->cache->fetch(array_keys($remaining_cids)) ?: [];
      foreach ($cache_results as $cid => $cached) {
        $filepath = $remaining_cids[$cid];
        if ($cached['mtime'] == filemtime($filepath)) {
          $file_data[$cached['filepath']] = $cached['data'];
          static::$cached[$cid] = $cached;
        }
      }
    }

    return $file_data;
  }

  /**
   * {@inheritdoc}
   */
  public function set($filepath, $data) {
    $realpath = realpath($filepath);
    $cached = [
      'mtime' => filemtime($filepath),
      'filepath' => $filepath,
      'data' => $data,
    ];

    $cid = $this->prefix . ':' . $this->collection . ':' . $realpath;
    static::$cached[$cid] = $cached;
    if ($this->cache) {
      $this->cache->store($cid, $cached);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($filepath) {
    $realpath = realpath($filepath);
    $cid = $this->prefix . ':' . $this->collection . ':' . $realpath;

    unset(static::$cached[$cid]);
    if ($this->cache) {
      $this->cache->delete($cid);
    }
  }

  /**
   * Resets the static cache.
   *
   * @todo Replace this once https://www.drupal.org/node/2260187 is in.
   */
  public static function reset() {
    static::$cached = [];
  }

}
