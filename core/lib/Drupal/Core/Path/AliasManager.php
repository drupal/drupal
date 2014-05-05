<?php

/**
 * @file
 * Contains Drupal\Core\Path\AliasManager.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;

class AliasManager implements AliasManagerInterface {

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $storage;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManager
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
   * This will only ever get populated if the alias manager is being used in
   * the context of a request.
   *
   * @var array
   */
  protected $preloadedPathLookups = array();

  /**
   * Constructs an AliasManager.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $storage
   *   The alias storage service.
   * @param \Drupal\Core\Path\AliasWhitelistInterface $whitelist
   *   The whitelist implementation to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(AliasStorageInterface $storage, AliasWhitelistInterface $whitelist, LanguageManager $language_manager) {
    $this->storage = $storage;
    $this->languageManager = $language_manager;
    $this->whitelist = $whitelist;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(Language::TYPE_URL)->id;

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
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(Language::TYPE_URL)->id;

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
      // Load paths from cache.
      if (!empty($this->preloadedPathLookups)) {
        $this->lookupMap[$langcode] = $this->storage->preloadPathAlias($this->preloadedPathLookups, $langcode);
        // Keep a record of paths with no alias to avoid querying twice.
        $this->noAlias[$langcode] = array_flip(array_diff_key($this->preloadedPathLookups, array_keys($this->lookupMap[$langcode])));
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
      return $alias;
    }

    // We can't record anything into $this->lookupMap because we didn't find any
    // aliases for this path. Thus cache to $this->noAlias.
    $this->noAlias[$langcode][$path] = TRUE;
    return $path;
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::cacheClear().
   */
  public function cacheClear($source = NULL) {
    if ($source) {
      foreach (array_keys($this->lookupMap) as $lang) {
        $this->lookupMap[$lang][$source];
      }
    }
    else {
      $this->lookupMap = array();
    }
    $this->noPath = array();
    $this->noAlias = array();
    $this->langcodePreloaded = array();
    $this->preloadedPathLookups = array();
    $this->pathAliasWhitelistRebuild($source);
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::getPathLookups().
   */
  public function getPathLookups() {
    $current = current($this->lookupMap);
    if ($current) {
      return array_keys($current);
    }
    return array();
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::preloadPathLookups().
   */
  public function preloadPathLookups(array $path_list) {
    $this->preloadedPathLookups = $path_list;
  }

  /**
   * Rebuild the path alias white list.
   *
   * @param $source
   *   An optional system path for which an alias is being inserted.
   *
   * @return
   *   An array containing a white list of path aliases.
   */
  protected function pathAliasWhitelistRebuild($source = NULL) {
    // When paths are inserted, only rebuild the whitelist if the system path
    // has a top level component which is not already in the whitelist.
    if (!empty($source)) {
      if ($this->whitelist->get(strtok($source, '/'))) {
        return;
     }
    }
    $this->whitelist->clear();
  }
}
