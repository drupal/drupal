<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\RevisionableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a class for CRUD operations on workspace associations.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use
 *  \Drupal\workspaces\WorkspaceTracker instead.
 *
 * @see https://www.drupal.org/node/3551450
 */
class WorkspaceAssociation implements WorkspaceAssociationInterface, EventSubscriberInterface {

  public function __construct(
    protected $workspaceTracker,
  ) {
    if (!$this->workspaceTracker instanceof WorkspaceTrackerInterface) {
      $this->workspaceTracker = \Drupal::service('workspace.tracker');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackEntity(RevisionableInterface $entity, WorkspaceInterface $workspace) {
    $this->workspaceTracker->trackEntity($workspace->id(), $workspace);
  }

  /**
   * {@inheritdoc}
   */
  public function workspaceInsert(WorkspaceInterface $workspace) {}

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntities($workspace_id, $entity_type_id = NULL, $entity_ids = NULL) {
    return $this->workspaceTracker->getTrackedEntities($workspace_id, $entity_type_id, $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntitiesForListing($workspace_id, ?int $pager_id = NULL, int|false $limit = 50): array {
    return $this->workspaceTracker->getTrackedEntitiesForListing($workspace_id, $pager_id, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getAssociatedRevisions($workspace_id, $entity_type_id, $entity_ids = NULL) {
    return $this->workspaceTracker->getAllTrackedRevisions($workspace_id, $entity_type_id, $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAssociatedInitialRevisions(string $workspace_id, string $entity_type_id, array $entity_ids = []) {
    return $this->workspaceTracker->getTrackedInitialRevisions($workspace_id, $entity_type_id, $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity, bool $latest_revision = FALSE) {
    return $this->workspaceTracker->getEntityTrackingWorkspaceIds($entity, $latest_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssociations($workspace_id = NULL, $entity_type_id = NULL, $entity_ids = NULL, $revision_ids = NULL) {
    $this->workspaceTracker->deleteTrackedEntities($workspace_id, $entity_type_id, $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function initializeWorkspace(WorkspaceInterface $workspace) {
    $this->workspaceTracker->initializeWorkspace($workspace);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [];
  }

  /**
   * Determines the target ID field name for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The name of the workspace association target ID field.
   *
   * @internal
   */
  public static function getIdField(string $entity_type_id): string {
    return WorkspaceTracker::getIdField($entity_type_id);
  }

}
