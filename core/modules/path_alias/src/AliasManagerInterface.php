<?php

namespace Drupal\path_alias;

/**
 * Find an alias for a path and vice versa.
 *
 * @see \Drupal\path_alias\AliasStorageInterface
 */
interface AliasManagerInterface {

  /**
   * Given the alias, return the path it represents.
   *
   * @param string $alias
   *   An alias.
   * @param string $langcode
   *   An optional language code to look up the path in.
   *
   * @return string
   *   The path represented by alias, or the alias if no path was found.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the path does not start with a slash.
   */
  public function getPathByAlias($alias, $langcode = NULL);

  /**
   * Given a path, return the alias.
   *
   * @param string $path
   *   A path.
   * @param string $langcode
   *   An optional language code to look up the path in.
   *
   * @return string
   *   An alias that represents the path, or path if no alias was found.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the path does not start with a slash.
   */
  public function getAliasByPath($path, $langcode = NULL);

  /**
   * Clears the static caches in alias manager and rebuilds the whitelist.
   *
   * @param $source
   *   Source path of the alias that is being inserted/updated. If omitted, the
   *   entire lookup static cache will be cleared and the whitelist will be
   *   rebuilt.
   */
  public function cacheClear($source = NULL);

}
