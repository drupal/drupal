<?php

/**
 * @file
 * Contains Drupal\Core\CacheDecorator\AliasManagerCacheDecorator.
 */

namespace Drupal\Core\CacheDecorator;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * Class used by the PathSubscriber to get the system path and cache path lookups.
 */
class AliasManagerCacheDecorator implements CacheDecoratorInterface, AliasManagerInterface {

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * The cache key to use when caching system paths.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * Holds an array of previously cached paths based on a request path.
   *
   * @var array
   */
  protected $preloadedPathLookups = array();

  /**
   * Whether the cache needs to be written.
   *
   * @var boolean
   */
  protected $cacheNeedsWriting = TRUE;

  /**
   * Constructs a \Drupal\Core\CacheDecorator\AliasManagerCacheDecorator.
   */
  public function __construct(AliasManagerInterface $alias_manager, CacheBackendInterface $cache) {
    $this->aliasManager = $alias_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheKey($key) {
    $this->cacheKey = $key;
  }

  /**
   * {@inheritdoc}
   *
   * Cache an array of the system paths available on each page. We assume
   * that aliases will be needed for the majority of these paths during
   * subsequent requests, and load them in a single query during path alias
   * lookup.
   */
  public function writeCache() {
    $path_lookups = $this->getPathLookups();
    // Check if the system paths for this page were loaded from cache in this
    // request to avoid writing to cache on every request.
    if ($this->cacheNeedsWriting && !empty($path_lookups) && !empty($this->cacheKey)) {
      // Set the path cache to expire in 24 hours.
      $expire = REQUEST_TIME + (60 * 60 * 24);
      $this->cache->set($this->cacheKey, $path_lookups, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemPath($path, $path_language = NULL) {
    $system_path = $this->aliasManager->getSystemPath($path, $path_language);
    // We need to pass on the list of previously cached system paths for this
    // key to the alias manager for use in subsequent lookups.
    $cached = $this->cache->get($system_path);
    $cached_paths = array();
    if ($cached) {
      $cached_paths = $cached->data;
      $this->cacheNeedsWriting = FALSE;
    }
    $this->preloadPathLookups($cached_paths);
    return $system_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathAlias($path, $path_language = NULL) {
    return $this->aliasManager->getPathAlias($path, $path_language);
  }

  /**
   * {@inheritdoc}
   */
  public function getPathLookups() {
    return $this->aliasManager->getPathLookups();
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathLookups(array $path_list) {
    $this->aliasManager->preloadPathLookups($path_list);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    $this->cache->delete($this->cacheKey);
    $this->aliasManager->cacheClear($source);
  }
}
