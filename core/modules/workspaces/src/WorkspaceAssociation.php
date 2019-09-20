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
   * Constructs a WorkspaceAssociation object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager for querying revisions.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function trackEntity(RevisionableInterface $entity, WorkspaceInterface $workspace) {
    $this->database->merge(static::TABLE)
      ->fields([
        'target_entity_revision_id' => $entity->getRevisionId(),
      ])
      ->keys([
        'workspace' => $workspace->id(),
        'target_entity_type_id' => $entity->getEntityTypeId(),
        'target_entity_id' => $entity->id(),
      ])
      ->execute();
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
    $query->leftJoin($entity_type->getBaseTable(), 'base', "revision.$id_field = base.$id_field");

    $query
      ->fields('revision', [$revision_id_field, $id_field])
      ->condition("revision.$workspace_field", $workspace_id)
      ->where("revision.$revision_id_field > base.$revision_id_field")
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
  }

}
