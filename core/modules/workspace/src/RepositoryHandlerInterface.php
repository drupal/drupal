<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * RepositoryHandler plugins handle content replication.
 *
 * The replication will use data from the target repository handler plugin to
 * merge the content between the source and the target. For example an internal
 * replication might just need the workspace IDs, but a contrib module
 * performing an external replication may need hostname, port, username,
 * password etc.
 */
interface RepositoryHandlerInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Indicate that an item has been updated both on the source and the target.
   *
   * @var int
   */
  const CONFLICT_UPDATE_ON_CHANGE = 1;

  /**
   * Indicate that an item updated on the source has been deleted on the target.
   *
   * @var int
   */
  const CONFLICT_UPDATE_ON_DELETE = 2;

  /**
   * Indicate that an item deleted on the source has been changed on the target.
   *
   * @var int
   */
  const CONFLICT_DELETE_ON_CHANGE = 3;

  /**
   * Returns the label of the repository handler.
   *
   * This is used as a form label where a user selects the replication target.
   *
   * @return string
   *   The label text, which could be a plain string or an object that can be
   *   cast to a string.
   */
  public function getLabel();

  /**
   * Returns the repository handler plugin description.
   *
   * @return string
   *   The description text, which could be a plain string or an object that can
   *   be cast to a string.
   */
  public function getDescription();

  /**
   * Pushes content from a source repository to a target repository.
   */
  public function push();

  /**
   * Pulls content from a target repository to a source repository.
   */
  public function pull();

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
   *   A multidimensional array of revision identifiers, either the revision ID
   *   or the revision UUID, keyed by entity type IDs.
   *
   * @todo Update the return values to be only UUIDs and revision UUIDs in
   *   https://www.drupal.org/node/2958752
   */
  public function getDifferringRevisionIdsOnTarget();

  /**
   * Gets the revision identifiers for items which have changed on the source.
   *
   * @return array
   *   A multidimensional array of revision identifiers, either the revision ID
   *   or the revision UUID, keyed by entity type IDs.
   *
   * @todo Update the return values to be only UUIDs and revision UUIDs in
   *   https://www.drupal.org/node/2958752
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
   *   The number of differing revisions.
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
   *   The number of differing revisions.
   */
  public function getNumberOfChangesOnSource();

}
