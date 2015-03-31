<?php

/**
 * @file
 * Contains Drupal\Core\Path\AliasManager.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\CacheDecorator\CacheDecoratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

class AliasManager implements AliasManagerInterface, CacheDecoratorInterface {

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $storage;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * The cache key to use when caching paths.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * Whether the cache needs to be written.
   *
   * @var bool
   */
  protected $cacheNeedsWriting = FALSE;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Holds the map of path lookups per language.
   *
   * @var array
   */
  protected $lookupMap = array();

  /**
   * Holds an array of aliases for which no path was found.
   *
   * @var array
   */
  protected $noPath = array();

  /**
   * Holds the array of whitelisted path aliases.
   *
   * @var \Drupal\Core\Path\AliasWhitelistInterface
   */
  protected $whitelist;

  /**
   * Holds an array of paths that have no alias.
   *
   * @var array
   */
  protected $noAlias = array();

  /**
   * Whether preloaded path lookups has already been loaded.
   *
   * @var array
   */
  protected $langcodePreloaded = array();

  /**
   * Holds an array of previously looked up paths for the current request path.
   *
   * This will only get populated if a cache key has been set, which for example
   * happens if the alias manager is used in the context of a request.
   *
   * @var array
   */
  protected $preloadedPathLookups = FALSE;

  /**
   * Constructs an AliasManager.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $storage
   *   The alias storage service.
   * @param \Drupal\Core\Path\AliasWhitelistInterface $whitelist
   *   The whitelist implementation to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(AliasStorageInterface $storage, AliasWhitelistInterface $whitelist, LanguageManagerInterface $language_manager, CacheBackendInterface $cache) {
    $this->storage = $storage;
    $this->languageManager = $language_manager;
    $this->whitelist = $whitelist;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheKey($key) {
    // Prefix the cache key to avoid clashes with other caches.
    $this->cacheKey = 'preload-paths:' . $key;
  }

  /**
   * {@inheritdoc}
   *
   * Cache an array of the paths available on each page. We assume that aliases
   * will be needed for the majority of these paths during subsequent requests,
   * and load them in a single query during path alias lookup.
   */
  public function writeCache() {
    // Check if the paths for this page were loaded from cache in this request
    // to avoid writing to cache on every request.
    if ($this->cacheNeedsWriting && !empty($this->cacheKey)) {
      // Start with the preloaded path lookups, so that cached entries for other
      // languages will not be lost.
      $path_lookups = $this->preloadedPathLookups ?: array();
      foreach ($this->lookupMap as $langcode => $lookups) {
        $path_lookups[$langcode] = array_keys($lookups);
        if (!empty($this->noAlias[$langcode])) {
          $path_lookups[$langcode] = array_merge($path_lookups[$langcode], array_keys($this->noAlias[$langcode]));
        }
      }

      if (!empty($path_lookups)) {
        $twenty_four_hours = 60 * 60 * 24;
        $this->cache->set($this->cacheKey, $path_lookups, REQUEST_TIME + $twenty_four_hours);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // If we already know that there are no paths for this alias simply return.
    if (empty($alias) || !empty($this->noPath[$langcode][$alias])) {
      return $alias;
    }

    // Look for the alias within the cached map.
    if (isset($this->lookupMap[$langcode]) && ($path = array_search($alias, $this->lookupMap[$langcode]))) {
      return $path;
    }

    // Look for path in storage.
    if ($path = $this->storage->lookupPathSource($alias, $langcode)) {
      $this->lookupMap[$langcode][$path] = $alias;
      $this->cacheNeedsWriting = TRUE;
      return $path;
    }

    // We can't record anything into $this->lookupMap because we didn't find any
    // paths for this alias. Thus cache to $this->noPath.
    $this->noPath[$langcode][$alias] = TRUE;

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // Check the path whitelist, if the top-level part before the first /
    // is not in the list, then there is no need to do anything further,
    // it is not in the database.
    if (empty($path) || !$this->whitelist->get(strtok($path, '/'))) {
      return $path;
    }

    // During the first call to this method per language, load the expected
    // paths for the page from cache.
    if (empty($this->langcodePreloaded[$langcode])) {
      $this->langcodePreloaded[$langcode] = TRUE;
      $this->lookupMap[$langcode] = array();

      // Load the cached paths that should be used for preloading. This only
      // happens if a cache key has been set.
      if ($this->preloadedPathLookups === FALSE) {
        $this->preloadedPathLookups = array();
        if ($this->cacheKey && $cached = $this->cache->get($this->cacheKey)) {
          $this->preloadedPathLookups = $cached->data;
        }
      }

      // Load paths from cache.
      if (!empty($this->preloadedPathLookups[$langcode])) {
        $this->lookupMap[$langcode] = $this->storage->preloadPathAlias($this->preloadedPathLookups[$langcode], $langcode);
        // Keep a record of paths with no alias to avoid querying twice.
        $this->noAlias[$langcode] = array_flip(array_diff_key($this->preloadedPathLookups[$langcode], array_keys($this->lookupMap[$langcode])));
      }
    }

    // If we already know that there are no aliases for this path simply return.
    if (!empty($this->noAlias[$langcode][$path])) {
      return $path;
    }

    // If the alias has already been loaded, return it from static cache.
    if (isset($this->lookupMap[$langcode][$path])) {
      return $this->lookupMap[$langcode][$path];
    }

    // Try to load alias from storage.
    if ($alias = $this->storage->lookupPathAlias($path, $langcode)) {
      $this->lookupMap[$langcode][$path] = $alias;
      $this->cacheNeedsWriting = TRUE;
      return $alias;
    }

    // We can't record anything into $this->lookupMap because we didn't find any
    // aliases for this path. Thus cache to $this->noAlias.
    $this->noAlias[$langcode][$path] = TRUE;
    $this->cacheNeedsWriting = TRUE;
    return $path;
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::cacheClear().
   */
  public function cacheClear($source = NULL) {
    if ($source) {
      foreach (array_keys($this->lookupMap) as $lang) {
        unset($this->lookupMap[$lang][$source]);
      }
    }
    else {
      $this->lookupMap = array();
    }
    $this->noPath = array();
    $this->noAlias = array();
    $this->langcodePreloaded = array();
    $this->preloadedPathLookups = array();
    $this->cache->delete($this->cacheKey);
    $this->pathAliasWhitelistRebuild($source);
  }

  /**
   * Rebuild the path alias white list.
   *
   * @param string $path
   *   An optional path for which an alias is being inserted.
   *
   * @return
   *   An array containing a white list of path aliases.
   */
  protected function pathAliasWhitelistRebuild($path = NULL) {
    // When paths are inserted, only rebuild the whitelist if the path has a top
    // level component which is not already in the whitelist.
    if (!empty($path)) {
      if ($this->whitelist->get(strtok($path, '/'))) {
        return;
     }
    }
    $this->whitelist->clear();
  }
}
