<?php

namespace Drupal\workspaces;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Utility\Error;
use Drupal\workspaces\Event\WorkspacePostPublishEvent;
use Drupal\workspaces\Event\WorkspacePublishEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a class for CRUD operations on workspace associations.
 */
class WorkspaceTracker implements WorkspaceTrackerInterface, EventSubscriberInterface {

  /**
   * The table for the workspace association storage.
   */
  const TABLE = 'workspace_association';

  /**
   * The table for the workspace association revision storage.
   */
  const string REVISION_TABLE = 'workspace_association_revision';

  /**
   * A multidimensional array of entity IDs that are associated to a workspace.
   *
   * The first level keys are workspace IDs, the second level keys are entity
   * type IDs, and the third level array are entity IDs, keyed by revision IDs.
   *
   * @var array
   */
  protected array $associatedRevisions = [];

  /**
   * A multidimensional array of entity IDs that were created in a workspace.
   *
   * The first level keys are workspace IDs, the second level keys are entity
   * type IDs, and the third level array are entity IDs, keyed by revision IDs.
   *
   * @var array
   */
  protected array $associatedInitialRevisions = [];

  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceRepositoryInterface $workspaceRepository,
    #[Autowire(service: 'logger.channel.workspaces')]
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function trackEntity(string $workspace_id, RevisionableInterface $entity): void {
    // Determine all workspaces that might be affected by this change.
    $affected_workspaces = $this->workspaceRepository->getDescendantsAndSelf($workspace_id);

    // Get the currently tracked revision for this workspace.
    $tracked = $this->getTrackedEntities($workspace_id, $entity->getEntityTypeId(), [$entity->id()]);

    $tracked_revision_id = NULL;
    if (isset($tracked[$entity->getEntityTypeId()])) {
      $tracked_revision_id = key($tracked[$entity->getEntityTypeId()]);
    }
    $id_field = static::getIdField($entity->getEntityTypeId());

    try {
      $transaction = $this->database->startTransaction();
      // Update all affected workspaces that were tracking the current revision.
      // This means they are inheriting content and should be updated.
      if ($tracked_revision_id) {
        $this->database->update(static::TABLE)
          ->fields([
            'target_entity_revision_id' => $entity->getRevisionId(),
          ])
          ->condition('workspace', $affected_workspaces, 'IN')
          ->condition('target_entity_type_id', $entity->getEntityTypeId())
          ->condition($id_field, $entity->id())
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
            'target_entity_type_id',
            $id_field,
            'target_entity_revision_id',
          ]);
        foreach ($missing_workspaces as $missing_workspace_id) {
          $insert_query->values([
            'workspace' => $missing_workspace_id,
            'target_entity_type_id' => $entity->getEntityTypeId(),
            $id_field => $entity->id(),
            'target_entity_revision_id' => $entity->getRevisionId(),
          ]);
        }
        $insert_query->execute();
      }

      // Individual revisions are tracked in a separate table only for the
      // workspace in which they were created or updated.
      $this->database->insert(static::REVISION_TABLE)
        ->fields([
          'workspace' => $workspace_id,
          'target_entity_type_id' => $entity->getEntityTypeId(),
          $id_field => $entity->id(),
          'target_entity_revision_id' => $entity->getRevisionId(),
          'initial_revision' => (int) $entity->isDefaultRevision(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException($this->logger, $e);
      throw $e;
    }

    $this->associatedRevisions = $this->associatedInitialRevisions = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntities(string $workspace_id, ?string $entity_type_id = NULL, ?array $entity_ids = NULL): array {
    $query = $this->database->select(static::TABLE);
    $query->fields(static::TABLE, [
      'target_entity_type_id',
      'target_entity_id',
      'target_entity_id_string',
      'target_entity_revision_id',
    ]);
    $query
      ->orderBy('target_entity_revision_id', 'ASC')
      ->condition('workspace', $workspace_id);

    if ($entity_type_id) {
      $query->condition('target_entity_type_id', $entity_type_id, '=');

      if ($entity_ids) {
        $query->condition(static::getIdField($entity_type_id), $entity_ids, 'IN');
      }
    }

    $tracked_revisions = [];
    foreach ($query->execute() as $record) {
      $target_id = $record->{static::getIdField($record->target_entity_type_id)};
      $tracked_revisions[$record->target_entity_type_id][$record->target_entity_revision_id] = $target_id;
    }

    return $tracked_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntitiesForListing(string $workspace_id, ?int $pager_id = NULL, int|false $limit = 50): array {
    $query = $this->database->select(static::TABLE);

    if ($limit !== FALSE) {
      $query = $query
        ->extend(PagerSelectExtender::class)
        ->limit($limit);
      if ($pager_id) {
        $query->element($pager_id);
      }
    }

    $query->fields(static::TABLE, [
      'target_entity_type_id',
      'target_entity_id',
      'target_entity_id_string',
      'target_entity_revision_id',
    ]);
    $query
      ->orderBy('target_entity_type_id', 'ASC')
      ->orderBy('target_entity_revision_id', 'DESC')
      ->condition('workspace', $workspace_id);

    $tracked_revisions = [];
    foreach ($query->execute() as $record) {
      $target_id = $record->{static::getIdField($record->target_entity_type_id)};
      $tracked_revisions[$record->target_entity_type_id][$record->target_entity_revision_id] = $target_id;
    }

    return $tracked_revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllTrackedRevisions(string $workspace_id, string $entity_type_id, ?array $entity_ids = NULL): array {
    $this->loadAssociatedRevisions($workspace_id);

    if ($entity_ids) {
      return array_intersect($this->associatedRevisions[$workspace_id][$entity_type_id] ?? [], $entity_ids);
    }
    else {
      return $this->associatedRevisions[$workspace_id][$entity_type_id] ?? [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedInitialRevisions(string $workspace_id, string $entity_type_id, ?array $entity_ids = NULL): array {
    $this->loadAssociatedRevisions($workspace_id);

    if ($entity_ids) {
      return array_intersect($this->associatedInitialRevisions[$workspace_id][$entity_type_id] ?? [], $entity_ids);
    }
    else {
      return $this->associatedInitialRevisions[$workspace_id][$entity_type_id] ?? [];
    }
  }

  /**
   * Loads associated revision IDs and populates their static caches.
   *
   * @param string $workspace_id
   *   The workspace ID to load associations for.
   */
  protected function loadAssociatedRevisions(string $workspace_id): void {
    // Only load if the associated revisions cache has not been populated for
    // this workspace. We don't need to check the associated initial revisions
    // cache because they're always populated together.
    if (!isset($this->associatedRevisions[$workspace_id])) {
      // Initialize both caches for this workspace.
      $this->associatedRevisions[$workspace_id] = [];
      $this->associatedInitialRevisions[$workspace_id] = [];

      // Get workspace candidates for regular (non-initial) revisions.
      $workspace_tree = $this->workspaceRepository->loadTree();
      if (isset($workspace_tree[$workspace_id])) {
        $workspace_candidates = array_merge([$workspace_id], $workspace_tree[$workspace_id]['ancestors']);
      }
      else {
        $workspace_candidates = [$workspace_id];
      }

      // Query all the associated revisions.
      $query = $this->database->select(static::REVISION_TABLE);
      $query->fields(static::REVISION_TABLE, [
        'workspace',
        'target_entity_type_id',
        'target_entity_id',
        'target_entity_id_string',
        'target_entity_revision_id',
        'initial_revision',
      ]);
      $query
        ->orderBy('target_entity_type_id')
        ->orderBy('target_entity_revision_id')
        ->condition('workspace', $workspace_candidates, 'IN');

      foreach ($query->execute() as $record) {
        $target_id = $record->{static::getIdField($record->target_entity_type_id)};

        // Always add to associatedRevisions for all workspace candidates.
        $this->associatedRevisions[$workspace_id][$record->target_entity_type_id][$record->target_entity_revision_id] = $target_id;

        // Only add to associatedInitialRevisions if it's an initial revision
        // for the specific workspace.
        if ($record->workspace === $workspace_id && $record->initial_revision) {
          $this->associatedInitialRevisions[$workspace_id][$record->target_entity_type_id][$record->target_entity_revision_id] = $target_id;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity, bool $latest_revision = FALSE): array {
    $id_field = static::getIdField($entity->getEntityTypeId());
    $query = $this->database->select(static::TABLE, 'wa')
      ->fields('wa', ['workspace'])
      ->condition('[wa].[target_entity_type_id]', $entity->getEntityTypeId())
      ->condition("[wa].[$id_field]", $entity->id());

    // Use a self-join to get only the workspaces in which the latest revision
    // of the entity is tracked.
    if ($latest_revision) {
      $inner_select = $this->database->select(static::TABLE, 'wai')
        ->condition('[wai].[target_entity_type_id]', $entity->getEntityTypeId())
        ->condition("[wai].[$id_field]", $entity->id());
      $inner_select->addExpression('MAX([wai].[target_entity_revision_id])', 'max_revision_id');

      $query->join($inner_select, 'waj', '[wa].[target_entity_revision_id] = [waj].[max_revision_id]');
    }

    $result = $query->execute()->fetchCol();

    // Return early if the entity is not tracked in any workspace.
    if (empty($result)) {
      return [];
    }

    // Return workspace IDs sorted in tree order.
    $tree = $this->workspaceRepository->loadTree();
    return array_keys(array_intersect_key($tree, array_flip($result)));
  }

  /**
   * {@inheritdoc}
   */
  public function moveTrackedEntities(string $source_workspace_id, string $target_workspace_id, ?string $entity_type_id = NULL, ?array $entity_ids = NULL): void {
    // Validate input parameters.
    if ($source_workspace_id === $target_workspace_id) {
      throw new \InvalidArgumentException('Source and target workspace IDs cannot be the same.');
    }

    if ($entity_type_id === NULL && $entity_ids !== NULL) {
      throw new \InvalidArgumentException('Entity type ID must be provided when entity IDs are specified.');
    }

    // Validate that both workspaces are top-level, and don't have children.
    $workspace_tree = $this->workspaceRepository->loadTree();
    if (!isset($workspace_tree[$source_workspace_id])
      || !isset($workspace_tree[$target_workspace_id])
      || $workspace_tree[$source_workspace_id]['depth'] !== 0
      || $workspace_tree[$target_workspace_id]['depth'] !== 0
      || !empty($workspace_tree[$source_workspace_id]['descendants'])
      || !empty($workspace_tree[$target_workspace_id]['descendants'])
    ) {
      throw new \DomainException('Both the source and target must be valid top-level workspaces.');
    }

    $transaction = $this->database->startTransaction();
    try {
      // Update the workspace revision metadata field if needed.
      $this->workspaceManager->executeOutsideWorkspace(function () use ($source_workspace_id, $target_workspace_id, $entity_type_id, $entity_ids) {
        // Gather a list of revision IDs that have to be moved.
        if ($entity_type_id) {
          $affected_revision_ids[$entity_type_id] = $this->getAllTrackedRevisions($source_workspace_id, $entity_type_id, $entity_ids);
        }
        else {
          $this->loadAssociatedRevisions($source_workspace_id);
          $affected_revision_ids = $this->associatedRevisions[$source_workspace_id];
        }

        foreach ($affected_revision_ids as $entity_type_id => $entity_ids) {
          $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

          // Move the revisions if they have the revision metadata field.
          if ($field_name = $entity_type->getRevisionMetadataKey('workspace')) {
            $affected_revisions = $this->entityTypeManager->getStorage($entity_type_id)
              ->loadMultipleRevisions(array_keys($entity_ids));

            foreach ($affected_revisions as $revision) {
              $revision->{$field_name}->target_id = $target_workspace_id;
              $revision->setNewRevision(FALSE);
              $revision->setSyncing(TRUE);
              $revision->save();
            }
          }
        }
      });

      // Update the main association table.
      $update_query = $this->database->update(static::TABLE)
        ->fields(['workspace' => $target_workspace_id])
        ->condition('workspace', $source_workspace_id);

      if ($entity_type_id) {
        $update_query->condition('target_entity_type_id', $entity_type_id);
        if ($entity_ids) {
          $update_query->condition(static::getIdField($entity_type_id), $entity_ids, 'IN');
        }
      }
      $update_query->execute();

      // Update the revision association table.
      $update_revision_query = $this->database->update(static::REVISION_TABLE)
        ->fields(['workspace' => $target_workspace_id])
        ->condition('workspace', $source_workspace_id);

      if ($entity_type_id) {
        $update_revision_query->condition('target_entity_type_id', $entity_type_id);
        if ($entity_ids) {
          $update_revision_query->condition(static::getIdField($entity_type_id), $entity_ids, 'IN');
        }
      }
      $update_revision_query->execute();

      // Clear the cached associations.
      $this->associatedRevisions = $this->associatedInitialRevisions = [];
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      Error::logException($this->logger, $e);
      throw $e;
    }
    unset($transaction);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTrackedEntities(?string $workspace_id = NULL, ?string $entity_type_id = NULL, ?array $entity_ids = NULL, ?array $revision_ids = NULL): void {
    if (!$workspace_id && !$entity_type_id) {
      throw new \InvalidArgumentException('A workspace ID or an entity type ID must be provided.');
    }

    try {
      $transaction = $this->database->startTransaction();
      $this->doDeleteAssociations(static::TABLE, $workspace_id, $entity_type_id, $entity_ids, $revision_ids);
      $this->doDeleteAssociations(static::REVISION_TABLE, $workspace_id, $entity_type_id, $entity_ids, $revision_ids);
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException($this->logger, $e);
      throw $e;
    }

    $this->associatedRevisions = $this->associatedInitialRevisions = [];
  }

  /**
   * Executes a delete query for workspace associations.
   *
   * @param string $table
   *   The database table to delete from.
   * @param string|null $workspace_id
   *   The workspace ID to filter by, or NULL to not filter by workspace.
   * @param string|null $entity_type_id
   *   The entity type ID to filter by, or NULL to not filter by entity type.
   * @param array|null $entity_ids
   *   The entity IDs to filter by, or NULL to not filter by entity IDs.
   * @param array|null $revision_ids
   *   The revision IDs to filter by, or NULL to not filter by revision IDs.
   *
   * @throws \InvalidArgumentException
   *   When required parameters are missing.
   */
  protected function doDeleteAssociations(string $table, ?string $workspace_id = NULL, ?string $entity_type_id = NULL, ?array $entity_ids = NULL, ?array $revision_ids = NULL): void {
    $query = $this->database->delete($table);

    if ($workspace_id) {
      $query->condition('workspace', $workspace_id);
    }

    if ($entity_type_id) {
      if (!$entity_ids && !$revision_ids) {
        throw new \InvalidArgumentException('A list of entity IDs or revision IDs must be provided for an entity type.');
      }

      $query->condition('target_entity_type_id', $entity_type_id, '=');

      if ($entity_ids) {
        try {
          $query->condition(static::getIdField($entity_type_id), $entity_ids, 'IN');
        }
        catch (PluginNotFoundException) {
          // When an entity type is being deleted, we no longer have the ability
          // to retrieve its identifier field type, so we try both.
          $query->condition(
            $query->orConditionGroup()
              ->condition('target_entity_id', $entity_ids, 'IN')
              ->condition('target_entity_id_string', $entity_ids, 'IN')
          );
        }
      }

      if ($revision_ids) {
        $query->condition('target_entity_revision_id', $revision_ids, 'IN');
      }
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function initializeWorkspace(WorkspaceInterface $workspace): void {
    if ($parent_id = $workspace->parent->target_id) {
      $indexed_rows = $this->database->select(static::TABLE);
      $indexed_rows->addExpression(':new_id', 'workspace', [
        ':new_id' => $workspace->id(),
      ]);
      $indexed_rows->fields(static::TABLE, [
        'target_entity_type_id',
        'target_entity_id',
        'target_entity_id_string',
        'target_entity_revision_id',
      ]);
      $indexed_rows->condition('workspace', $parent_id);
      $this->database->insert(static::TABLE)->from($indexed_rows)->execute();
    }

    $this->associatedRevisions = $this->associatedInitialRevisions = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Workspace association records cleanup should happen as late as possible.
    $events[WorkspacePostPublishEvent::class][] = ['onPostPublish', -500];
    return $events;
  }

  /**
   * Triggers clean-up operations after a workspace is published.
   *
   * @param \Drupal\workspaces\Event\WorkspacePublishEvent $event
   *   The workspace publish event.
   */
  public function onPostPublish(WorkspacePublishEvent $event): void {
    // Cleanup associations for the published workspace as well as its
    // descendants.
    $affected_workspaces = $this->workspaceRepository->getDescendantsAndSelf($event->getWorkspace()->id());
    foreach ($affected_workspaces as $workspace_id) {
      $this->deleteTrackedEntities($workspace_id);
    }
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
    static $id_field_map = [];

    if (!isset($id_field_map[$entity_type_id])) {
      $id_field = \Drupal::entityTypeManager()->getDefinition($entity_type_id)
        ->getKey('id');
      $field_map = \Drupal::service('entity_field.manager')->getFieldMap()[$entity_type_id];

      $id_field_map[$entity_type_id] = $field_map[$id_field]['type'] !== 'integer'
        ? 'target_entity_id_string'
        : 'target_entity_id';
    }

    return $id_field_map[$entity_type_id];
  }

}
