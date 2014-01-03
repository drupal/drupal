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
   *   Storage controller object used to read configuration.
   */
  public function getSourceStorage();

  /**
   * Gets the configuration target storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   Storage controller object used to write configuration.
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
   * Adds changes to the changelist.
   *
   * @param string $op
   *   The change operation performed. Either delete, create or update.
   * @param array $changes
   *   Array of changes to add the changelist.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function addChangeList($op, array $changes);

  /**
   * Add differences between source and target configuration storage to changelist.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function createChangelist();

  /**
   * Creates the delete changelist.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function addChangelistDelete();

  /**
   * Creates the create changelist.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function addChangelistCreate();

  /**
   * Creates the update changelist.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   An object which implements the StorageComparerInterface.
   */
  public function addChangelistUpdate();

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

}
