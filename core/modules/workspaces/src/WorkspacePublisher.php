<?php

namespace Drupal\workspaces;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\workspaces\Event\WorkspacePostPublishEvent;
use Drupal\workspaces\Event\WorkspacePrePublishEvent;
use Psr\Log\LoggerInterface;

/**
 * Default implementation of the workspace publisher.
 *
 * @internal
 */
class WorkspacePublisher implements WorkspacePublisherInterface {

  use StringTranslationTrait;

  /**
   * The source workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $sourceWorkspace;

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
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WorkspacePublisher.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\workspaces\WorkspaceInterface $source
   *   The source workspace entity.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association, $event_dispatcher, WorkspaceInterface $source = NULL, protected ?LoggerInterface $logger = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;
    if ($event_dispatcher instanceof WorkspaceInterface) {
      @trigger_error('Calling WorkspacePublisher::__construct() without the $event_dispatcher argument is deprecated in drupal:10.1.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3242573', E_USER_DEPRECATED);
      $source = $event_dispatcher;
      $event_dispatcher = \Drupal::service('event_dispatcher');
    }
    $this->eventDispatcher = $event_dispatcher;
    $this->sourceWorkspace = $source;
    if ($this->logger === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
      $this->logger = \Drupal::service('logger.channel.workspaces');
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

    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->sourceWorkspace->id());
    $event = new WorkspacePrePublishEvent($this->sourceWorkspace, $tracked_entities);
    $this->eventDispatcher->dispatch($event);

    if ($event->isPublishingStopped()) {
      throw new WorkspacePublishException((string) $event->getPublishingStoppedReason());
    }

    try {
      $transaction = $this->database->startTransaction();
      // @todo Handle the publishing of a workspace with a batch operation in
      //   https://www.drupal.org/node/2958752.
      $this->workspaceManager->executeOutsideWorkspace(function () use ($tracked_entities) {
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

            $entity->original = $default_revisions[$entity->id()];
            $entity->save();
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
