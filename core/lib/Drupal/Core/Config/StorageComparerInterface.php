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
   * @return \Drupal\Core\Config\StorageInterface
   *   Storage object used to read configuration.
   */
  public function getSourceStorage();

  /**
   * Gets the configuration target storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   Storage object used to write configuration.
   */
  public function getTargetStorage();

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
   *
   * @return array
   *   An array of config changes that are yet to be imported.
   */
  public function getChangelist($op = NULL);

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
   * @param array $ops
   *   The operations to check for changes. Defaults to all operations, i.e.
   *   array('delete', 'create', 'update').
   *
   * @return bool
   *   TRUE if there are changes to process and FALSE if not.
   */
  public function hasChanges($ops = array('delete', 'create', 'update'));

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
   *
   * @see \Drupal\Core\Config\ConfigImporter::createRenameName()
   */
  public function moveRenameToUpdate($rename);

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

}
