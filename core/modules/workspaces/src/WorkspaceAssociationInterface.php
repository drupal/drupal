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
   * Retrieves a paged list of entities tracked by a given workspace.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param int|null $pager_id
   *   (optional) A pager ID. Defaults to NULL.
   * @param int|false $limit
   *   (optional) An integer specifying the number of elements per page. If
   *   passed a false value (FALSE, 0, NULL), the pager is disabled. Defaults to
   *   50.
   *
   * @return array
   *   Returns a multidimensional array where the first level keys are entity
   *   type IDs and the values are an array of entity IDs keyed by revision IDs.
   */
  public function getTrackedEntitiesForListing($workspace_id, ?int $pager_id = NULL, int|false $limit = 50): array;

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
   * Retrieves all content revisions that were created in a given workspace.
   *
   * @param string $workspace_id
   *   The ID of the workspace.
   * @param string $entity_type_id
   *   An entity type ID to find revisions for.
   * @param int[]|string[] $entity_ids
   *   (optional) An array of entity IDs to filter the results by. Defaults to
   *   an empty array.
   *
   * @return array
   *   Returns an array where the values are an array of entity IDs keyed by
   *   revision IDs.
   */
  public function getAssociatedInitialRevisions(string $workspace_id, string $entity_type_id, array $entity_ids = []);

  /**
   * Gets a list of workspace IDs in which an entity is tracked.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   An entity object.
   * @param bool $latest_revision
   *   (optional) Whether to return only the workspaces in which the latest
   *   revision of the entity is tracked. Defaults to FALSE.
   *
   * @return string[]
   *   An array of workspace IDs where the given entity is tracked, or an empty
   *   array if it is not tracked anywhere.
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity, bool $latest_revision = FALSE);

  /**
   * Triggers clean-up operations after publishing a workspace.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   A workspace entity.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use the
   *   \Drupal\workspaces\Event\WorkspacePostPublishEvent event instead.
   *
   * @see https://www.drupal.org/node/3242573
   */
  public function postPublish(WorkspaceInterface $workspace);

  /**
   * Deletes all the workspace association records for the given workspace.
   *
   * @param string|null $workspace_id
   *   (optional) A workspace entity ID. Defaults to NULL.
   * @param string|null $entity_type_id
   *   (optional) The target entity type of the associations to delete. Defaults
   *   to NULL.
   * @param int[]|string[]|null $entity_ids
   *   (optional) The target entity IDs of the associations to delete. Defaults
   *   to NULL.
   * @param int[]|string[]|null $revision_ids
   *   (optional) The target entity revision IDs of the associations to delete.
   *   Defaults to NULL.
   *
   * @throws \InvalidArgumentException
   *   If neither $workspace_id nor $entity_type_id arguments were provided.
   */
  public function deleteAssociations($workspace_id = NULL, $entity_type_id = NULL, $entity_ids = NULL, $revision_ids = NULL);

  /**
   * Initializes a workspace with all the associations of its parent.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace to be initialized.
   */
  public function initializeWorkspace(WorkspaceInterface $workspace);

}
