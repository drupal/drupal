<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Order\OrderBefore;
use Drupal\content_moderation\Hook\ContentModerationHooks;
use Drupal\workspaces\WorkspaceAssociationInterface;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\workspaces\WorkspaceRepositoryInterface;

/**
 * Defines a class for reacting to entity runtime hooks.
 */
class EntityOperations {

  /**
   * A list of entity UUIDs that were created as published in a workspace.
   */
  protected array $initialPublished = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceAssociationInterface $workspaceAssociation,
    protected WorkspaceInformationInterface $workspaceInfo,
    protected WorkspaceRepositoryInterface $workspaceRepository,
  ) {}

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $ids, string $entity_type_id): array {
    $entities = [];

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$this->workspaceInfo->isEntityTypeSupported($entity_type) || !$this->workspaceManager->hasActiveWorkspace()) {
      return $entities;
    }

    // Get a list of revision IDs for entities that have a revision set for the
    // current active workspace. If an entity has multiple revisions set for a
    // workspace, only the one with the highest ID is returned.
    if ($tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->workspaceManager->getActiveWorkspace()->id(), $entity_type_id, $ids)) {
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
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave', order: Order::First)]
  #[ReorderHook('entity_presave',
    class: ContentModerationHooks::class,
    method: 'entityPresave',
    order: new OrderBefore(['workspaces'])
  )]
  public function entityPresave(EntityInterface $entity): void {
    if ($this->shouldSkipOperations($entity)) {
      return;
    }

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
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert', order: Order::Last)]
  public function entityInsert(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'workspace') {
      $this->workspaceAssociation->workspaceInsert($entity);
      $this->workspaceRepository->resetCache();
    }

    if ($this->shouldSkipOperations($entity) || !$this->workspaceInfo->isEntitySupported($entity)) {
      return;
    }

    assert($entity instanceof RevisionableInterface && $entity instanceof EntityPublishedInterface);
    $this->workspaceAssociation->trackEntity($entity, $this->workspaceManager->getActiveWorkspace());

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
      $entity->isDefaultRevision(FALSE);
      $entity->save();
    }
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'workspace') {
      $this->workspaceRepository->resetCache();
    }

    if ($this->shouldSkipOperations($entity) || !$this->workspaceInfo->isEntitySupported($entity)) {
      return;
    }

    // Only track new revisions.
    /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      $this->workspaceAssociation->trackEntity($entity, $this->workspaceManager->getActiveWorkspace());
    }
  }

  /**
   * Implements hook_entity_translation_insert().
   */
  #[Hook('entity_translation_insert')]
  public function entityTranslationInsert(EntityInterface $translation): void {
    if ($this->shouldSkipOperations($translation)
      || !$this->workspaceInfo->isEntitySupported($translation)
      || $translation->isSyncing()
    ) {
      return;
    }

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
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'workspace') {
      $this->workspaceRepository->resetCache();
    }

    if ($this->shouldSkipOperations($entity)) {
      return;
    }

    // Prevent the entity from being deleted if the entity type does not have
    // support for workspaces, or if the entity has a published default
    // revision.
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if (!$this->workspaceInfo->isEntitySupported($entity) || !$this->workspaceInfo->isEntityDeletable($entity, $active_workspace)) {
      throw new \RuntimeException("This {$entity->getEntityType()->getSingularLabel()} can only be deleted in the Live workspace.");
    }
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    if ($this->workspaceInfo->isEntityTypeSupported($entity->getEntityType())) {
      $this->workspaceAssociation->deleteAssociations(NULL, $entity->getEntityTypeId(), [$entity->id()]);
    }
  }

  /**
   * Implements hook_entity_revision_delete().
   */
  #[Hook('entity_revision_delete')]
  public function entityRevisionDelete(EntityInterface $entity): void {
    if ($this->workspaceInfo->isEntityTypeSupported($entity->getEntityType())) {
      $this->workspaceAssociation->deleteAssociations(NULL, $entity->getEntityTypeId(), [$entity->id()], [$entity->getRevisionId()]);
    }
  }

  /**
   * Implements hook_entity_query_tag__TAG_alter() for 'latest_translated_affected_revision'.
   */
  #[Hook('entity_query_tag__latest_translated_affected_revision_alter')]
  public function entityQueryTagLatestTranslatedAffectedRevisionAlter(QueryInterface $query): void {
    $entity_type = $this->entityTypeManager->getDefinition($query->getEntityTypeId());
    if (!$this->workspaceInfo->isEntityTypeSupported($entity_type) || !$this->workspaceManager->hasActiveWorkspace()) {
      return;
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($active_workspace->id());

    if (!isset($tracked_entities[$entity_type->id()])) {
      return;
    }

    if ($revision_id = array_search($query->getMetaData('entity_id'), $tracked_entities[$entity_type->id()])) {
      $query->condition($entity_type->getKey('revision'), $revision_id, '<=');
      $conditions = $query->orConditionGroup();
      $conditions->condition($entity_type->getRevisionMetadataKey('workspace'), $active_workspace->id());
      $conditions->condition($entity_type->getRevisionMetadataKey('revision_default'), TRUE);
      $query->condition($conditions);
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * Alters entity forms to disallow concurrent editing in multiple workspaces.
   */
  #[Hook('form_alter', order: Order::First)]
  public function entityFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!$form_state->getFormObject() instanceof EntityFormInterface) {
      return;
    }

    $entity = $form_state->getFormObject()->getEntity();
    if (!$this->workspaceInfo->isEntitySupported($entity) && !$this->workspaceInfo->isEntityIgnored($entity)) {
      return;
    }

    // For supported and ignored entity types, signal the fact that this form is
    // safe to use in a workspace.
    // @see \Drupal\workspaces\Hook\FormOperations::formAlter()
    $form_state->set('workspace_safe', TRUE);

    // There is nothing more to do for ignored entity types.
    if ($this->workspaceInfo->isEntityIgnored($entity)) {
      return;
    }

    // Add an entity builder to the form which marks the edited entity object as
    // a pending revision. This is needed so validation constraints like
    // \Drupal\path\Plugin\Validation\Constraint\PathAliasConstraintValidator
    // know in advance (before hook_entity_presave()) that the new revision will
    // be a pending one.
    if ($this->workspaceManager->hasActiveWorkspace()) {
      $form['#entity_builders'][] = [static::class, 'entityFormEntityBuild'];
    }
  }

  /**
   * Entity builder that marks all supported entities as pending revisions.
   */
  public static function entityFormEntityBuild(string $entity_type_id, RevisionableInterface $entity, array &$form, FormStateInterface &$form_state): void {
    // Ensure that all entity forms are signaling that a new revision will be
    // created.
    $entity->setNewRevision(TRUE);

    // Set the non-default revision flag so that validation constraints are also
    // aware that a pending revision is about to be created.
    $entity->isDefaultRevision(FALSE);
  }

  /**
   * Determines whether we need to react on entity operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Returns TRUE if entity operations should not be altered, FALSE otherwise.
   */
  protected function shouldSkipOperations(EntityInterface $entity): bool {
    // We should not react on entity operations when the entity is ignored or
    // when we're not in a workspace context.
    return $this->workspaceInfo->isEntityIgnored($entity) || !$this->workspaceManager->hasActiveWorkspace();
  }

}
