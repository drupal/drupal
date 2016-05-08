<?php

namespace Drupal\Core\Cache;

/**
 * Provides checksums for cache tag invalidations.
 *
 * Cache backends can use this to check if any cache tag invalidations happened
 * for a stored cache item.
 *
 * To do so, they can inject the cache_tags.invalidator.checksum service, and
 * when a cache item is written, store cache tags together with the current
 * checksum, calculated by getCurrentChecksum(). When a cache item is fetched,
 * the checksum can be validated with isValid(). The service will return FALSE
 * if any of those cache tags were invalidated in the meantime.
 *
 * @ingroup cache
 */
interface CacheTagsChecksumInterface {

  /**
   * Returns the sum total of validations for a given set of tags.
   *
   * Called by a backend when storing a cache item.
   *
   * @param string[] $tags
   *   Array of cache tags.
   *
   * @return string
   *   Cache tag invalidations checksum.
   */
  public function getCurrentChecksum(array $tags);

  /**
   * Returns whether the checksum is valid for the given cache tags.
   *
   * Used when retrieving a cache item in a cache backend, to verify that no
   * cache tag based invalidation happened.
   *
   * @param int $checksum
   *   The checksum that was stored together with the cache item.
   * @param string[] $tags
   *   The cache tags that were stored together with the cache item.
   *
   * @return bool
   *   FALSE if cache tag invalidations happened for the passed in tags since
   *   the cache item was stored, TRUE otherwise.
   */
  public function isValid($checksum, array $tags);

  /**
   * Reset statically cached tags.
   *
   * This is only used by tests.
   */
  public function reset();

}
