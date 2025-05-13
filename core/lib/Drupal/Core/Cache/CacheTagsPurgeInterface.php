<?php

declare(strict_types=1);

namespace Drupal\Core\Cache;

/**
 * Provides purging of cache tag invalidations.
 *
 * Backends that persistently store cache tag invalidations can use this
 * interface to implement purging of cache tag invalidations. By default, cache
 * tag purging will only be called during drupal_flush_all_caches(), after all
 * other caches have been cleared.
 *
 * @ingroup cache
 */
interface CacheTagsPurgeInterface {

  /**
   * Purge cache tag invalidations.
   */
  public function purge(): void;

}
