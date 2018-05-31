<?php

namespace Drupal\Core\Cache\MemoryCache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Defines an interface for memory cache implementations.
 *
 * This has additional requirements over CacheBackendInterface and
 * CacheTagsInvalidatorInterface. Objects stored must be the same instance when
 * retrieved from cache, so that this can be used as a replacement for protected
 * properties and similar.
 *
 * @ingroup cache
 */
interface MemoryCacheInterface extends CacheBackendInterface, CacheTagsInvalidatorInterface {}
