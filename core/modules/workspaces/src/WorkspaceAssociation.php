<?php

namespace Drupal\workspaces;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Utility\Error;
use Drupal\workspaces\Event\WorkspacePostPublishEvent;
use Drupal\workspaces\Event\WorkspacePublishEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a class for CRUD operations on workspace associations.
 */
class WorkspaceAssociation implements WorkspaceAssociationInterface, EventSubscriberInterface {

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

  /**
   * Constructs a WorkspaceAssociation object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager for querying revisions.
   * @param \Drupal\workspaces\WorkspaceRepositoryInterface $workspace_repository
   *   The Workspace repository service.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, WorkspaceRepositoryInterface $workspace_repository, protected ?LoggerInterface $logger = NULL) {
    $this->database = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceRepository = $workspace_repository;
    if ($this->logger === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
      $this->logger = \Drupal::service('logger.channel.workspaces');
    }
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
  public function getTrackedEntitiesForListing($workspace_id, ?int $pager_id = NULL, int|false $limit = 50): array {
    $query = $this->database->select(static::TABLE)
      ->extend(PagerSelectExtender::class)
      ->limit($limit);
    if ($pager_id) {
      $query->element($pager_id);
    }

    $query
      ->fields(static::TABLE, ['target_entity_type_id', 'target_entity_id', 'target_entity_revision_id'])
      ->orderBy('target_entity_type_id', 'ASC')
      ->orderBy('target_entity_revision_id', 'DESC')
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
  public function getAssociatedRevisions($workspace_id, $entity_type_id, $entity_ids = NULL) {
    if (isset($this->associatedRevisions[$workspace_id][$entity_type_id])) {
      if ($entity_ids) {
        return array_intersect($this->associatedRevisions[$workspace_id][$entity_type_id], $entity_ids);
      }
      else {
        return $this->associatedRevisions[$workspace_id][$entity_type_id];
      }
    }

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

    $workspace_tree = $this->workspaceRepository->loadTree();
    if (isset($workspace_tree[$workspace_id])) {
      $workspace_candidates = array_merge([$workspace_id], $workspace_tree[$workspace_id]['ancestors']);
    }
    else {
      $workspace_candidates = [$workspace_id];
    }

    $query = $this->database->select($entity_type->getRevisionTable(), 'revision');
    $query->leftJoin($entity_type->getBaseTable(), 'base', "[revision].[$id_field] = [base].[$id_field]");

    $query
      ->fields('revision', [$revision_id_field, $id_field])
      ->condition("revision.$workspace_field", $workspace_candidates, 'IN')
      ->where("[revision].[$revision_id_field] >= [base].[$revision_id_field]")
      ->orderBy("revision.$revision_id_field", 'ASC');

    // Restrict the result to a set of entity ID's if provided.
    if ($entity_ids) {
      $query->condition("revision.$id_field", $entity_ids, 'IN');
    }

    $result = $query->execute()->fetchAllKeyed();

    // Cache the list of associated entity IDs if the full list was requested.
    if (!$entity_ids) {
      $this->associatedRevisions[$workspace_id][$entity_type_id] = $result;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssociatedInitialRevisions(string $workspace_id, string $entity_type_id, array $entity_ids = []) {
    if (isset($this->associatedInitialRevisions[$workspace_id][$entity_type_id])) {
      if ($entity_ids) {
        return array_intersect($this->associatedInitialRevisions[$workspace_id][$entity_type_id], $entity_ids);
      }
      else {
        return $this->associatedInitialRevisions[$workspace_id][$entity_type_id];
      }
    }

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

    $query = $this->database->select($entity_type->getBaseTable(), 'base');
    $query->leftJoin($entity_type->getRevisionTable(), 'revision', "[base].[$revision_id_field] = [revision].[$revision_id_field]");

    $query
      ->fields('base', [$revision_id_field, $id_field])
      ->condition("revision.$workspace_field", $workspace_id, '=')
      ->orderBy("base.$revision_id_field", 'ASC');

    // Restrict the result to a set of entity ID's if provided.
    if ($entity_ids) {
      $query->condition("base.$id_field", $entity_ids, 'IN');
    }

    $result = $query->execute()->fetchAllKeyed();

    // Cache the list of associated entity IDs if the full list was requested.
    if (!$entity_ids) {
      $this->associatedInitialRevisions[$workspace_id][$entity_type_id] = $result;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTrackingWorkspaceIds(RevisionableInterface $entity, bool $latest_revision = FALSE) {
    $query = $this->database->select(static::TABLE, 'wa')
      ->fields('wa', ['workspace'])
      ->condition('[wa].[target_entity_type_id]', $entity->getEntityTypeId())
      ->condition('[wa].[target_entity_id]', $entity->id());

    // Use a self-join to get only the workspaces in which the latest revision
    // of the entity is tracked.
    if ($latest_revision) {
      $inner_select = $this->database->select(static::TABLE, 'wai')
        ->condition('[wai].[target_entity_type_id]', $entity->getEntityTypeId())
        ->condition('[wai].[target_entity_id]', $entity->id());
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
  public function postPublish(WorkspaceInterface $workspace) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use the \Drupal\workspaces\Event\WorkspacePostPublishEvent event instead. See https://www.drupal.org/node/3242573', E_USER_DEPRECATED);
    $this->deleteAssociations($workspace->id());
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssociations($workspace_id = NULL, $entity_type_id = NULL, $entity_ids = NULL, $revision_ids = NULL) {
    if (!$workspace_id && !$entity_type_id) {
      throw new \InvalidArgumentException('A workspace ID or an entity type ID must be provided.');
    }

    $query = $this->database->delete(static::TABLE);

    if ($workspace_id) {
      $query->condition('workspace', $workspace_id);
    }

    if ($entity_type_id) {
      if (!$entity_ids && !$revision_ids) {
        throw new \InvalidArgumentException('A list of entity IDs or revision IDs must be provided for an entity type.');
      }

      $query->condition('target_entity_type_id', $entity_type_id, '=');

      if ($entity_ids) {
        $query->condition('target_entity_id', $entity_ids, 'IN');
      }

      if ($revision_ids) {
        $query->condition('target_entity_revision_id', $revision_ids, 'IN');
      }
    }

    $query->execute();

    $this->associatedRevisions = $this->associatedInitialRevisions = [];
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
    $this->deleteAssociations($event->getWorkspace()->id());
  }

}
