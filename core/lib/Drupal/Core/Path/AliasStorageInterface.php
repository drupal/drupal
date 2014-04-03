<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasStorageInterface.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Language\Language;

/**
 * Provides a class for CRUD operations on path aliases.
 */
interface AliasStorageInterface {

  /**
   * Saves a path alias to the database.
   *
   * @param string $source
   *   The internal system path.
   * @param string $alias
   *   The URL alias.
   * @param string $langcode
   *   The language code of the alias.
   * @param int|null $pid
   *   Unique path alias identifier.
   *
   * @return mixed[]|bool
   *   FALSE if the path could not be saved or an associative array containing
   *   the following keys:
   *   - source (string): The internal system path.
   *   - alias (string): The URL alias.
   *   - pid (int): Unique path alias identifier.
   *   - langcode (string): The language code of the alias.
   */
  public function save($source, $alias, $langcode = Language::LANGCODE_NOT_SPECIFIED, $pid = NULL);

  /**
   * Fetches a specific URL alias from the database.
   *
   * @param $conditions
   *   An array of query conditions.
   *
   * @return mixed[]|bool
   *   FALSE if no alias was found or an associative array containing the
   *   following keys:
   *   - source (string): The internal system path.
   *   - alias (string): The URL alias.
   *   - pid (int): Unique path alias identifier.
   *   - langcode (string): The language code of the alias.
   */
  public function load($conditions);

  /**
   * Deletes a URL alias.
   *
   * @param array $conditions
   *   An array of criteria.
   */
  public function delete($conditions);

  /**
   * Pre-loads path alias information for a given list of source paths.
   *
   * @param $preloaded
   * @param $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string[]
   *   Source (keys) to alias (values) mapping.
   */
  public function preloadPathAlias($preloaded, $langcode);

  /**
   * Returns an alias of Drupal system URL.
   *
   * @param string $path
   *   The path to investigate for corresponding path aliases.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string|false
   *   A path alias, or FALSE if no path was found.
   */
  public function lookupPathAlias($path, $langcode);

  /**
   * Returns Drupal system URL of an alias.
   *
   * @param string $path
   *   The path to investigate for corresponding system URLs.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string|false
   *   A Drupal system path, or FALSE if no path was found.
   */
  public function lookupPathSource($path, $langcode);
}
