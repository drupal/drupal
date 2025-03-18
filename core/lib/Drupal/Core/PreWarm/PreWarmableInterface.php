<?php

namespace Drupal\Core\PreWarm;

/**
 * Interface for services with prewarmable caches.
 *
 * This interface should be implemented alongside the cache_prewarmable
 * service tag.
 *
 * You should consider carefully whether your service will benefit from
 * implementing this interface, it should only be used when:
 * 1. Your service has an expensive cache rebuild, such as attribute or YAML
 * discovery.
 * 2. Your service is in the critical path of most requests to the site and is
 * likely to be impacted by a cache stampede. If it's mainly used on cron or
 * admin pages, then prewarming would be counter-productive.
 * Additionally note that there is no guaranteed code path by which your service
 * will be called, so it can not (for example) assume that routing has been
 * completed. You should either ensure that you can prewarm your cache without
 * knowing the route or current theme, or return early if these aren't
 * available. You should also ensure that if your ::preWarm() method is called
 * early in a request, that later requests to your service retrieve the cached
 * information from memory rather than requesting it from the cache bin again.
 *
 * @see Drupal\Core\Prewarm\PreWarmerInterface
 */
interface PreWarmableInterface {

  /**
   * Build any cache item or items that this service relies on.
   */
  public function preWarm(): void;

}
