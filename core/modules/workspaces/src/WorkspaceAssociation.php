<?php

namespace Drupal\workspaces;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Provides a class for CRUD operations on workspace associations.
 */
class WorkspaceAssociation implements WorkspaceAssociationInterface {

  /**
   * The table for the workspace association storage.
   */
  const TABLE = 'workspace_association';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace repository service.
   *
   * @var \Drupal\workspaces\WorkspaceRepositoryInterface
   */
  protected $workspaceRepository;

  /**
   * A multidimensional array of entity IDs that can be deleted in a workspace.
   *
   * The first level keys are workspace IDs, the second level keys are entity
   * type IDs, and the third level array contains entity IDs.
   *
   * @var array
   */
  protected $deletableEntityIds = [];

  /**
   * Constructs a WorkspaceAssociation object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager for querying revisions.
   * @param \Drupal\workspaces\WorkspaceRepositoryInterface $workspace_repository
   *   The Workspace repository service.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, WorkspaceRepositoryInterface $workspace_repository) {
    $this->database = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceRepository = $workspace_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function trackEntity(RevisionableInterface $entity, WorkspaceInterface $workspace) {
    // Determine all workspaces that might be affected by this change.
    $affected_workspaces = $this->workspaceRepository->getDescendantsAndSelf($workspace->id());

    // Get the currently tracked revision for this workspace.
    $tracked = $this->getTrackedEntities($workspace->id(), $entity->getEntityTypeId(), [$entity->id()]);

    $tracked_revision_id = NULL;
    if (isset($tracked[$entity->getEntityTypeId()])) {
      $tracked_revision_id = key($tracked[$entity->getEntityTypeId()]);
    }

    $transaction = $this->database->startTransaction();
    try {
      // Update all affected workspaces that were tracking the current revision.
      // This means they are inheriting content and should be updated.
      if ($tracked_revision_id) {
        $this->database->update(static::TABLE)
          ->fields([
            'target_entity_revision_id' => $entity->getRevisionId(),
          ])
          ->condition('workspace', $affected_workspaces, 'IN')
          ->condition('target_entity_type_id', $entity->getEntityTypeId())
          ->condition('target_entity_id', $entity->id())
          // Only update descendant workspaces if they have the same initial
          // revision, which means they are currently inheriting content.
          ->condition('target_entity_revision_id', $tracked_revision_id)
          ->execute();
      }

      // Insert a new index entry for each workspace that is not tracking this
      // entity yet.
      $missing_workspaces = array_diff($affected_workspaces, $this->getEntityTrackingWorkspaceIds($entity));
      if ($missing_workspaces) {
        $insert_query = $this->database->insert(static::TABLE)
          ->fields([
            'workspace',
            'target_entity_revision_id',
            'target_entity_type_id',
            'target_entity_id',
          ]);
        foreach ($missing_workspaces as $workspace_id) {
          $insert_query->values([
            'workspace' => $workspace_id,
            'target_entity_type_id' => $entity->getEntityTypeId(),
            'target_entity_id' => $entity->id(),
            'target_entity_revision_id' => $entity->getRevisionId(),
          ]);
        }
        $insert_query->execute();
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      watchdog_exception('workspaces', $e);
      throw $e;
    }

    $this->deletableEntityIds = [];
  }

  /**
   * {@inheritdoc}
   */
  public function workspaceInsert(WorkspaceInterface $workspace) {
    // When a new workspace has been saved, we need to copy all the associations
    // of its parent.
    if ($workspace->hasParent()) {
      $this->initializeWorkspace($workspace);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntities($workspace_id, $entity_type_id = NULL, $entity_ids = NULL) {
    $query = $this->database->select(static::TABLE);
    $query
      ->fields(static::TABLE, ['target_entity_type_id', 'target_entity_id', 'target_entity_revision_id'])
      ->orderBy('target_entity_revision_id', 'ASC')
      ->condition('workspace', $workspace_id);

    if ($entity_type_id) {
      $query->condition('target_entity_type_id', $entity_type_id, '=');

      if ($entity_ids) {
        $query->condition('target_entity_id', $entity_ids, 'IN');
      }
    }

    $tracked_revisions = [];
    foreach ($query->execute() as $record) {
      $tracked_revisions[$record->target_entity_type_id][$record->target_entity_revision_id] = $record->target_entity_id;
    }

    return $tracked_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssociatedRevisions($workspace_id, $entity_type_id, $entity_ids = NULL) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // If the entity type is not using core's default entity storage, we can't
    // assume the table mapping layout so we have to return only the latest
    // tracked revisions.
    if (!$storage instanceof SqlContentEntityStorage) {
      return $this->getTrackedEntities($workspace_id, $entity_type_id, $entity_ids)[$entity_type_id];
    }

    $entity_type = $storage->getEntityType();
    $table_mapping = $storage->getTableMapping();
    $workspace_field = $table_mapping->getColumnNames($entity_type->get('revision_metadata_keys')['workspace'])['target_id'];
    $id_field = $table_mapping->getColumnNames($entity_type->getKey('id'))['value'];
    $revision_id_field = $table_mapping->getColumnNames($entity_type->getKey('revision'))['value'];

    $query = $this->database->select($entity_type->getRevisionTable(), 'revision');
    $query->leftJoin($entity_type->getBaseTable(), 'base', "[revision].[$id_field] = [base].[$id_field]");

    $query
      ->fields('revision', [$revision_id_field, $id_field])
      ->condition("revision.$workspace_field", $workspace_id)
      ->where("[revision].[$revision_id_field] >= [base].[$revision_id_field]")
      ->orderBy("revision.$revision_id_field", 'ASC');

    // Restrict the result to a set of entity ID's if provided.
    if ($entity_ids) {
      $query->condition("revision.$id_field", $entity_ids, 'IN');
    }

    return $query->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity) {
    $query = $this->database->select(static::TABLE)
      ->fields(static::TABLE, ['workspace'])
      ->condition('target_entity_type_id', $entity->getEntityTypeId())
      ->condition('target_entity_id', $entity->id());

    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function postPublish(WorkspaceInterface $workspace) {
    $this->deleteAssociations($workspace->id());
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssociations($workspace_id, $entity_type_id = NULL, $entity_ids = NULL) {
    $query = $this->database->delete(static::TABLE)
      ->condition('workspace', $workspace_id);

    if ($entity_type_id) {
      $query->condition('target_entity_type_id', $entity_type_id, '=');

      if ($entity_ids) {
        $query->condition('target_entity_id', $entity_ids, 'IN');
      }
    }

    $query->execute();
    $this->deletableEntityIds = [];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeWorkspace(WorkspaceInterface $workspace) {
    if ($parent_id = $workspace->parent->target_id) {
      $indexed_rows = $this->database->select(static::TABLE);
      $indexed_rows->addExpression(':new_id', 'workspace', [
        ':new_id' => $workspace->id(),
      ]);
      $indexed_rows->fields(static::TABLE, [
        'target_entity_type_id',
        'target_entity_id',
        'target_entity_revision_id',
      ]);
      $indexed_rows->condition('workspace', $parent_id);
      $this->database->insert(static::TABLE)->from($indexed_rows)->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityDeletable(RevisionableInterface $entity, WorkspaceInterface $workspace) {
    // Initialize all the deletable entity IDs upfront for performance reasons.
    // This is needed for admin listing pages, which usually do this check for
    // at least 50 entities.
    if (!isset($this->deletableEntityIds[$workspace->id()][$entity->getEntityTypeId()])) {
      // First, get all the entity IDs for the entities where the default
      // revision is unpublished.
      $unpublished_default_revisions = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition($entity->getEntityType()->getKey('published'), 0)
        ->addMetaData('skip_workspaces_alter', TRUE)
        ->execute();

      // If there are no unpublished default revisions, the entity can not be
      // deleted.
      if (!$unpublished_default_revisions) {
        $this->deletableEntityIds[$workspace->id()][$entity->getEntityTypeId()] = [];
        return FALSE;
      }

      // Next, get the revisions count for all the entity IDs found above.
      $id_key = $entity->getEntityType()->getKey('id');
      $revision_id_key = $entity->getEntityType()->getKey('revision');
      $revision_count_key = 'revision_count';
      $revisions_count = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
        ->getAggregateQuery()
        ->allRevisions()
        ->accessCheck(FALSE)
        ->condition($id_key, $unpublished_default_revisions, 'IN')
        ->aggregate($revision_id_key, 'COUNT', NULL, $revision_count_key)
        ->groupBy($id_key)
        ->execute();

      // Now get all the revisions associated with the given workspace.
      $associated_revisions = $this->getAssociatedRevisions($workspace->id(), $entity->getEntityTypeId(), $unpublished_default_revisions);

      // Create an array containing the number of workspace-specific revisions
      // for each associated entity, keyed by entity ID.
      $associated_revisions_count = array_count_values($associated_revisions) + array_fill_keys($unpublished_default_revisions, 0);

      // Finally, compare the number of workspace-specific revisions with the
      // total number of revisions for each entity. If they match, it means that
      // the entity was created and edited in a single workspace, and therefore
      // can be deleted.
      foreach ($revisions_count as $revision_count_value) {
        if ((int) $revision_count_value[$revision_count_key] === $associated_revisions_count[$revision_count_value[$id_key]]) {
          $this->deletableEntityIds[$workspace->id()][$entity->getEntityTypeId()][$revision_count_value[$id_key]] = $revision_count_value[$id_key];
        }
      }
    }

    return isset($this->deletableEntityIds[$workspace->id()][$entity->getEntityTypeId()][$entity->id()]);
  }

}
