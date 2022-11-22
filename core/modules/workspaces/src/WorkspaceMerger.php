<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default implementation of the workspace merger.
 *
 * @internal
 */
class WorkspaceMerger implements WorkspaceMergerInterface {

  /**
   * The source workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $sourceWorkspace;

  /**
   * The target workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $targetWorkspace;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new WorkspaceMerger.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   * @param \Drupal\workspaces\WorkspaceInterface $source
   *   The source workspace.
   * @param \Drupal\workspaces\WorkspaceInterface $target
   *   The target workspace.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, WorkspaceAssociationInterface $workspace_association, CacheTagsInvalidatorInterface $cache_tags_invalidator, WorkspaceInterface $source, WorkspaceInterface $target) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->workspaceAssociation = $workspace_association;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->sourceWorkspace = $source;
    $this->targetWorkspace = $target;
  }

  /**
   * {@inheritdoc}
   */
  public function merge() {
    if (!$this->sourceWorkspace->hasParent() || $this->sourceWorkspace->parent->target_id != $this->targetWorkspace->id()) {
      throw new \InvalidArgumentException('The contents of a workspace can only be merged into its parent workspace.');
    }

    if ($this->checkConflictsOnTarget()) {
      throw new WorkspaceConflictException();
    }

    try {
      $transaction = $this->database->startTransaction();
      foreach ($this->getDifferringRevisionIdsOnSource() as $entity_type_id => $revision_difference) {
        $revisions_on_source = $this->entityTypeManager->getStorage($entity_type_id)
          ->loadMultipleRevisions(array_keys($revision_difference));

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        foreach ($revisions_on_source as $revision) {
          // Track all the differing revisions from the source workspace in
          // the context of the target workspace. This will automatically
          // update all the descendants of the target workspace as well.
          $this->workspaceAssociation->trackEntity($revision, $this->targetWorkspace);
        }

        // Since we're not saving entity objects, we need to invalidate the list
        // cache tags manually.
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $this->cacheTagsInvalidator->invalidateTags($entity_type->getListCacheTags());
      }
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      watchdog_exception('workspaces', $e);
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLabel() {
    return $this->sourceWorkspace->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetLabel() {
    return $this->targetWorkspace->label();
  }

  /**
   * {@inheritdoc}
   */
  public function checkConflictsOnTarget() {
    // Nothing to do for now, we can not get to a conflicting state because an
    // entity which is being edited in a workspace can not be edited in any
    // other workspace.
  }

  /**
   * {@inheritdoc}
   */
  public function getDifferringRevisionIdsOnTarget() {
    $target_revision_difference = [];

    $tracked_entities_on_source = $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
    $tracked_entities_on_target = $this->workspaceAssociation->getTrackedEntities($this->targetWorkspace->id());
    foreach ($tracked_entities_on_target as $entity_type_id => $tracked_revisions) {
      // Now we compare the revision IDs which are tracked by the target
      // workspace to those that are tracked by the source workspace, and the
      // difference between these two arrays gives us all the entities which
      // have a different revision ID on the target.
      if (!isset($tracked_entities_on_source[$entity_type_id])) {
        $target_revision_difference[$entity_type_id] = $tracked_revisions;
      }
      elseif ($revision_difference = array_diff_key($tracked_revisions, $tracked_entities_on_source[$entity_type_id])) {
        $target_revision_difference[$entity_type_id] = $revision_difference;
      }
    }

    return $target_revision_difference;
  }

  /**
   * {@inheritdoc}
   */
  public function getDifferringRevisionIdsOnSource() {
    $source_revision_difference = [];

    $tracked_entities_on_source = $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
    $tracked_entities_on_target = $this->workspaceAssociation->getTrackedEntities($this->targetWorkspace->id());
    foreach ($tracked_entities_on_source as $entity_type_id => $tracked_revisions) {
      // Now we compare the revision IDs which are tracked by the source
      // workspace to those that are tracked by the target workspace, and the
      // difference between these two arrays gives us all the entities which
      // have a different revision ID on the source.
      if (!isset($tracked_entities_on_target[$entity_type_id])) {
        $source_revision_difference[$entity_type_id] = $tracked_revisions;
      }
      elseif ($revision_difference = array_diff_key($tracked_revisions, $tracked_entities_on_target[$entity_type_id])) {
        $source_revision_difference[$entity_type_id] = $revision_difference;
      }
    }

    return $source_revision_difference;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfChangesOnTarget() {
    $total_changes = $this->getDifferringRevisionIdsOnTarget();
    return count($total_changes, COUNT_RECURSIVE) - count($total_changes);
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfChangesOnSource() {
    $total_changes = $this->getDifferringRevisionIdsOnSource();
    return count($total_changes, COUNT_RECURSIVE) - count($total_changes);
  }

}
