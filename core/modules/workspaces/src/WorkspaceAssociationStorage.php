<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for the Workspace association entity type.
 */
class WorkspaceAssociationStorage extends SqlContentEntityStorage implements WorkspaceAssociationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function postPush(WorkspaceInterface $workspace) {
    $this->database
      ->delete($this->entityType->getBaseTable())
      ->condition('workspace', $workspace->id())
      ->execute();
    $this->database
      ->delete($this->entityType->getRevisionTable())
      ->condition('workspace', $workspace->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntities($workspace_id, $all_revisions = FALSE) {
    $table = $all_revisions ? $this->getRevisionTable() : $this->getBaseTable();
    $query = $this->database->select($table, 'base_table');
    $query
      ->fields('base_table', ['target_entity_type_id', 'target_entity_id', 'target_entity_revision_id'])
      ->orderBy('target_entity_revision_id', 'ASC')
      ->condition('workspace', $workspace_id);

    $tracked_revisions = [];
    foreach ($query->execute() as $record) {
      $tracked_revisions[$record->target_entity_type_id][$record->target_entity_revision_id] = $record->target_entity_id;
    }

    return $tracked_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTrackingWorkspaceIds(EntityInterface $entity) {
    $query = $this->database->select($this->getBaseTable(), 'base_table');
    $query
      ->fields('base_table', ['workspace'])
      ->condition('target_entity_type_id', $entity->getEntityTypeId())
      ->condition('target_entity_id', $entity->id());

    return $query->execute()->fetchCol();
  }

}
