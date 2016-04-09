<?php

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\Response;

/**
 * A response that contains and can expose cacheability metadata.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.
 *
 * @see \Drupal\Core\Cache\Cache
 * @see \Drupal\Core\Cache\CacheableMetadata
 * @see \Drupal\Core\Cache\CacheableResponseTrait
 */
class CacheableResponse extends Response implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
