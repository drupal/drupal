<?php

namespace Drupal\Core\Cache;

/**
 * Indicates that caching is optional.
 *
 * This interface can be used to suggest that certain implementations are fast
 * enough that they do not require additional caching.
 *
 * If and how exactly this is implemented and used depends on the specific
 * system.
 *
 * Examples:
 *  - If all active access policies implement this interface,
 *    \Drupal\Core\Session\AccessPolicyProcessor will skip the persistent cache.
 */
interface CacheOptionalInterface {

}
