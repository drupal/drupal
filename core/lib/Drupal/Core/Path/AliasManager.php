<?php

/**
 * @file
 * Contains Drupal\Core\Path\AliasManager.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;

class AliasManager implements AliasManagerInterface {

  /**
   * The database connection to use for path lookups.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @var \Drupal\Core\Utility\PathAliasWhitelist;
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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Path\AliasWhitelist $whitelist
   *   The whitelist implementation to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(Connection $connection, AliasWhitelist $whitelist, LanguageManager $language_manager) {
    $this->connection = $connection;
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
    $path_language = $path_language ?: $this->languageManager->getLanguage(Language::TYPE_URL)->id;
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
    $path_language = $path_language ?: $this->languageManager->getLanguage(Language::TYPE_URL)->id;
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
    $this->lookupMap = array();
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
        $args = array(
          ':system' => $this->preloadedPathLookups,
          ':langcode' => $langcode,
          ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
        );
        // Always get the language-specific alias before the language-neutral
        // one. For example 'de' is less than 'und' so the order needs to be
        // ASC, while 'xx-lolspeak' is more than 'und' so the order needs to
        // be DESC. We also order by pid ASC so that fetchAllKeyed() returns
        // the most recently created alias for each source. Subsequent queries
        // using fetchField() must use pid DESC to have the same effect.
        // For performance reasons, the query builder is not used here.
        if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
          // Prevent PDO from complaining about a token the query doesn't use.
          unset($args[':langcode']);
          $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode = :langcode_undetermined ORDER BY pid ASC', $args);
        }
        elseif ($langcode < Language::LANGCODE_NOT_SPECIFIED) {
          $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid ASC', $args);
        }
        else {
          $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid ASC', $args);
        }
        $this->lookupMap[$langcode] = $result->fetchAllKeyed();
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
      $args = array(
        ':source' => $path,
        ':langcode' => $langcode,
        ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
      );
      // See the queries above.
      if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
        unset($args[':langcode']);
        $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode = :langcode_undetermined ORDER BY pid DESC", $args)->fetchField();
      }
      elseif ($langcode > Language::LANGCODE_NOT_SPECIFIED) {
        $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid DESC", $args)->fetchField();
      }
      else {
        $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid DESC", $args)->fetchField();
      }
      $this->lookupMap[$langcode][$path] = $alias;
      return $alias;
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
        $args = array(
          ':alias' => $path,
          ':langcode' => $langcode,
          ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
        );
        // See the queries above.
        if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
          unset($args[':langcode']);
          $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode = :langcode_undetermined ORDER BY pid DESC", $args);
        }
        elseif ($langcode > Language::LANGCODE_NOT_SPECIFIED) {
          $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid DESC", $args);
        }
        else {
          $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid DESC", $args);
        }
        if ($source = $result->fetchField()) {
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
