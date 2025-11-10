<?php

namespace Drupal\workspaces;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityChangedInterface;
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

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceTrackerInterface $workspaceTracker,
    protected EventDispatcherInterface $eventDispatcher,
    protected WorkspaceInterface $sourceWorkspace,
    protected LoggerInterface $logger,
    protected ?TimeInterface $time = NULL,
  ) {
    if ($time === NULL) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $time argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3531037', E_USER_DEPRECATED);
      $this->time = \Drupal::time();
    }
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

    $tracked_entities = $this->workspaceTracker->getTrackedEntities($this->sourceWorkspace->id());
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

          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          foreach ($entity_revisions as $entity) {
            // We might be saving a lot of entities during workspace publishing,
            // so we set the original entity manually for performance.
            $entity->setOriginal(clone $entity);

            // When pushing workspace-specific revisions to the default
            // workspace (Live), we simply need to mark them as default
            // revisions.
            $entity->setSyncing(TRUE);
            $entity->isDefaultRevision(TRUE);

            // Update the changed time of the entity to be the publishing time.
            if ($entity instanceof EntityChangedInterface) {
              $entity->setChangedTime($this->time->getRequestTime());
            }

            // The default revision is not workspace-specific anymore.
            $field_name = $entity->getEntityType()->getRevisionMetadataKey('workspace');
            $entity->{$field_name}->target_id = NULL;

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

    $tracked_entities = $this->workspaceTracker->getTrackedEntities($this->sourceWorkspace->id());
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
    // Get the tracked revisions that haven't been published.
    return $this->workspaceTracker->getTrackedEntities($this->sourceWorkspace->id());
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
