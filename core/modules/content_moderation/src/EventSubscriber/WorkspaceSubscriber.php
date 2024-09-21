<?php

namespace Drupal\content_moderation\EventSubscriber;

use Drupal\content_moderation\ContentModerationState;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\Event\WorkspacePrePublishEvent;
use Drupal\workspaces\Event\WorkspacePublishEvent;
use Drupal\workspaces\WorkspaceAssociationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks whether a workspace is publishable, and prevents publishing if needed.
 */
class WorkspaceSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new WorkspaceSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface|null $workspaceAssociation
   *   The workspace association service.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?WorkspaceAssociationInterface $workspaceAssociation,
  ) {}

  /**
   * Prevents a workspace from being published based on certain conditions.
   *
   * @param \Drupal\workspaces\Event\WorkspacePublishEvent $event
   *   The workspace publish event.
   */
  public function onWorkspacePrePublish(WorkspacePublishEvent $event): void {
    // Prevent a workspace from being published if there are any pending
    // revisions in a moderation state that doesn't create default revisions.
    $workspace = $event->getWorkspace();

    $tracked_revisions = $this->workspaceAssociation->getTrackedEntities($workspace->id());

    // Gather a list of moderation states that don't create a default revision.
    $workflow_non_default_states = [];
    foreach ($this->entityTypeManager->getStorage('workflow')->loadByProperties(['type' => 'content_moderation']) as $workflow) {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
      $workflow_type = $workflow->getTypePlugin();
      // Find all workflows which are moderating entity types of the same type
      // to those that are tracked by the workspace.
      if (array_intersect($workflow_type->getEntityTypes(), array_keys($tracked_revisions))) {
        $workflow_non_default_states[$workflow->id()] = array_filter(array_map(function (ContentModerationState $state) {
          return !$state->isDefaultRevisionState() ? $state->id() : NULL;
        }, $workflow_type->getStates()));
      }
    }

    // If no tracked revisions are moderated then no status check is necessary.
    if (empty($workflow_non_default_states)) {
      return;
    }

    // Check if any revisions that are about to be published are in a
    // non-default revision moderation state.
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery()
      ->allRevisions()
      ->accessCheck(FALSE);

    $tracked_revisions_condition_group = $query->orConditionGroup();
    foreach ($tracked_revisions as $tracked_type => $tracked_revision_ids) {
      $entity_type_group = $query->andConditionGroup()
        ->condition('content_entity_type_id', $tracked_type)
        ->condition('content_entity_revision_id', array_keys($tracked_revision_ids), 'IN');

      $tracked_revisions_condition_group->condition($entity_type_group);
    }
    $query->condition($tracked_revisions_condition_group);

    $workflow_condition_group = $query->orConditionGroup();
    foreach ($workflow_non_default_states as $workflow_id => $non_default_states) {
      $group = $query->andConditionGroup()
        ->condition('workflow', $workflow_id, '=')
        ->condition('moderation_state', $non_default_states, 'IN');

      $workflow_condition_group->condition($group);
    }
    $query->condition($workflow_condition_group);

    if ($count = $query->count()->execute()) {
      $message = \Drupal::translation()->formatPlural($count, 'The @label workspace can not be published because it contains 1 item in an unpublished moderation state.', 'The @label workspace can not be published because it contains @count items in an unpublished moderation state.', [
        '@label' => $workspace->label(),
      ]);

      $event->stopPublishing();
      $event->setPublishingStoppedReason((string) $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[WorkspacePrePublishEvent::class][] = ['onWorkspacePrePublish'];
    return $events;
  }

}
