<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * A value object to store generated cache keys with its cacheability metadata.
 */
class ContextCacheKeys extends CacheableMetadata {

  /**
   * The generated cache keys.
   *
   * @var string[]
   */
  protected $keys;

  /**
   * Constructs a ContextCacheKeys object.
   *
   * @param string[] $keys
   *   The cache context keys.
   */
  public function __construct(array $keys) {
    $this->keys = $keys;
  }

  /**
   * Gets the generated cache keys.
   *
   * @return string[]
   *   The cache keys.
   */
  public function getKeys() {
    return $this->keys;
  }

}
