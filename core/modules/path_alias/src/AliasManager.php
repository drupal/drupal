<?php

namespace Drupal\path_alias;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\FiberResumeType;

/**
 * The default alias manager implementation.
 */
class AliasManager implements AliasManagerInterface {

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
   * Holds an array of paths that have no alias.
   *
   * @var array
   */
  protected $noAlias = [];

  /**
   * Holds an array of paths that have been requested but not loaded yet.
   */
  protected array $requestedPaths = [];

  public function __construct(
    protected AliasRepositoryInterface $pathAliasRepository,
    protected AliasPrefixListInterface $pathPrefixes,
    protected LanguageManagerInterface $languageManager,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
  ) {
  }

  /**
   * Sets the cache key for the preload alias cache.
   *
   * @deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. There
   *   is no replacement.
   * @see https://www.drupal.org/node/3532412
   */
  public function setCacheKey($key) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3532412', E_USER_DEPRECATED);
  }

  /**
   * Writes to the per-page system path cache.
   *
   * @deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. There
   *   is no replacement.
   * @see https://www.drupal.org/node/3532412
   */
  public function writeCache() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3532412', E_USER_DEPRECATED);
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
      $this->lookupMap[$langcode][$path_alias['path']] = $path_alias['alias'];
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
    if (!str_starts_with($path, '/')) {
      throw new \InvalidArgumentException(sprintf('Source path %s has to start with a slash.', $path));
    }
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // Check the path prefix, if the top-level part before the first / is not in
    // the list, then there is no need to do anything further, it is not in the
    // database.
    if ($path === '/' || !$this->pathPrefixes->get(strtok(trim($path, '/'), '/'))) {
      return $path;
    }

    // If we already know that there are no aliases for this path simply return.
    if (!empty($this->noAlias[$langcode][$path])) {
      return $path;
    }
    // If the alias has already been loaded, return it from static cache.
    if (isset($this->lookupMap[$langcode][$path])) {
      return $this->lookupMap[$langcode][$path];
    }

    // Add the path to the list of requested paths.
    $this->requestedPaths[$langcode][$path] = $path;

    // If we're inside a Fiber, suspend now, this allows other fibers to collect
    // more requested paths.
    if (\Fiber::getCurrent() !== NULL) {
      \Fiber::suspend(FiberResumeType::Immediate);
    }

    // If we reach here, then either there are no other Fibers, or none of them
    // have aliases left to look up. Check the static caches in case the path
    // we're looking for was looked up in the meantime.
    if (!empty($this->noAlias[$langcode][$path])) {
      return $path;
    }

    // If the alias has already been loaded, return it from static cache.
    if (isset($this->lookupMap[$langcode][$path])) {
      return $this->lookupMap[$langcode][$path];
    }

    $this->lookupMap[$langcode] = array_merge($this->lookupMap[$langcode] ?? [], $this->pathAliasRepository->preloadPathAlias($this->requestedPaths[$langcode], $langcode));

    // Keep a record of paths with no alias to avoid querying twice.
    $this->noAlias[$langcode] = array_merge($this->noAlias[$langcode] ?? [], array_diff_key($this->requestedPaths[$langcode], $this->lookupMap[$langcode]));

    // Unset the requested paths variable now they've been loaded.
    unset($this->requestedPaths[$langcode]);

    // If we already know that there are no aliases for this path simply return.
    if (!empty($this->noAlias[$langcode][$path])) {
      return $path;
    }

    // If the alias has already been loaded, return it from static cache.
    if (isset($this->lookupMap[$langcode][$path])) {
      return $this->lookupMap[$langcode][$path];
    }
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
    $this->pathAliasPrefixListRebuild($source);
  }

  /**
   * Rebuild the path alias prefix list.
   *
   * @param string $path
   *   An optional path for which an alias is being inserted.
   */
  protected function pathAliasPrefixListRebuild($path = NULL) {
    // When paths are inserted, only rebuild the prefix list if the path has a
    // top level component which is not already in the prefix list.
    if (!empty($path)) {
      if ($this->pathPrefixes->get(strtok($path, '/'))) {
        return;
      }
    }
    $this->pathPrefixes->clear();
  }

  /**
   * Rebuild the path alias prefix list.
   *
   * @param string $path
   *   An optional path for which an alias is being inserted.
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0.
   *  Use \Drupal\path_alias\AliasManager::pathAliasPrefixListRebuild instead.
   *
   * @see https://www.drupal.org/node/3467559
   *
   * cspell:ignore whitelist
   */
  protected function pathAliasWhitelistRebuild($path = NULL) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use \Drupal\path_alias\AliasManager::pathAliasPrefixListRebuild() instead. See https://www.drupal.org/node/3467559', E_USER_DEPRECATED);
    $this->pathAliasPrefixListRebuild($path);
  }

}
