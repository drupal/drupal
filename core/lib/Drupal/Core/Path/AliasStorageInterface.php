<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasStorageInterface.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Language\LanguageInterface;

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
   *   (optional) The language code of the alias.
   * @param int|null $pid
   *   (optional) Unique path alias identifier.
   *
   * @return array|false
   *   FALSE if the path could not be saved or an associative array containing
   *   the following keys:
   *   - source (string): The internal system path with a starting slash.
   *   - alias (string): The URL alias with a starting slash.
   *   - pid (int): Unique path alias identifier.
   *   - langcode (string): The language code of the alias.
   *   - original: For updates, an array with source, alias and langcode with
   *     the previous values.
   *
   * @thrown \InvalidArgumentException
   *   Thrown when either the source or alias has not a starting slash.
   */
  public function save($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid = NULL);

  /**
   * Fetches a specific URL alias from the database.
   *
   * @param array $conditions
   *   An array of query conditions.
   *
   * @return array|false
   *   FALSE if no alias was found or an associative array containing the
   *   following keys:
   *   - source (string): The internal system path with a starting slash.
   *   - alias (string): The URL alias with a starting slash.
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
   * @param array $preloaded
   *   Paths that need preloading of aliases.
   * @param string $langcode
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

  /**
   * Checks if alias already exists.
   *
   * @param string $alias
   *   Alias to check against.
   * @param string $langcode
   *   Language of the alias.
   * @param string|null $source
   *   (optional) Path that alias is to be assigned to.
   *
   * @return bool
   *   TRUE if alias already exists and FALSE otherwise.
   */
  public function aliasExists($alias, $langcode, $source = NULL);

  /**
   * Checks if there are any aliases with language defined.
   *
   * @return bool
   *   TRUE if aliases with language exist.
   */
  public function languageAliasExists();

  /**
   * Loads aliases for admin listing.
   *
   * @param array $header
   *   Table header.
   * @param string[]|null $keys
   *   (optional) Search keys.
   *
   * @return array
   *   Array of items to be displayed on the current page.
   */
  public function getAliasesForAdminListing($header, $keys = NULL);

  /**
   * Check if any alias exists starting with $initial_substring.
   *
   * @param string $initial_substring
   *   Initial path substring to test against.
   *
   * @return bool
   *   TRUE if any alias exists, FALSE otherwise.
   */
  public function pathHasMatchingAlias($initial_substring);
}
