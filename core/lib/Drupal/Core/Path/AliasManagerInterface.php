<?php

/**
 * @file
 * Contains Drupal\Core\Path\AliasManagerInterface.
 */

namespace Drupal\Core\Path;

interface AliasManagerInterface {

  /**
   * Given a path alias, return the internal path it represents.
   *
   * @param $path
   *   A Drupal path alias.
   * @param $path_language
   *   An optional language code to look up the path in.
   *
   * @return
   *   The internal path represented by the alias, or the original alias if no
   *   internal path was found.
   */
  public function getSystemPath($path, $path_language = NULL);

  /**
   * Given an internal Drupal path, return the alias set by the administrator.
   *
   * @param $path
   *   An internal Drupal path.
   *
   * @param $path_language
   *   An optional language code to look up the path in.
   *
   * @return
   *   An aliased path if one was found, or the original path if no alias was
   *   found.
   */
  public function getPathAlias($path, $path_language = NULL);

  /**
   * Returns an array of system paths that have been looked up.
   *
   * @return array
   *   An array of all system paths that have been looked up during the current
   *   request.
   */
  public function getPathLookups();

  /**
   * Preload a set of paths for bulk alias lookups.
   *
   * @param $path_list
   *   An array of system paths.
   */
  public function preloadPathLookups(array $path_list);

  /**
   * Clear internal caches in alias manager.
   *
   * @param $source
   *   Source path of the alias that is being inserted/updated. Can be ommitted
   *   if entire cache needs to be flushed.
   */
  public function cacheClear($source = NULL);
}
