<?php

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A RedirectResponse that contains and can expose cacheability metadata.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.
 *
 * @see \Drupal\Core\Cache\Cache
 * @see \Drupal\Core\Cache\CacheableMetadata
 * @see \Drupal\Core\Cache\CacheableResponseTrait
 */
class CacheableRedirectResponse extends RedirectResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
