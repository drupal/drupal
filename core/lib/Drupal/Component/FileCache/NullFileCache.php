<?php

namespace Drupal\Component\FileCache;

/**
 * Null implementation for the file cache.
 */
class NullFileCache implements FileCacheInterface {

  /**
   * Constructs a FileCache object.
   *
   * @param string $prefix
   *   A prefix that is used as a prefix, should be set to a secure, unique key
   *   to prevent cache pollution by a third party.
   * @param string $collection
   *   A collection identifier to ensure that the same files could be cached for
   *   different purposes without clashing.
   * @param string|null $cache_backend_class
   *   (optional) The class that should be used as cache backend.
   * @param array $cache_backend_configuration
   *   (optional) The configuration for the backend class.
   */
  public function __construct($prefix, $collection, $cache_backend_class = NULL, array $cache_backend_configuration = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($filepath) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $filepaths) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function set($filepath, $data) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete($filepath) {
  }

}
