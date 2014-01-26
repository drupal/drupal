<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\CacheDecorator.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\Cache;

/**
 * Enables static and persistent caching of discovered plugin definitions.
 */
class CacheDecorator implements CachedDiscoveryInterface {

  /**
   * The cache key used to store the definition list.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * The cache bin used to store the definition list.
   *
   * @var string
   */
  protected $cacheBin;

  /**
   * The timestamp indicating when the definition list cache expires.
   *
   * @var int
   */
  protected $cacheExpire;

  /**
   * The cache tags associated with the definition list.
   *
   * @var array
   */
  protected $cacheTags;

  /**
   * The plugin definitions of the decorated discovery class.
   *
   * @var array
   */
  protected $definitions;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Constructs a Drupal\Core\Plugin\Discovery\CacheDecorator object.
   *
   * It uses the DiscoveryInterface object it should decorate.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   * @param string $cache_key
   *   The cache identifier used for storage of the definition list.
   * @param string $cache_bin
   *   The cache bin used for storage and retrieval of the definition list.
   * @param int $cache_expire
   *   A Unix timestamp indicating that the definition list will be considered
   *   invalid after this time.
   * @param array $cache_tags
   *   The cache tags associated with the definition list.
   */
  public function __construct(DiscoveryInterface $decorated, $cache_key, $cache_bin = 'cache', $cache_expire = Cache::PERMANENT, array $cache_tags = array()) {
    $this->decorated = $decorated;
    $this->cacheKey = $cache_key;
    $this->cacheBin = $cache_bin;
    $this->cacheExpire = $cache_expire;
    $this->cacheTags = $cache_tags;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinition().
   */
  public function getDefinition($plugin_id) {
    // Optimize for fast access to definitions if they are already in memory.
    if (isset($this->definitions)) {
      // Avoid using a ternary that would create a copy of the array.
      if (isset($this->definitions[$plugin_id])) {
        return $this->definitions[$plugin_id];
      }
      else {
        return;
      }
    }

    $definitions = $this->getDefinitions();
    // Avoid using a ternary that would create a copy of the array.
    if (isset($definitions[$plugin_id])) {
      return $definitions[$plugin_id];
    }
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    // Optimize for fast access to definitions if they are already in memory.
    if (isset($this->definitions)) {
      return $this->definitions;
    }

    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->decorated->getDefinitions();
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

  /**
   * Returns the cached plugin definitions of the decorated discovery class.
   *
   * @return mixed
   *   On success this will return an array of plugin definitions. On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array
   *   and would actually be returned by the getDefinitions() method.
   */
  protected function getCachedDefinitions() {
    if (!isset($this->definitions) && isset($this->cacheKey) && $cache = $this->cache($this->cacheBin)->get($this->cacheKey)) {
      $this->definitions = $cache->data;
    }
    return $this->definitions;
  }

  /**
   * Sets a cache of plugin definitions for the decorated discovery class.
   *
   * @param array $definitions
   *   List of definitions to store in cache.
   */
  protected function setCachedDefinitions($definitions) {
    if (isset($this->cacheKey)) {
      $this->cache($this->cacheBin)->set($this->cacheKey, $definitions, $this->cacheExpire, $this->cacheTags);
    }
    $this->definitions = $definitions;
  }

  /**
   * Implements \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface::clearCachedDefinitions().
   */
  public function clearCachedDefinitions() {
    // If there are any cache tags, clear cache based on those.
    if (!empty($this->cacheTags)) {
      Cache::deleteTags($this->cacheTags);
    }
    // Otherwise, just delete the specified cache key.
    else if (isset($this->cacheKey)) {
      $this->cache($this->cacheBin)->delete($this->cacheKey);
    }
    $this->definitions = NULL;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }

  /**
   * Wraps the \Drupal::cache() method.
   */
  protected function cache($bin) {
    return \Drupal::cache($bin);
  }

}
