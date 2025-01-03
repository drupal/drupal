<?php

namespace Drupal\workspaces;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\workspaces\Event\WorkspacePostPublishEvent;
use Drupal\workspaces\Event\WorkspacePrePublishEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// cspell:ignore differring

/**
 * Default implementation of the workspace publisher.
 *
 * @internal
 */
class WorkspacePublisher implements WorkspacePublisherInterface {

  use StringTranslationTrait;

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected Connection $database, protected WorkspaceManagerInterface $workspaceManager, protected WorkspaceAssociationInterface $workspaceAssociation, protected EventDispatcherInterface $eventDispatcher, protected WorkspaceInterface $sourceWorkspace, protected LoggerInterface $logger) {
  }

  /**
   * {@inheritdoc}
   */
  public function publish() {
    if ($this->sourceWorkspace->hasParent()) {
      throw new WorkspacePublishException('Only top-level workspaces can be published.');
    }

    if ($this->checkConflictsOnTarget()) {
      throw new WorkspaceConflictException();
    }

    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
    $event = new WorkspacePrePublishEvent($this->sourceWorkspace, $tracked_entities);
    $this->eventDispatcher->dispatch($event);

    if ($event->isPublishingStopped()) {
      throw new WorkspacePublishException((string) $event->getPublishingStoppedReason());
    }

    try {
      $transaction = $this->database->startTransaction();
      $this->workspaceManager->executeOutsideWorkspace(function () use ($tracked_entities) {
        $max_execution_time = ini_get('max_execution_time');
        $step_size = Settings::get('entity_update_batch_size', 50);
        $counter = 0;

        foreach ($tracked_entities as $entity_type_id => $revision_difference) {
          $entity_revisions = $this->entityTypeManager->getStorage($entity_type_id)
            ->loadMultipleRevisions(array_keys($revision_difference));
          $default_revisions = $this->entityTypeManager->getStorage($entity_type_id)
            ->loadMultiple(array_values($revision_difference));

          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          foreach ($entity_revisions as $entity) {
            // When pushing workspace-specific revisions to the default
            // workspace (Live), we simply need to mark them as default
            // revisions.
            $entity->setSyncing(TRUE);
            $entity->isDefaultRevision(TRUE);

            // The default revision is not workspace-specific anymore.
            $field_name = $entity->getEntityType()->getRevisionMetadataKey('workspace');
            $entity->{$field_name}->target_id = NULL;

            $entity->setOriginal($default_revisions[$entity->id()]);
            $entity->save();
            $counter++;

            // Extend the execution time in order to allow processing workspaces
            // that contain a large number of items.
            if ((int) ($counter / $step_size) >= 1) {
              set_time_limit($max_execution_time);
              $counter = 0;
            }
          }
        }
      });
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException($this->logger, $e);
      throw $e;
    }

    $event = new WorkspacePostPublishEvent($this->sourceWorkspace, $tracked_entities);
    $this->eventDispatcher->dispatch($event);
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
    return $this->t('Live');
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

    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
    foreach ($tracked_entities as $entity_type_id => $tracked_revisions) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // Get the latest revision IDs for all the entities that are tracked by
      // the source workspace.
      $query = $this->entityTypeManager
        ->getStorage($entity_type_id)
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition($entity_type->getKey('id'), $tracked_revisions, 'IN')
        ->latestRevision();
      $result = $query->execute();

      // Now we compare the revision IDs which are tracked by the source
      // workspace to the latest revision IDs of those entities and the
      // difference between these two arrays gives us all the entities which
      // have been modified on the target.
      if ($revision_difference = array_diff_key($result, $tracked_revisions)) {
        $target_revision_difference[$entity_type_id] = $revision_difference;
      }
    }

    return $target_revision_difference;
  }

  /**
   * {@inheritdoc}
   */
  public function getDifferringRevisionIdsOnSource() {
    // Get the Workspace association revisions which haven't been pushed yet.
    return $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
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
