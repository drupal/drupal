<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheTagsInvalidatorInterface
 */

namespace Drupal\Core\Cache;

/**
 * Defines required methods for classes wanting to handle cache tag changes.
 *
 * Services that implement this interface must add the cache_tags_invalidator
 * tag to be notified. Cache backends may implement this interface as well, they
 * will be notified automatically.
 *
 * @ingroup cache
 */
interface CacheTagsInvalidatorInterface {

  /**
   * Marks cache items with any of the specified tags as invalid.
   *
   * @param string[] $tags
   *   The list of tags for which to invalidate cache items.
   */
  public function invalidateTags(array $tags);

}
