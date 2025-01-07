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
    return array_pop($chain);
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
      /** @phpstan-ignore-next-line */
      $this->cacheBackend->set($chain_cid, new CacheRedirect($cacheability));
    }

    $this->cacheBackend->set($cid, $data, $this->maxAgeToExpire($cacheability->getCacheMaxAge()), $optimized_cacheability->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $keys, CacheableDependencyInterface $initial_cacheability): void {
    $chain = $this->getRedirectChain($keys, $initial_cacheability);
    $this->cacheBackend->delete(array_key_last($chain));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $keys, CacheableDependencyInterface $initial_cacheability): void {
    $chain = $this->getRedirectChain($keys, $initial_cacheability);
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
    $cid = $this->createCacheIdFast($keys, $initial_cacheability);
    $chain[$cid] = $result = $this->cacheBackend->get($cid);

    while ($result && $result->data instanceof CacheRedirect) {
      $cid = $this->createCacheIdFast($keys, $result->data);
      $chain[$cid] = $result = $this->cacheBackend->get($cid);
    }

    return $chain;
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

}
