<?php

namespace Drupal\path_alias;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * The default alias manager implementation.
 */
class AliasManager implements AliasManagerInterface {

  /**
   * The path alias repository.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $pathAliasRepository;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
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
  protected $lookupMap = [];

  /**
   * Holds an array of aliases for which no path was found.
   *
   * @var array
   */
  protected $noPath = [];

  /**
   * Holds the array of whitelisted path aliases.
   *
   * @var \Drupal\path_alias\AliasWhitelistInterface
   */
  protected $whitelist;

  /**
   * Holds an array of paths that have no alias.
   *
   * @var array
   */
  protected $noAlias = [];

  /**
   * Whether preloaded path lookups has already been loaded.
   *
   * @var array
   */
  protected $langcodePreloaded = [];

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
   * @param \Drupal\path_alias\AliasRepositoryInterface $alias_repository
   *   The path alias repository.
   * @param \Drupal\path_alias\AliasWhitelistInterface $whitelist
   *   The whitelist implementation to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct($alias_repository, AliasWhitelistInterface $whitelist, LanguageManagerInterface $language_manager, CacheBackendInterface $cache) {
    $this->pathAliasRepository = $alias_repository;
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
      $path_lookups = $this->preloadedPathLookups ?: [];
      foreach ($this->lookupMap as $langcode => $lookups) {
        $path_lookups[$langcode] = array_keys($lookups);
        if (!empty($this->noAlias[$langcode])) {
          $path_lookups[$langcode] = array_merge($path_lookups[$langcode], array_keys($this->noAlias[$langcode]));
        }
      }

      $twenty_four_hours = 60 * 60 * 24;
      $this->cache->set($this->cacheKey, $path_lookups, $this->getRequestTime() + $twenty_four_hours);
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
    if ($path_alias = $this->pathAliasRepository->lookupByAlias($alias, $langcode)) {
      $this->lookupMap[$langcode][$path_alias['path']] = $alias;
      return $path_alias['path'];
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
    if ($path[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Source path %s has to start with a slash.', $path));
    }
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // Check the path whitelist, if the top-level part before the first /
    // is not in the list, then there is no need to do anything further,
    // it is not in the database.
    if ($path === '/' || !$this->whitelist->get(strtok(trim($path, '/'), '/'))) {
      return $path;
    }

    // During the first call to this method per language, load the expected
    // paths for the page from cache.
    if (empty($this->langcodePreloaded[$langcode])) {
      $this->langcodePreloaded[$langcode] = TRUE;
      $this->lookupMap[$langcode] = [];

      // Load the cached paths that should be used for preloading. This only
      // happens if a cache key has been set.
      if ($this->preloadedPathLookups === FALSE) {
        $this->preloadedPathLookups = [];
        if ($this->cacheKey) {
          if ($cached = $this->cache->get($this->cacheKey)) {
            $this->preloadedPathLookups = $cached->data;
          }
          else {
            $this->cacheNeedsWriting = TRUE;
          }
        }
      }

      // Load paths from cache.
      if (!empty($this->preloadedPathLookups[$langcode])) {
        $this->lookupMap[$langcode] = $this->pathAliasRepository->preloadPathAlias($this->preloadedPathLookups[$langcode], $langcode);
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
    if ($path_alias = $this->pathAliasRepository->lookupBySystemPath($path, $langcode)) {
      $this->lookupMap[$langcode][$path] = $path_alias['alias'];
      return $path_alias['alias'];
    }

    // We can't record anything into $this->lookupMap because we didn't find any
    // aliases for this path. Thus cache to $this->noAlias.
    $this->noAlias[$langcode][$path] = TRUE;
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    // Note this method does not flush the preloaded path lookup cache. This is
    // because if a path is missing from this cache, it still results in the
    // alias being loaded correctly, only less efficiently.

    if ($source) {
      foreach (array_keys($this->lookupMap) as $lang) {
        unset($this->lookupMap[$lang][$source]);
      }
    }
    else {
      $this->lookupMap = [];
    }
    $this->noPath = [];
    $this->noAlias = [];
    $this->langcodePreloaded = [];
    $this->preloadedPathLookups = [];
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

  /**
   * Wrapper method for REQUEST_TIME constant.
   *
   * @return int
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
