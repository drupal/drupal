<?php

declare(strict_types=1);

namespace Drupal\workspaces\Provider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\workspaces\WorkspaceTrackerInterface;

/**
 * Defines the base class for workspace providers.
 */
abstract class WorkspaceProviderBase implements WorkspaceProviderInterface {

  use StringTranslationTrait;

  /**
   * A list of entity UUIDs that were created as published in a workspace.
   */
  protected array $initialPublished = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceTrackerInterface $workspaceTracker,
    protected WorkspaceInformationInterface $workspaceInfo,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function checkAccess(WorkspaceInterface $workspace, string $operation, AccountInterface $account): AccessResultInterface {
    if ($operation === 'publish' && $workspace->hasParent()) {
      $message = $this->t('Only top-level workspaces can be published.');
      return AccessResult::forbidden((string) $message)->addCacheableDependency($workspace);
    }

    if ($account->hasPermission('administer workspaces')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // @todo Consider adding explicit "publish any|own workspace" permissions in
    //   https://www.drupal.org/project/drupal/issues/3084260.
    switch ($operation) {
      case 'update':
      case 'publish':
        $permission_operation = 'edit';
        break;

      case 'view all revisions':
        $permission_operation = 'view';
        break;

      default:
        $permission_operation = $operation;
        break;
    }

    // Check if the user has permission to access all workspaces.
    $access_result = AccessResult::allowedIfHasPermission($account, $permission_operation . ' any workspace');

    // Check if it's their own workspace, and they have permission to access
    // their own workspace.
    if ($access_result->isNeutral() && $account->isAuthenticated() && $account->id() === $workspace->getOwnerId()) {
      $access_result = AccessResult::allowedIfHasPermission($account, $permission_operation . ' own workspace')
        ->cachePerUser()
        ->addCacheableDependency($workspace);
    }

    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreate(EntityInterface $entity): void {}

  /**
   * {@inheritdoc}
   */
  public function entityPreload(array $ids, string $entity_type_id): array {
    $entities = [];

    // Get a list of revision IDs for entities that have a revision set for the
    // current active workspace. If an entity has multiple revisions set for a
    // workspace, only the one with the highest ID is returned.
    if ($tracked_entities = $this->workspaceTracker->getTrackedEntities($this->workspaceManager->getActiveWorkspace()->id(), $entity_type_id, $ids)) {
      // Bail out early if there are no tracked entities of this type.
      if (!isset($tracked_entities[$entity_type_id])) {
        return $entities;
      }

      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type_id);

      // Swap out every entity which has a revision set for the current active
      // workspace.
      foreach ($storage->loadMultipleRevisions(array_keys($tracked_entities[$entity_type_id])) as $revision) {
        $entities[$revision->id()] = $revision;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function entityPresave(EntityInterface $entity): void {
    // Disallow any change to an unsupported entity when we are not in the
    // default workspace.
    if (!$this->workspaceInfo->isEntitySupported($entity)) {
      throw new \RuntimeException(sprintf('The "%s" entity type can only be saved in the default workspace.', $entity->getEntityTypeId()));
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
    if (!$entity->isNew() && !$entity->isSyncing()) {
      // Force a new revision if the entity is not replicating.
      $entity->setNewRevision(TRUE);

      // All entities in the non-default workspace are pending revisions,
      // regardless of their publishing status. This means that when creating
      // a published pending revision in a non-default workspace it will also be
      // a published pending revision in the default workspace, however, it will
      // become the default revision only when it is replicated to the default
      // workspace.
      $entity->isDefaultRevision(FALSE);
    }

    // In ::entityFormEntityBuild() we mark the entity as a non-default revision
    // so that validation constraints can rely on $entity->isDefaultRevision()
    // always returning FALSE when an entity form is submitted in a workspace.
    // However, after validation has run, we need to revert that flag so the
    // first revision of a new entity is correctly seen by the system as the
    // default revision.
    if ($entity->isNew()) {
      $entity->isDefaultRevision(TRUE);
    }

    // Track the workspaces in which the new revision was saved.
    if (!$entity->isSyncing()) {
      $field_name = $entity->getEntityType()->getRevisionMetadataKey('workspace');
      $entity->{$field_name}->target_id = $this->workspaceManager->getActiveWorkspace()->id();
    }

    // When a new published entity is inserted in a non-default workspace, we
    // actually want two revisions to be saved:
    // - An unpublished default revision in the default ('live') workspace.
    // - A published pending revision in the current workspace.
    if ($entity->isNew() && $entity->isPublished()) {
      // Keep track of the initially published entities for ::entityInsert(),
      // then unpublish the default revision.
      $this->initialPublished[$entity->uuid()] = TRUE;
      $entity->setUnpublished();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityInsert(EntityInterface $entity): void {
    assert($entity instanceof RevisionableInterface && $entity instanceof EntityPublishedInterface);
    $this->workspaceTracker->trackEntity($this->workspaceManager->getActiveWorkspace()->id(), $entity);

    // When a published entity is created in a workspace, it should remain
    // published only in that workspace, and unpublished in the live workspace.
    // It is first saved as unpublished for the default revision, then
    // immediately a second revision is created which is published and attached
    // to the workspace. This ensures that the initial version of the entity
    // does not 'leak' into the live site. This differs from edits to existing
    // entities where there is already a valid default revision for the live
    // workspace.
    if (isset($this->initialPublished[$entity->uuid()])) {
      // Ensure that the default revision of an entity saved in a workspace is
      // unpublished.
      if ($entity->isPublished()) {
        throw new \RuntimeException('The default revision of an entity created in a workspace cannot be published.');
      }

      $entity->setPublished();
      // Ensure that the second (workspace-specific) revision is marked as new
      // early, so operations that are executed before the entity presave hook
      // (e.g. field-level presave) can take that into account.
      $entity->setNewRevision();
      $entity->isDefaultRevision(FALSE);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityUpdate(EntityInterface $entity): void {
    // Only track new revisions.
    /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      $this->workspaceTracker->trackEntity($this->workspaceManager->getActiveWorkspace()->id(), $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityTranslationInsert(EntityInterface $translation): void {
    // When a new translation is added to an existing entity, we need to add
    // that translation to the default revision as well, otherwise the new
    // translation wouldn't show up in entity queries or views which use the
    // field data table as the base table.
    $default_revision = $this->workspaceManager->executeOutsideWorkspace(function () use ($translation) {
      return $this->entityTypeManager
        ->getStorage($translation->getEntityTypeId())
        ->load($translation->id());
    });
    $langcode = $translation->language()->getId();
    if (!$default_revision->hasTranslation($langcode)) {
      $default_revision_translation = $default_revision->addTranslation($langcode, $translation->toArray());
      assert($default_revision_translation instanceof EntityPublishedInterface);
      $default_revision_translation->setUnpublished();
      $default_revision_translation->setSyncing(TRUE);
      $default_revision_translation->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityPredelete(EntityInterface $entity): void {
    // Prevent the entity from being deleted if the entity type does not have
    // support for workspaces, or if the entity has a published default
    // revision.
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if (!$this->workspaceInfo->isEntitySupported($entity) || !$this->workspaceInfo->isEntityDeletable($entity, $active_workspace)) {
      throw new \RuntimeException("This {$entity->getEntityType()->getSingularLabel()} can only be deleted in the Live workspace.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete(EntityInterface $entity): void {}

  /**
   * {@inheritdoc}
   */
  public function entityRevisionDelete(EntityInterface $entity): void {}

}
