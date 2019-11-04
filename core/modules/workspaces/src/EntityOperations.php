<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspaces\Plugin\Validation\Constraint\EntityWorkspaceConflictConstraint;
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
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * Constructs a new EntityOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager'),
      $container->get('workspaces.association')
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
    if ($tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->workspaceManager->getActiveWorkspace()->id(), $entity_type_id, $ids)) {
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

      // Track the workspaces in which the new revision was saved.
      $field_name = $entity_type->getRevisionMetadataKey('workspace');
      $entity->{$field_name}->target_id = $this->workspaceManager->getActiveWorkspace()->id();
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

    $this->workspaceAssociation->trackEntity($entity, $this->workspaceManager->getActiveWorkspace());

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
      $this->workspaceAssociation->trackEntity($entity, $this->workspaceManager->getActiveWorkspace());
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
    /** @var \Drupal\Core\Entity\RevisionableInterface $entity */
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

    // Run the workspace conflict validation constraint when the entity form is
    // being built so we can "disable" it early and display a message to the
    // user, instead of allowing them to enter data that can never be saved.
    foreach ($entity->validate()->getEntityViolations() as $violation) {
      if ($violation->getConstraint() instanceof EntityWorkspaceConflictConstraint) {
        $form['#markup'] = $violation->getMessage();
        $form['#access'] = FALSE;
        continue;
      }
    };
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
