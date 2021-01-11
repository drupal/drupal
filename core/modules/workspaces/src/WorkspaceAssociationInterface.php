<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\RevisionableInterface;

/**
 * Defines an interface for the workspace_association service.
 *
 * The canonical workspace association data is stored in a revision metadata
 * field on each entity revision that is tracked by a workspace.
 *
 * For the purpose of optimizing workspace-specific queries, the default
 * implementation of this interface defines a custom 'workspace_association'
 * index table which stores only the latest revisions tracked by a workspace.
 *
 * @internal
 */
interface WorkspaceAssociationInterface {

  /**
   * Updates or creates the association for a given entity and a workspace.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity to update or create from.
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace in which the entity will be tracked.
   */
  public function trackEntity(RevisionableInterface $entity, WorkspaceInterface $workspace);

  /**
   * Responds to the creation of a new workspace entity.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspaces that was inserted.
   */
  public function workspaceInsert(WorkspaceInterface $workspace);

  /**
   * Retrieves the entities tracked by a given workspace.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param string|null $entity_type_id
   *   (optional) An entity type ID to filter the results by. Defaults to NULL.
   * @param int[]|string[]|null $entity_ids
   *   (optional) An array of entity IDs to filter the results by. Defaults to
   *   NULL.
   *
   * @return array
   *   Returns a multidimensional array where the first level keys are entity
   *   type IDs and the values are an array of entity IDs keyed by revision IDs.
   */
  public function getTrackedEntities($workspace_id, $entity_type_id = NULL, $entity_ids = NULL);

  /**
   * Retrieves all content revisions tracked by a given workspace.
   *
   * Since the 'workspace_association' index table only tracks the latest
   * associated revisions, this method retrieves all the tracked revisions by
   * querying the entity type's revision table directly.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param string $entity_type_id
   *   An entity type ID to find revisions for.
   * @param int[]|string[]|null $entity_ids
   *   (optional) An array of entity IDs to filter the results by. Defaults to
   *   NULL.
   *
   * @return array
   *   Returns an array where the values are an array of entity IDs keyed by
   *   revision IDs.
   */
  public function getAssociatedRevisions($workspace_id, $entity_type_id, $entity_ids = NULL);

  /**
   * Gets a list of workspace IDs in which an entity is tracked.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   An entity object.
   *
   * @return string[]
   *   An array of workspace IDs where the given entity is tracked, or an empty
   *   array if it is not tracked anywhere.
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity);

  /**
   * Triggers clean-up operations after publishing a workspace.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   A workspace entity.
   */
  public function postPublish(WorkspaceInterface $workspace);

  /**
   * Deletes all the workspace association records for the given workspace.
   *
   * @param string $workspace_id
   *   A workspace entity ID.
   * @param string|null $entity_type_id
   *   (optional) The target entity type of the associations to delete. Defaults
   *   to NULL.
   * @param int[]|string[]|null $entity_ids
   *   (optional) The target entity IDs of the associations to delete. Defaults
   *   to NULL.
   */
  public function deleteAssociations($workspace_id, $entity_type_id = NULL, $entity_ids = NULL);

  /**
   * Initializes a workspace with all the associations of its parent.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace to be initialized.
   */
  public function initializeWorkspace(WorkspaceInterface $workspace);

}
