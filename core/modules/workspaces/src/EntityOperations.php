<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 *
 * @internal
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace manager service.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new EntityOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager')
    );
  }

  /**
   * Acts on entity IDs before they are loaded.
   *
   * @see hook_entity_preload()
   */
  public function entityPreload(array $ids, $entity_type_id) {
    $entities = [];

    // Only run if the entity type can belong to a workspace and we are in a
    // non-default workspace.
    if (!$this->workspaceManager->shouldAlterOperations($this->entityTypeManager->getDefinition($entity_type_id))) {
      return $entities;
    }

    // Get a list of revision IDs for entities that have a revision set for the
    // current active workspace. If an entity has multiple revisions set for a
    // workspace, only the one with the highest ID is returned.
    $max_revision_id = 'max_target_entity_revision_id';
    $query = $this->entityTypeManager
      ->getStorage('workspace_association')
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->aggregate('target_entity_revision_id', 'MAX', NULL, $max_revision_id)
      ->groupBy('target_entity_id')
      ->condition('target_entity_type_id', $entity_type_id)
      ->condition('workspace', $this->workspaceManager->getActiveWorkspace()->id());

    if ($ids) {
      $query->condition('target_entity_id', $ids, 'IN');
    }

    $results = $query->execute();

    if ($results) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type_id);

      // Swap out every entity which has a revision set for the current active
      // workspace.
      $swap_revision_ids = array_column($results, $max_revision_id);
      foreach ($storage->loadMultipleRevisions($swap_revision_ids) as $revision) {
        $entities[$revision->id()] = $revision;
      }
    }

    return $entities;
  }

  /**
   * Acts on an entity before it is created or updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @see hook_entity_presave()
   */
  public function entityPresave(EntityInterface $entity) {
    $entity_type = $entity->getEntityType();

    // Only run if we are not dealing with an entity type provided by the
    // Workspaces module, an internal entity type or if we are in a non-default
    // workspace.
    if ($this->shouldSkipPreOperations($entity_type)) {
      return;
    }

    // Disallow any change to an unsupported entity when we are not in the
    // default workspace.
    if (!$this->workspaceManager->isEntityTypeSupported($entity_type)) {
      throw new \RuntimeException('This entity can only be saved in the default workspace.');
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

    // When a new published entity is inserted in a non-default workspace, we
    // actually want two revisions to be saved:
    // - An unpublished default revision in the default ('live') workspace.
    // - A published pending revision in the current workspace.
    if ($entity->isNew() && $entity->isPublished()) {
      // Keep track of the publishing status in a dynamic property for
      // ::entityInsert(), then unpublish the default revision.
      // @todo Remove this dynamic property once we have an API for associating
      //   temporary data with an entity: https://www.drupal.org/node/2896474.
      $entity->_initialPublished = TRUE;
      $entity->setUnpublished();
    }
  }

  /**
   * Responds to the creation of a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_insert()
   */
  public function entityInsert(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\RevisionableInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
    // Only run if the entity type can belong to a workspace and we are in a
    // non-default workspace.
    if (!$this->workspaceManager->shouldAlterOperations($entity->getEntityType())) {
      return;
    }

    $this->trackEntity($entity);

    // When an entity is newly created in a workspace, it should be published in
    // that workspace, but not yet published on the live workspace. It is first
    // saved as unpublished for the default revision, then immediately a second
    // revision is created which is published and attached to the workspace.
    // This ensures that the published version of the entity does not 'leak'
    // into the live site. This differs from edits to existing entities where
    // there is already a valid default revision for the live workspace.
    if (isset($entity->_initialPublished)) {
      // Operate on a clone to avoid changing the entity prior to subsequent
      // hook_entity_insert() implementations.
      $pending_revision = clone $entity;
      $pending_revision->setPublished();
      $pending_revision->isDefaultRevision(FALSE);
      $pending_revision->save();
    }
  }

  /**
   * Responds to updates to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(EntityInterface $entity) {
    // Only run if the entity type can belong to a workspace and we are in a
    // non-default workspace.
    if (!$this->workspaceManager->shouldAlterOperations($entity->getEntityType())) {
      return;
    }

    // Only track new revisions.
    /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      $this->trackEntity($entity);
    }
  }

  /**
   * Acts on an entity before it is deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   *
   * @see hook_entity_predelete()
   */
  public function entityPredelete(EntityInterface $entity) {
    $entity_type = $entity->getEntityType();

    // Only run if we are not dealing with an entity type provided by the
    // Workspaces module, an internal entity type or if we are in a non-default
    // workspace.
    if ($this->shouldSkipPreOperations($entity_type)) {
      return;
    }

    // Disallow any change to an unsupported entity when we are not in the
    // default workspace.
    if (!$this->workspaceManager->isEntityTypeSupported($entity_type)) {
      throw new \RuntimeException('This entity can only be deleted in the default workspace.');
    }
  }

  /**
   * Updates or creates a WorkspaceAssociation entity for a given entity.
   *
   * If the passed-in entity can belong to a workspace and already has a
   * WorkspaceAssociation entity, then a new revision of this will be created with
   * the new information. Otherwise, a new WorkspaceAssociation entity is created to
   * store the passed-in entity's information.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity to update or create from.
   */
  protected function trackEntity(RevisionableInterface $entity) {
    // If the entity is not new, check if there's an existing
    // WorkspaceAssociation entity for it.
    $workspace_association_storage = $this->entityTypeManager->getStorage('workspace_association');
    if (!$entity->isNew()) {
      $workspace_associations = $workspace_association_storage->loadByProperties([
        'target_entity_type_id' => $entity->getEntityTypeId(),
        'target_entity_id' => $entity->id(),
      ]);

      /** @var \Drupal\Core\Entity\ContentEntityInterface $workspace_association */
      $workspace_association = reset($workspace_associations);
    }

    // If there was a WorkspaceAssociation entry create a new revision,
    // otherwise create a new entity with the type and ID.
    if (!empty($workspace_association)) {
      $workspace_association->setNewRevision(TRUE);
    }
    else {
      $workspace_association = $workspace_association_storage->create([
        'target_entity_type_id' => $entity->getEntityTypeId(),
        'target_entity_id' => $entity->id(),
      ]);
    }

    // Add the revision ID and the workspace ID.
    $workspace_association->set('target_entity_revision_id', $entity->getRevisionId());
    $workspace_association->set('workspace', $this->workspaceManager->getActiveWorkspace()->id());

    // Save without updating the tracked content entity.
    $workspace_association->save();
  }

  /**
   * Alters entity forms to disallow concurrent editing in multiple workspaces.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   *
   * @see hook_form_alter()
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    if (!$this->workspaceManager->isEntityTypeSupported($entity->getEntityType())) {
      return;
    }

    // For supported entity types, signal the fact that this form is safe to use
    // in a non-default workspace.
    // @see \Drupal\workspaces\FormOperations::validateForm()
    $form_state->set('workspace_safe', TRUE);

    // Add an entity builder to the form which marks the edited entity object as
    // a pending revision. This is needed so validation constraints like
    // \Drupal\path\Plugin\Validation\Constraint\PathAliasConstraintValidator
    // know in advance (before hook_entity_presave()) that the new revision will
    // be a pending one.
    if ($this->workspaceManager->hasActiveWorkspace()) {
      $form['#entity_builders'][] = [get_called_class(), 'entityFormEntityBuild'];
    }

    /** @var \Drupal\workspaces\WorkspaceAssociationStorageInterface $workspace_association_storage */
    $workspace_association_storage = $this->entityTypeManager->getStorage('workspace_association');
    if ($workspace_ids = $workspace_association_storage->getEntityTrackingWorkspaceIds($entity)) {
      // An entity can only be edited in one workspace.
      $workspace_id = reset($workspace_ids);

      $active_workspace = $this->workspaceManager->getActiveWorkspace();
      if ($workspace_id && (!$active_workspace || $workspace_id !== $active_workspace->id())) {
        $workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id);

        $form['#markup'] = $this->t('The content is being edited in the %label workspace.', ['%label' => $workspace->label()]);
        $form['#access'] = FALSE;
      }
    }
  }

  /**
   * Entity builder that marks all supported entities as pending revisions.
   */
  public static function entityFormEntityBuild($entity_type_id, RevisionableInterface $entity, &$form, FormStateInterface &$form_state) {
    // Set the non-default revision flag so that validation constraints are also
    // aware that a pending revision is about to be created.
    $entity->isDefaultRevision(FALSE);
  }

  /**
   * Determines whether we need to react on pre-save or pre-delete operations.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   Returns TRUE if the pre-save or pre-delete entity operations should not
   *   be altered in the current request, FALSE otherwise.
   */
  protected function shouldSkipPreOperations(EntityTypeInterface $entity_type) {
    // We should not react on pre-save and pre-delete entity operations if one
    // of the following conditions are met:
    // - the entity type is provided by the Workspaces module;
    // - the entity type is internal, which means that it should not affect
    //   anything in the default (Live) workspace;
    // - we are in the default workspace.
    return $entity_type->getProvider() === 'workspaces' || $entity_type->isInternal() || !$this->workspaceManager->hasActiveWorkspace();
  }

}
