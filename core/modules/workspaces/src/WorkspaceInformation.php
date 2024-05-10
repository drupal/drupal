<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspaces\Entity\Handler\IgnoredWorkspaceHandler;

/**
 * General service for workspace support information.
 */
class WorkspaceInformation implements WorkspaceInformationInterface {

  /**
   * An array of workspace-support statuses, keyed by entity type ID.
   *
   * @var bool[]
   */
  protected array $supported = [];

  /**
   * An array of workspace-ignored statuses, keyed by entity type ID.
   *
   * @var bool[]
   */
  protected array $ignored = [];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly WorkspaceAssociationInterface $workspaceAssociation,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isEntitySupported(EntityInterface $entity): bool {
    $entity_type = $entity->getEntityType();

    if (!$this->isEntityTypeSupported($entity_type)) {
      return FALSE;
    }

    $handler = $this->entityTypeManager->getHandler($entity_type->id(), 'workspace');
    return $handler->isEntitySupported($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type): bool {
    if (!isset($this->supported[$entity_type->id()])) {
      if ($entity_type->hasHandlerClass('workspace')) {
        $supported = !is_a($entity_type->getHandlerClass('workspace'), IgnoredWorkspaceHandler::class, TRUE);
      }
      else {
        // Fallback for cases when entity type info hasn't been altered yet, for
        // example when the Workspaces module is being installed.
        $supported = $entity_type->entityClassImplements(EntityPublishedInterface::class) && $entity_type->isRevisionable();
      }

      $this->supported[$entity_type->id()] = $supported;
    }

    return $this->supported[$entity_type->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes(): array {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->isEntityTypeSupported($entity_type)) {
        $entity_types[$entity_type_id] = $entity_type;
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityIgnored(EntityInterface $entity): bool {
    $entity_type = $entity->getEntityType();

    if ($this->isEntityTypeIgnored($entity_type)) {
      return TRUE;
    }

    if ($entity_type->hasHandlerClass('workspace')) {
      $handler = $this->entityTypeManager->getHandler($entity_type->id(), 'workspace');
      return !$handler->isEntitySupported($entity);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeIgnored(EntityTypeInterface $entity_type): bool {
    if (!isset($this->ignored[$entity_type->id()])) {
      $this->ignored[$entity_type->id()] = $entity_type->hasHandlerClass('workspace')
        && is_a($entity_type->getHandlerClass('workspace'), IgnoredWorkspaceHandler::class, TRUE);
    }

    return $this->ignored[$entity_type->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityDeletable(EntityInterface $entity, WorkspaceInterface $workspace): bool {
    $initial_revisions = $this->workspaceAssociation->getAssociatedInitialRevisions($workspace->id(), $entity->getEntityTypeId());

    return in_array($entity->id(), $initial_revisions, TRUE);
  }

}
