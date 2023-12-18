<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Helper methods for cache.
 *
 * @ingroup cache
 */
class Cache {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   */
  const PERMANENT = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * Merges arrays of cache contexts and removes duplicates.
   *
   * @param string[] ...
   *   Cache contexts arrays to merge.
   *
   * @return string[]
   *   The merged array of cache contexts.
   */
  public static function mergeContexts(array ...$cache_contexts) {
    $cache_contexts = array_unique(array_merge(...$cache_contexts));
    assert(\Drupal::service('cache_contexts_manager')->assertValidTokens($cache_contexts), sprintf('Failed to assert that "%s" are valid cache contexts.', implode(', ', $cache_contexts)));
    return $cache_contexts;
  }

  /**
   * Merges arrays of cache tags and removes duplicates.
   *
   * The cache tags array is returned in a format that is valid for
   * \Drupal\Core\Cache\CacheBackendInterface::set().
   *
   * When caching elements, it is necessary to collect all cache tags into a
   * single array, from both the element itself and all child elements. This
   * allows items to be invalidated based on all tags attached to the content
   * they're constituted from.
   *
   * @param string[] ...
   *   Cache tags arrays to merge.
   *
   * @return string[]
   *   The merged array of cache tags.
   */
  public static function mergeTags(array ...$cache_tags) {
    $cache_tags = array_unique(array_merge(...$cache_tags));
    assert(Inspector::assertAllStrings($cache_tags), 'Cache tags must be valid strings');
    return $cache_tags;
  }

  /**
   * Merges max-age values (expressed in seconds), finds the lowest max-age.
   *
   * Ensures infinite max-age (Cache::PERMANENT) is taken into account.
   *
   * @param int ...
   *   Max age values to merge.
   *
   * @return int
   *   The minimum max-age value.
   */
  public static function mergeMaxAges(...$max_ages) {
    // Remove Cache::PERMANENT values to return the correct minimum value.
    $max_ages = array_filter($max_ages, function ($max_age) {
      return $max_age !== Cache::PERMANENT;
    });

    // If there are no max ages left return Cache::PERMANENT, otherwise return
    // the minimum value.
    return empty($max_ages) ? Cache::PERMANENT : min($max_ages);
  }

  /**
   * Build an array of cache tags from a given prefix and an array of suffixes.
   *
   * Each suffix will be converted to a cache tag by appending it to the prefix,
   * with a colon between them.
   *
   * @param string $prefix
   *   A prefix string.
   * @param array $suffixes
   *   An array of suffixes. Will be cast to strings.
   * @param string $glue
   *   A string to be used as glue for concatenation. Defaults to a colon.
   *
   * @return string[]
   *   An array of cache tags.
   */
  public static function buildTags($prefix, array $suffixes, $glue = ':') {
    $tags = [];
    foreach ($suffixes as $suffix) {
      $tags[] = $prefix . $glue . $suffix;
    }
    return $tags;
  }

  /**
   * Marks cache items from all bins with any of the specified tags as invalid.
   *
   * @param string[] $tags
   *   The list of tags to invalidate cache items for.
   */
  public static function invalidateTags(array $tags) {
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
  }

  /**
   * Gets all cache bin services.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface[]
   *   An array of cache backend objects keyed by cache bins.
   */
  public static function getBins() {
    $bins = [];
    $container = \Drupal::getContainer();
    foreach ($container->getParameter('cache_bins') as $service_id => $bin) {
      $bins[$bin] = $container->get($service_id);
    }
    return $bins;
  }

  /**
   * Gets all memory cache bin services.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface[]
   *   An array of cache backend objects keyed by memory cache bins.
   */
  public static function getMemoryBins(): array {
    $bins = [];
    $container = \Drupal::getContainer();
    foreach ($container->getParameter('memory_cache_bins') as $service_id => $bin) {
      $bins[$bin] = $container->get($service_id);
    }
    return $bins;
  }

  /**
   * Generates a hash from a query object, to be used as part of the cache key.
   *
   * This smart caching strategy saves Drupal from querying and rendering to
   * HTML when the underlying query is unchanged.
   *
   * Expensive queries should use the query builder to create the query and then
   * call this function. Executing the query and formatting results should
   * happen in a #pre_render callback.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. No
   *   replacement provided.
   *
   * @see https://www.drupal.org/node/3308507
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A select query object.
   *
   * @return string
   *   A hash of the query arguments.
   */
  public static function keyFromQuery(SelectInterface $query) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. No replacement provided. See https://www.drupal.org/node/3322044', E_USER_DEPRECATED);
    $query->preExecute();
    $keys = [(string) $query, $query->getArguments()];
    return hash('sha256', serialize($keys));
  }

}
