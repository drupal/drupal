<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
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
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The workspace information service.
   *
   * @var \Drupal\workspaces\WorkspaceInformationInterface
   */
  protected $workspaceInfo;

  /**
   * Constructs a new EntityOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   * @param \Drupal\workspaces\WorkspaceInformationInterface $workspace_information
   *   The workspace information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association, WorkspaceInformationInterface $workspace_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;
    $this->workspaceInfo = $workspace_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager'),
      $container->get('workspaces.association'),
      $container->get('workspaces.information')
    );
  }

  /**
   * Acts on entity IDs before they are loaded.
   *
   * @see hook_entity_preload()
   */
  public function entityPreload(array $ids, $entity_type_id) {
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
   * Acts on an entity before it is created or updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @see hook_entity_presave()
   */
  public function entityPresave(EntityInterface $entity) {
    if ($this->shouldSkipOperations($entity)) {
      return;
    }

    // Disallow any change to an unsupported entity when we are not in the
    // default workspace.
    if (!$this->workspaceInfo->isEntitySupported($entity)) {
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
    if ($this->shouldSkipOperations($entity) || !$this->workspaceInfo->isEntitySupported($entity)) {
      return;
    }

    $this->workspaceAssociation->trackEntity($entity, $this->workspaceManager->getActiveWorkspace());

    // When a published entity is created in a workspace, it should remain
    // published only in that workspace, and unpublished in the live workspace.
    // It is first saved as unpublished for the default revision, then
    // immediately a second revision is created which is published and attached
    // to the workspace. This ensures that the initial version of the entity
    // does not 'leak' into the live site. This differs from edits to existing
    // entities where there is already a valid default revision for the live
    // workspace.
    if (isset($entity->_initialPublished)) {
      $entity->setPublished();
      $entity->isDefaultRevision(FALSE);
      $entity->save();
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
   * Acts after an entity translation has been added.
   *
   * @param \Drupal\Core\Entity\EntityInterface $translation
   *   The translation that was added.
   *
   * @see hook_entity_translation_insert()
   */
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
    $this->workspaceManager->executeOutsideWorkspace(function () use ($translation) {
      $storage = $this->entityTypeManager->getStorage($translation->getEntityTypeId());
      $default_revision = $storage->load($translation->id());

      $langcode = $translation->language()->getId();
      if (!$default_revision->hasTranslation($langcode)) {
        $default_revision_translation = $default_revision->addTranslation($langcode, $translation->toArray());
        $default_revision_translation->setUnpublished();
        $default_revision_translation->setSyncing(TRUE);
        $default_revision_translation->save();
      }
    });
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
    $entity = $form_state->getFormObject()->getEntity();
    if (!$this->workspaceInfo->isEntitySupported($entity) && !$this->workspaceInfo->isEntityIgnored($entity)) {
      return;
    }

    // For supported and ignored entity types, signal the fact that this form is
    // safe to use in a workspace.
    // @see \Drupal\workspaces\FormOperations::validateForm()
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
  public static function entityFormEntityBuild($entity_type_id, RevisionableInterface $entity, &$form, FormStateInterface &$form_state) {
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
  protected function shouldSkipOperations(EntityInterface $entity) {
    // We should not react on entity operations when the entity is ignored or
    // when we're not in a workspace context.
    return $this->workspaceInfo->isEntityIgnored($entity) || !$this->workspaceManager->hasActiveWorkspace();
  }

}
