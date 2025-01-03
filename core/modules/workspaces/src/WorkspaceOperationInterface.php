<?php

namespace Drupal\workspaces;

// cspell:ignore differring

/**
 * Defines an interface for workspace operations.
 *
 * Example operations are publishing, merging and syncing with a remote
 * workspace.
 *
 * @internal
 */
interface WorkspaceOperationInterface {

  /**
   * Returns the human-readable label of the source.
   *
   * @return string
   *   The source label.
   */
  public function getSourceLabel();

  /**
   * Returns the human-readable label of the target.
   *
   * @return string
   *   The target label.
   */
  public function getTargetLabel();

  /**
   * Checks if there are any conflicts between the source and the target.
   *
   * @return array
   *   Returns an array consisting of the number of conflicts between the source
   *   and the target, keyed by the conflict type constant.
   */
  public function checkConflictsOnTarget();

  /**
   * Gets the revision identifiers for items which have changed on the target.
   *
   * @return array
   *   A multidimensional array of revision identifiers, keyed by entity type
   *   IDs.
   */
  public function getDifferringRevisionIdsOnTarget();

  /**
   * Gets the revision identifiers for items which have changed on the source.
   *
   * @return array
   *   A multidimensional array of revision identifiers, keyed by entity type
   *   IDs.
   */
  public function getDifferringRevisionIdsOnSource();

  /**
   * Gets the total number of items which have changed on the target.
   *
   * This returns the aggregated changes count across all entity types.
   * For example, if two nodes and one taxonomy term have changed on the target,
   * the return value is 3.
   *
   * @return int
   *   The number of different revisions.
   */
  public function getNumberOfChangesOnTarget();

  /**
   * Gets the total number of items which have changed on the source.
   *
   * This returns the aggregated changes count across all entity types.
   * For example, if two nodes and one taxonomy term have changed on the source,
   * the return value is 3.
   *
   * @return int
   *   The number of different revisions.
   */
  public function getNumberOfChangesOnSource();

}
