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
   * The Path CRUD service.
   *
   * @var \Drupal\Core\Path\Path
   */
  protected $path;

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
   * Holds an array of path alias for which no source was found.
   *
   * @var array
   */
  protected $noSource = array();

  /**
   * Holds the array of whitelisted path aliases.
   *
   * @var \Drupal\Core\Path\AliasWhitelistInterface
   */
  protected $whitelist;

  /**
   * Holds an array of system paths that have no aliases.
   *
   * @var array
   */
  protected $noAliases = array();

  /**
   * Whether lookupPath() has not yet been called.
   *
   * @var boolean
   */
  protected $firstLookup = TRUE;

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
   * @param \Drupal\Core\Path\Path $path
   *   The Path CRUD service.
   * @param \Drupal\Core\Path\AliasWhitelistInterface $whitelist
   *   The whitelist implementation to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(Path $path, AliasWhitelistInterface $whitelist, LanguageManager $language_manager) {
    $this->path = $path;
    $this->languageManager = $language_manager;
    $this->whitelist = $whitelist;
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::getSystemPath().
   */
  public function getSystemPath($path, $path_language = NULL) {
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $path_language = $path_language ?: $this->languageManager->getCurrentLanguage(Language::TYPE_URL)->id;
    // Lookup the path alias first.
    if (!empty($path) && $source = $this->lookupPathSource($path, $path_language)) {
      $path = $source;
    }

    return $path;
  }

  /**
   * Implements \Drupal\Core\Path\AliasManagerInterface::getPathAlias().
   */
  public function getPathAlias($path, $path_language = NULL) {
    // If no language is explicitly specified we default to the current URL
    // language. If we used a language different from the one conveyed by the
    // requested URL, we might end up being unable to check if there is a path
    // alias matching the URL path.
    $path_language = $path_language ?: $this->languageManager->getCurrentLanguage(Language::TYPE_URL)->id;
    $result = $path;
    if (!empty($path) && $alias = $this->lookupPathAlias($path, $path_language)) {
      $result = $alias;
    }
    return $result;
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
    $this->noSource = array();
    $this->no_aliases = array();
    $this->firstCall = TRUE;
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
   * Given a Drupal system URL return one of its aliases if such a one exists.
   * Otherwise, return FALSE.
   *
   * @param $path
   *   The path to investigate for corresponding aliases.
   * @param $langcode
   *   Optional language code to search the path with. Defaults to the page language.
   *   If there's no path defined for that language it will search paths without
   *   language.
   *
   * @return
   *   An aliased path, or FALSE if no path was found.
   */
  protected function lookupPathAlias($path, $langcode) {
    // During the first call to this method per language, load the expected
    // system paths for the page from cache.
    if (!empty($this->firstLookup)) {
      $this->firstLookup = FALSE;
      $this->lookupMap[$langcode] = array();
      // Load system paths from cache.
      if (!empty($this->preloadedPathLookups)) {
        // Now fetch the aliases corresponding to these system paths.
        $this->lookupMap[$langcode] = $this->path->preloadPathAlias($this->preloadedPathLookups, $langcode);
        // Keep a record of paths with no alias to avoid querying twice.
        $this->noAliases[$langcode] = array_flip(array_diff_key($this->preloadedPathLookups, array_keys($this->lookupMap[$langcode])));
      }
    }
    // If the alias has already been loaded, return it.
    if (isset($this->lookupMap[$langcode][$path])) {
      return $this->lookupMap[$langcode][$path];
    }
    // Check the path whitelist, if the top-level part before the first /
    // is not in the list, then there is no need to do anything further,
    // it is not in the database.
    elseif (!$this->whitelist->get(strtok($path, '/'))) {
      return FALSE;
    }
    // For system paths which were not cached, query aliases individually.
    elseif (!isset($this->noAliases[$langcode][$path])) {
      $this->lookupMap[$langcode][$path] = $this->path->lookupPathAlias($path, $langcode);
      return $this->lookupMap[$langcode][$path];
    }
    return FALSE;
  }

  /**
   * Given an alias, return its Drupal system URL if one exists. Otherwise,
   * return FALSE.
   *
   * @param $path
   *   The path to investigate for corresponding system URLs.
   * @param $langcode
   *   Optional language code to search the path with. Defaults to the page language.
   *   If there's no path defined for that language it will search paths without
   *   language.
   *
   * @return
   *   A Drupal system path, or FALSE if no path was found.
   */
  protected function lookupPathSource($path, $langcode) {
    if ($this->whitelist && !isset($this->noSource[$langcode][$path])) {
      // Look for the value $path within the cached $map
      $source = isset($this->lookupMap[$langcode]) ? array_search($path, $this->lookupMap[$langcode]) : FALSE;
      if (!$source) {
        if ($source = $this->path->lookupPathSource($path, $langcode)) {
          $this->lookupMap[$langcode][$source] = $path;
        }
        else {
          // We can't record anything into $map because we do not have a valid
          // index and there is no need because we have not learned anything
          // about any Drupal path. Thus cache to $no_source.
          $this->noSource[$langcode][$path] = TRUE;
        }
      }
      return $source;
    }
    return FALSE;
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
