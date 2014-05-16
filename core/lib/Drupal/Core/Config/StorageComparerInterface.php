<?php

/**
 * @file
 * Contains \Drupal\Core\Config\StorageComparerInterface.
 */

namespace Drupal\Core\Config;

/**
 * Defines an interface for comparison of configuration storage objects.
 */
interface StorageComparerInterface {

  /**
   * Gets the configuration source storage.
   *
   * @param string $collection
   *   (optional) The storage collection to use. Defaults to the
   *   default collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   Storage object used to read configuration.
   */
  public function getSourceStorage($collection = StorageInterface::DEFAULT_COLLECTION);

  /**
   * Gets the configuration target storage.
   *
   * @param string $collection
   *   (optional) The storage collection to use. Defaults to the
   *   default collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   Storage object used to write configuration.
   */
  public function getTargetStorage($collection = StorageInterface::DEFAULT_COLLECTION);

  /**
   * Gets an empty changelist.
   *
   * @return array
   *   An empty changelist array.
   */
  public function getEmptyChangelist();

  /**
   * Gets the list of differences to import.
   *
   * @param string $op
   *   (optional) A change operation. Either delete, create or update. If
   *   supplied the returned list will be limited to this operation.
   * @param string $collection
   *   (optional) The collection to get the changelist for. Defaults to the
   *   default collection.
   *
   * @return array
   *   An array of config changes that are yet to be imported.
   */
  public function getChangelist($op = NULL, $collection = StorageInterface::DEFAULT_COLLECTION);

  /**
   * Recalculates the differences.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function reset();

  /**
   * Checks if there are any operations with changes to process.
   *
   * Until the changelist has been calculated this will always be FALSE.
   *
   * @see \Drupal\Core\Config\StorageComparerInterface::createChangelist().
   *
   * @return bool
   *   TRUE if there are changes to process and FALSE if not.
   */
  public function hasChanges();

  /**
   * Validates that the system.site::uuid in the source and target match.
   *
   * @return bool
   *   TRUE if identical, FALSE if not.
   */
  public function validateSiteUuid();

  /**
   * Moves a rename operation to an update.
   *
   * @param string $rename
   *   The rename name, as provided by ConfigImporter::createRenameName().
   * @param string $collection
   *   (optional) The collection where the configuration is stored. Defaults to
   *   the default collection.
   *
   * @see \Drupal\Core\Config\ConfigImporter::createRenameName()
   */
  public function moveRenameToUpdate($rename, $collection = StorageInterface::DEFAULT_COLLECTION);

  /**
   * Extracts old and new configuration names from a configuration change name.
   *
   * @param string $name
   *   The configuration change name, as provided by
   *   ConfigImporter::createRenameName().
   *
   * @return array
   *   An associative array of configuration names. The array keys are
   *   'old_name' and and 'new_name' representing the old and name configuration
   *   object names during a rename operation.
   *
   * @see \Drupal\Core\Config\StorageComparer::createRenameNames()
   */
  public function extractRenameNames($name);

  /**
   * Gets the existing collections from both the target and source storage.
   *
   * @param bool $include_default
   *   (optional) Include the default collection. Defaults to TRUE.
   *
   * @return array
   *   An array of existing collection names.
   */
  public function getAllCollectionNames($include_default = TRUE);

}
