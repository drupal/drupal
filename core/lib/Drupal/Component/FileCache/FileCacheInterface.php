<?php

/**
 * @file
 * Contains \Drupal\Component\FileCache\FileCacheInterface.
 */

namespace Drupal\Component\FileCache;

/**
 * Interface for objects that allow caching file data.
 *
 * Parsing YAML, annotations or similar data out of files can be a
 * time-consuming process, especially since those files usually don't change
 * and identical data is parsed over and over again.
 *
 * File cache is a self-contained caching layer for such processing, that relies
 * on the file modification to ensure that cached data is still up to date and
 * does not need to be invalidated externally.
 */
interface FileCacheInterface {

  /**
   * Gets data based on a filename.
   *
   * @param string $filepath
   *   Path of the file that the cached data is based on.
   *
   * @return mixed|null
   *   The data that was persisted with set() or NULL if there is no data
   *   or the file has been modified.
   */
  public function get($filepath);

  /**
   * Gets data based on filenames.
   *
   * @param string[] $filepaths
   *   List of file paths used as cache identifiers.
   *
   * @return array
   *   List of cached data keyed by the passed in file paths.
   */
  public function getMultiple(array $filepaths);

  /**
   * Stores data based on a filename.
   *
   * @param string $filepath
   *   Path of the file that the cached data is based on.
   * @param mixed $data
   *   The data that should be cached.
   */
  public function set($filepath, $data);

  /**
   * Deletes data from the cache.
   *
   * @param string $filepath
   *   Path of the file that the cached data is based on.
   */
  public function delete($filepath);

}
