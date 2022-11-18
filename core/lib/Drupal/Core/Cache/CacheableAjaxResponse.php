<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Ajax\AjaxResponse;

/**
 * A AjaxResponse that contains and can expose cacheability metadata.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.
 *
 * @see \Drupal\Core\Cache\Cache
 * @see \Drupal\Core\Cache\CacheableMetadata
 * @see \Drupal\Core\Cache\CacheableResponseTrait
 */
class CacheableAjaxResponse extends AjaxResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
