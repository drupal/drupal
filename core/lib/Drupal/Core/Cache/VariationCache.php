<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Wraps a regular cache backend to make it support cache contexts.
 *
 * @ingroup cache
 */
class VariationCache implements VariationCacheInterface {

  /**
   * Stores redirect chain lookups until the next set, invalidate or delete.
   *
   * Array keys are the cache IDs constructed from the cache keys and initial
   * cacheability and values are arrays where each step of a redirect chain is
   * recorded.
   *
   * These arrays are indexed by the cache IDs being followed during the chain
   * and the CacheRedirect objects that construct the chain. At the end there
   * should always be a value of FALSE for a cache miss, or a CacheRedirect for
   * a cache hit because we cannot store the cache hit itself into a property
   * that does not support invalidation based on cache metadata. By storing the
   * last CacheRedirect that led to the hit, we can at least avoid having to
   * retrieve the entire chain again to get to the actual cached data.
   *
   * @var array
   */
  protected array $redirectChainCache = [];

  /**
   * Constructs a new VariationCache object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend to wrap.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cacheContextsManager
   *   The cache contexts manager.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected CacheBackendInterface $cacheBackend,
    protected CacheContextsManager $cacheContextsManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get(array $keys, CacheableDependencyInterface $initial_cacheability) {
    $chain = $this->getRedirectChain($keys, $initial_cacheability);
    return end($chain);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $items): array {
    // This method does not use ::getRedirectChain() like ::get() does, because
    // we are looking for multiple cache entries and can therefore optimize the
    // following of redirect chains by calling ::getMultiple() on the underlying
    // cache backend.
    //
    // However, ::getRedirectChain() has an internal cache that we could both
    // benefit from and contribute to whenever we call this function. So any use
    // or manipulation of $this->redirectChainCache below is for optimization
    // purposes. A description of the internal cache structure is on the
    // property documentation of $this->redirectChainCache.
    //
    // Create a map of CIDs with their associated $items index and cache keys.
    $cid_map = [];
    foreach ($items as $index => [$keys, $cacheability]) {
      // Try to optimize based on the cached redirect chain.
      if ($chain = $this->getValidatedCachedRedirectChain($keys, $cacheability)) {
        $last_item = end($chain);

        // Immediately skip processing the CID for cache misses.
        if ($last_item === FALSE) {
          continue;
        }

        // We do not need to calculate the initial CID as its part of the chain.
        $initial_cid = array_key_first($chain);

        // Prime the CID map with the last known redirect for the initial CID.
        assert($last_item->data instanceof CacheRedirect);
        $cid = $this->createCacheIdFast($keys, $last_item->data);
      }
      else {
        $cid = $initial_cid = $this->createCacheIdFast($keys, $cacheability);
      }

      $cid_map[$cid] = [
        'index' => $index,
        'keys' => $keys,
        'initial' => $initial_cid,
      ];
    }

    // Go over all CIDs and update the map according to found redirects. If the
    // map is empty, it means we've followed all CIDs to their final result or
    // lack thereof.
    $results = [];
    while (!empty($cid_map)) {
      $new_cid_map = [];

      $fetch_cids = array_keys($cid_map);
      foreach ($this->cacheBackend->getMultiple($fetch_cids) as $cid => $result) {
        $info = $cid_map[$cid];

        // Add redirects to the next CID map, so the next iteration can look
        // them all up in one ::getMultiple() call to the cache backend.
        if ($result->data instanceof CacheRedirect) {
          $redirect_cid = $this->createCacheIdFast($info['keys'], $result->data);
          $new_cid_map[$redirect_cid] = $info;
          $this->redirectChainCache[$info['initial']][$cid] = $result;
          continue;
        }

        $results[$info['index']] = $result;
      }

      // Any CID that did not get a cache hit is still in $fetch_cids. Add them
      // to the internal redirect chain cache as a miss.
      foreach ($fetch_cids as $fetch_cid) {
        $info = $cid_map[$fetch_cid];
        $this->redirectChainCache[$info['initial']][$fetch_cid] = FALSE;
      }

      $cid_map = $new_cid_map;
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function set(array $keys, $data, CacheableDependencyInterface $cacheability, CacheableDependencyInterface $initial_cacheability): void {
    $initial_contexts = $initial_cacheability->getCacheContexts();
    $contexts = $cacheability->getCacheContexts();

    if ($missing_contexts = array_diff($initial_contexts, $contexts)) {
      throw new \LogicException(sprintf(
        'The complete set of cache contexts for a variation cache item must contain all of the initial cache contexts, missing: %s.',
        implode(', ', $missing_contexts)
      ));
    }

    // Don't store uncacheable items.
    if ($cacheability->getCacheMaxAge() === 0) {
      return;
    }

    // Track the potential effect of cache context optimization on cache tags.
    $optimized_cacheability = CacheableMetadata::createFromObject($cacheability);
    $cid = $this->createCacheId($keys, $optimized_cacheability);

    // Check whether we had any cache redirects leading to the cache ID already.
    // If there are none, we know that there is no proper redirect path to the
    // cache ID we're trying to store the data at. This may be because there is
    // either no full redirect path yet or there is one that is too specific at
    // a given step of the way. In case of the former, we simply need to store a
    // redirect. In case of the latter, we need to replace the overly specific
    // step with a simpler one.
    $chain = $this->getRedirectChain($keys, $initial_cacheability);
    if (!array_key_exists($cid, $chain)) {
      // We can easily find overly specific redirects by comparing their cache
      // contexts to the ones we have here. If a redirect has more or different
      // contexts, it needs to be replaced with a simplified version.
      //
      // Simplifying overly specific redirects can be done in two ways:
      //
      // -------
      //
      // Problem: The redirect is a superset of the current cache contexts.
      // Solution: We replace the redirect with the current contexts.
      //
      // Example: Suppose we try to store an object with context A, whereas we
      // already have a redirect that uses A and B. In this case we simply store
      // the object at the address designated by context A and next time someone
      // tries to load the initial AB object, it will restore its redirect path
      // by adding an AB redirect step after A.
      //
      // -------
      //
      // Problem: The redirect overlaps, with both options having unique values.
      // Solution: Find the common contexts and use those for a new redirect.
      //
      // Example: Suppose we try to store an object with contexts A and C, but
      // we already have a redirect that uses A and B. In this case we find A to
      // be the common cache context and replace the redirect with one only
      // using A, immediately followed by one for AC so there is a full path to
      // the data we're trying to set. Next time someone tries to load the
      // initial AB object, it will restore its redirect path by adding an AB
      // redirect step after A.
      $previous_step_contexts = $initial_contexts;
      foreach ($chain as $chain_cid => $result) {
        if ($result && $result->data instanceof CacheRedirect) {
          $result_contexts = $result->data->getCacheContexts();
          if (array_diff($result_contexts, $contexts)) {
            // Check whether we have an overlap scenario as we need to manually
            // create an extra redirect in that case.
            $common_contexts = array_intersect($result_contexts, $contexts);

            // If the only common contexts are those we've seen before, it means
            // we are trying to set a redirect at an address that is completely
            // different from the one that was already there. This cannot be
            // allowed as it completely breaks the redirect system.
            //
            // Example: The value for context A is 'foo' and we are trying to
            // store a redirect with AB at A:foo. Then, for a different value of
            // B, we are trying to store a redirect at A:foo with AC. This makes
            // no sense as there would now no longer be a way to find the first
            // item that triggered the initial redirect.
            //
            // This usually occurs when using calculated cache contexts and the
            // author tried to manually optimize them. E.g.: When using
            // user.roles:anonymous and in one of the outcomes we end up varying
            // by user.roles. In that case, both user.roles:anonymous and
            // user.roles need to be present on the cacheable metadata, even
            // though they will eventually be optimized into user.roles. The
            // cache needs all the initial information to do its job and if an
            // author were to manually optimize this prematurely, it would be
            // impossible to properly store a redirect chain.
            //
            // Another way this might happen is if a new object that can specify
            // cacheable metadata is instantiated without inheriting the cache
            // contexts of all the logic that happened up until that point. A
            // common example of this is when people immediately return the
            // result of one of the factory methods on AccessResult, without
            // adding the cacheability from previous access checks that did not
            // lead to a value being returned.
            if (!array_diff($common_contexts, $previous_step_contexts)) {
              trigger_error(sprintf(
                'Trying to overwrite a cache redirect for "%s" with one that has nothing in common, old one at address "%s" was pointing to "%s", new one points to "%s".',
                $chain_cid,
                implode(', ', $previous_step_contexts),
                implode(', ', array_diff($result_contexts, $previous_step_contexts)),
                implode(', ', array_diff($contexts, $previous_step_contexts)),
              ), E_USER_WARNING);
            }

            // != is the most appropriate comparison operator here, since we
            // only want to know if any keys or values don't match.
            if ($common_contexts != $contexts) {
              // Set the redirect to the common contexts at the current address.
              // In the above example this is essentially overwriting the
              // redirect to AB with a redirect to A.
              $common_cacheability = (new CacheableMetadata())->setCacheContexts($common_contexts);
              $this->cacheBackend->set($chain_cid, new CacheRedirect($common_cacheability));

              // Before breaking the loop, set the current address to the next
              // one in line so that we can store the full redirect as well. In
              // the above example, this is the part where we immediately also
              // store a redirect to AC at the CID that A pointed to.
              $chain_cid = $this->createCacheIdFast($keys, $common_cacheability);
            }
            break;
          }
          $previous_step_contexts = $result_contexts;
        }
      }

      // The loop above either broke at an overly specific step or completed
      // without any problem. In both cases, $chain_cid ended up with the value
      // that we should store the new redirect at.
      //
      // Cache redirects are stored indefinitely and without tags as they never
      // need to be cleared. If they ever end up leading to a stale cache item
      // that now uses different contexts then said item will either follow an
      // existing path of redirects or carve its own over the old one.
      // @phpstan-ignore variable.undefined
      $this->cacheBackend->set($chain_cid, new CacheRedirect($cacheability));
    }

    unset($this->redirectChainCache[$this->createCacheIdFast($keys, $initial_cacheability)]);
    $this->cacheBackend->set($cid, $data, $this->maxAgeToExpire($cacheability->getCacheMaxAge()), $optimized_cacheability->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $keys, CacheableDependencyInterface $initial_cacheability): void {
    $chain = $this->getRedirectChain($keys, $initial_cacheability);

    // Don't need to delete what could not be found.
    if (end($chain) === FALSE) {
      return;
    }

    unset($this->redirectChainCache[$this->createCacheIdFast($keys, $initial_cacheability)]);
    $this->cacheBackend->delete(array_key_last($chain));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $keys, CacheableDependencyInterface $initial_cacheability): void {
    $chain = $this->getRedirectChain($keys, $initial_cacheability);

    // Don't need to invalidate what could not be found.
    if (end($chain) === FALSE) {
      return;
    }

    unset($this->redirectChainCache[$this->createCacheIdFast($keys, $initial_cacheability)]);
    $this->cacheBackend->invalidate(array_key_last($chain));
  }

  /**
   * Performs a full get, returning every step of the way.
   *
   * This will check whether there is a cache redirect and follow it if so. It
   * will keep following redirects until it gets to a cache miss or the actual
   * cache object.
   *
   * @param string[] $keys
   *   The cache keys to retrieve the cache entry for.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The cache metadata of the data to store before other systems had a chance
   *   to adjust it. This is also commonly known as "pre-bubbling" cacheability.
   *
   * @return array
   *   Every cache get that lead to the final result, keyed by the cache ID used
   *   to query the cache for that result.
   */
  protected function getRedirectChain(array $keys, CacheableDependencyInterface $initial_cacheability): array {
    $chain = $this->getValidatedCachedRedirectChain($keys, $initial_cacheability);

    // Initiate the chain if we couldn't retrieve (a partial) one from memory.
    if (empty($chain)) {
      $cid = $initial_cid = $this->createCacheIdFast($keys, $initial_cacheability);
      $chain[$cid] = $result = $this->cacheBackend->get($cid);
    }
    // If we did find one, we continue our search from the last valid redirect
    // in the chain or bypass the while loop below in case the chain ends in
    // FALSE, indicating a previous cache miss.
    else {
      $initial_cid = array_key_first($chain);
      $result = end($chain);
    }

    while ($result && $result->data instanceof CacheRedirect) {
      $cid = $this->createCacheIdFast($keys, $result->data);
      $chain[$cid] = $result = $this->cacheBackend->get($cid);
    }

    // When storing the redirect chain in memory we must take care to not store
    // a cache hit as they can be invalidated, unlike CacheRedirect objects. We
    // do store the rest of the chain because redirects can be reused safely.
    $chain_to_cache = $chain;
    if ($result !== FALSE) {
      array_pop($chain_to_cache);
    }
    $this->redirectChainCache[$initial_cid] = $chain_to_cache;

    return $chain;
  }

  /**
   * Retrieved the redirect chain from cache, validating each part.
   *
   * @param string[] $keys
   *   The cache keys to retrieve the redirect chain for.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The initial cacheability for the redirect chain.
   *
   * @return array
   *   The part of the cached redirect chain, if any, that is still valid.
   */
  protected function getValidatedCachedRedirectChain(array $keys, CacheableDependencyInterface $initial_cacheability): array {
    $cid = $this->createCacheIdFast($keys, $initial_cacheability);
    if (!isset($this->redirectChainCache[$cid])) {
      return [];
    }
    $chain = $this->redirectChainCache[$cid];

    // Only use that part of the redirect chain that is still valid. Even though
    // we do not store cache hits in the internal redirect chain cache, we can
    // still reuse the whole chain up until what would have been a cache hit.
    //
    // If part of a redirect chain no longer matches because cache contexts
    // changed values, we could perhaps still reuse part of the chain until we
    // encounter a redirect for the changed cache context value.
    //
    // There is one special case: If the very last item of the chain is a cache
    // redirect, and we cannot find anything for it, we still add the redirect
    // to the validated chain because the only way a cached chain ends in a
    // redirect is if it led to a cache hit in ::getRedirectChain().
    $valid_parts = [];
    $last_key = array_key_last($chain);
    foreach ($chain as $key => $result) {
      if ($result && $result->data instanceof CacheRedirect) {
        $cid = $this->createCacheIdFast($keys, $result->data);
        if (!isset($chain[$cid]) && $last_key !== $key) {
          break;
        }
      }
      $valid_parts[$key] = $result;
    }
    return $valid_parts;
  }

  /**
   * Maps a max-age value to an "expire" value for the Cache API.
   *
   * @param int $max_age
   *   A max-age value.
   *
   * @return int
   *   A corresponding "expire" value.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   */
  protected function maxAgeToExpire($max_age) {
    if ($max_age !== Cache::PERMANENT) {
      return (int) $this->requestStack->getMainRequest()->server->get('REQUEST_TIME') + $max_age;
    }
    return $max_age;
  }

  /**
   * Creates a cache ID based on cache keys and cacheable metadata.
   *
   * If cache contexts are optimized during the creating of the cache ID, then
   * the effect of said optimization on the cache contexts will be reflected in
   * the provided cacheable metadata.
   *
   * @param string[] $keys
   *   The cache keys of the data to store.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   The cacheable metadata of the data to store.
   *
   * @return string
   *   The cache ID.
   */
  protected function createCacheId(array $keys, CacheableMetadata &$cacheable_metadata) {
    if ($contexts = $cacheable_metadata->getCacheContexts()) {
      $context_cache_keys = $this->cacheContextsManager->convertTokensToKeys($contexts);
      $keys = array_merge($keys, $context_cache_keys->getKeys());
      $cacheable_metadata = $cacheable_metadata->merge($context_cache_keys);
    }
    return implode(':', $keys);
  }

  /**
   * Creates a cache ID based on cache keys and cacheable metadata.
   *
   * This is a simpler, faster version of ::createCacheID() to be used when you
   * do not care about how cache context optimization affects the cache tags.
   *
   * @param string[] $keys
   *   The cache keys of the data to store.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cache metadata of the data to store.
   *
   * @return string
   *   The cache ID for the redirect.
   */
  protected function createCacheIdFast(array $keys, CacheableDependencyInterface $cacheability) {
    if ($contexts = $cacheability->getCacheContexts()) {
      $context_cache_keys = $this->cacheContextsManager->convertTokensToKeys($contexts);
      $keys = array_merge($keys, $context_cache_keys->getKeys());
    }
    return implode(':', $keys);
  }

  /**
   * Reset statically cached variables.
   *
   * This is only used by tests.
   *
   * @internal
   */
  public function reset(): void {
    $this->redirectChainCache = [];
  }

}
