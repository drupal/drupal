<?php

namespace Drupal\Core\PreWarm;

/**
 * Interface for cache prewarmers.
 *
 * Drupal has multiple registries that are fairly expensive to build: plugins,
 * theme hooks etc. These registries are required to serve most requests, and
 * therefore are in the critical path. When the cache for one of them is empty,
 * it is likely that the rest are too, usually due to a deployment.
 *
 * After a full cache clear on a high traffic site, a cache stampede may occur,
 * where multiple simultaneous requests all hit the site before caches have been
 * built. This either results in the same expensive cache item being built
 * multiple times, or in requests being caught in a lock wait pattern while
 * others build them, if this has been implemented (e.g. router rebuilds). In
 * the worst cases, it can take several seconds before any pages can be served
 * at all, meanwhile more requests are coming in, affecting both server loads
 * and concurrent request limits.
 *
 * The cache prewarm API attempts to mitigate this situation significantly.
 * Except for via the lock system, Drupal can't detect that it's in a cache
 * stampede situation itself, but there are particular caches we can assume that
 * if they're empty, then we might be. On most sites, even in a stampede
 * situation, these caches have to be built sequentially, i.e. the router has to
 * exist before a controller can be rendered, Views plugins have to be available
 * for a Views query to run, entity/field caches have to be built before
 * entities can be rendered, theme and element info caches have to be built
 * before templates can be rendered. Very few requests will try to render
 * a template without first running routing, even if some minor details will be
 * different between different routes and sites.
 *
 * To reduce duplicate work, and to enable those first pages after a cache clear
 * to be served faster, we want to divide up cache building between different
 * requests that are coming in. This is achieved by the cache_prewarmable
 * service tag and Drupal\Core\PreWarm\PreWarmableInterface* where any service
 * can define itself as prewarmable with a common method to call to warm caches.
 *
 * The default implementation takes the list of prewarmable services, and picks
 * one at random. By choosing the service at random, it increases the likelihood
 * that when multiple requests all try to prewarm at the same time, that they'll
 * try to prewarm different things. If we always chose the service to prewarm
 * sequentially, we could end up reproducing the cache stampede situation.
 *
 * @internal
 *
 * @see Drupal\Core\PreWarm\PreWarmableInterface
 * @see Drupal\Core\DrupalKernel::handle()
 * @see Drupal\Core\LockBackendAbstract::wait()
 * @see Drupal\Core\Routing\RouteProvider::preload()
 */
interface CachePreWarmerInterface {

  /**
   * Prewarms one PreWarmable service.
   *
   * @return bool
   *   TRUE if a cache was prewarmed, FALSE if there was nothing to prewarm.
   */
  public function preWarmOneCache(): bool;

  /**
   * Prewarms all PreWarmable services.
   *
   * @return bool
   *   TRUE if a cache was prewarmed, FALSE if there was nothing to prewarm.
   */
  public function preWarmAllCaches(): bool;

}
