<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for workspace association entity storage classes.
 */
interface WorkspaceAssociationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Triggers clean-up operations after pushing.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   A workspace entity.
   */
  public function postPush(WorkspaceInterface $workspace);

  /**
   * Retrieves the content revisions tracked by a given workspace.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param bool $all_revisions
   *   (optional) Whether to return all the tracked revisions for each entity or
   *   just the latest tracked revision. Defaults to FALSE.
   *
   * @return array
   *   Returns a multidimensional array where the first level keys are entity
   *   type IDs and the values are an array of entity IDs keyed by revision IDs.
   */
  public function getTrackedEntities($workspace_id, $all_revisions = FALSE);

  /**
   * Gets a list of workspace IDs in which an entity is tracked.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return string[]
   *   An array of workspace IDs where the given entity is tracked, or an empty
   *   array if it is not tracked anywhere.
   */
  public function getEntityTrackingWorkspaceIds(EntityInterface $entity);

}
