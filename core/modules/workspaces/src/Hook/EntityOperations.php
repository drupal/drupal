<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Order\OrderBefore;
use Drupal\content_moderation\Hook\ContentModerationHooks;
use Drupal\workspaces\WorkspaceTrackerInterface;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Drupal\workspaces\WorkspaceRepositoryInterface;

/**
 * Defines a class for reacting to entity runtime hooks.
 */
class EntityOperations {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceTrackerInterface $workspaceTracker,
    protected WorkspaceInformationInterface $workspaceInfo,
    protected WorkspaceRepositoryInterface $workspaceRepository,
  ) {}

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $ids, string $entity_type_id): array {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$this->workspaceInfo->isEntityTypeSupported($entity_type)) {
      return [];
    }

    return $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityPreload($ids, $entity_type_id) ?? [];
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
    if ($this->workspaceInfo->isEntityIgnored($entity)) {
      return;
    }

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityPresave($entity);
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert', order: Order::Last)]
  public function entityInsert(EntityInterface $entity): void {
    if ($this->workspaceInfo->isEntityIgnored($entity) || !$this->workspaceInfo->isEntitySupported($entity)) {
      return;
    }

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityInsert($entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    if ($this->workspaceInfo->isEntityIgnored($entity) || !$this->workspaceInfo->isEntitySupported($entity)) {
      return;
    }

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityUpdate($entity);
  }

  /**
   * Implements hook_entity_translation_insert().
   */
  #[Hook('entity_translation_insert')]
  public function entityTranslationInsert(EntityInterface $translation): void {
    if ($this->workspaceInfo->isEntityIgnored($translation)
      || !$this->workspaceInfo->isEntitySupported($translation)
      || $translation->isSyncing()
    ) {
      return;
    }

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityTranslationInsert($translation);
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'workspace') {
      $this->workspaceRepository->resetCache();
    }

    if ($this->workspaceInfo->isEntityIgnored($entity)) {
      return;
    }

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityPredelete($entity);
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    if (!$this->workspaceInfo->isEntityTypeSupported($entity->getEntityType())) {
      return;
    }

    $this->workspaceTracker->deleteTrackedEntities(NULL, $entity->getEntityTypeId(), [$entity->id()]);

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityDelete($entity);
  }

  /**
   * Implements hook_entity_revision_delete().
   */
  #[Hook('entity_revision_delete')]
  public function entityRevisionDelete(EntityInterface $entity): void {
    if (!$this->workspaceInfo->isEntityTypeSupported($entity->getEntityType())) {
      return;
    }

    $this->workspaceTracker->deleteTrackedEntities(NULL, $entity->getEntityTypeId(), [$entity->id()], [$entity->getRevisionId()]);

    $this->workspaceManager->getActiveWorkspace()?->getProvider()->entityRevisionDelete($entity);
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
    $tracked_entities = $this->workspaceTracker->getTrackedEntities($active_workspace->id());

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

}
