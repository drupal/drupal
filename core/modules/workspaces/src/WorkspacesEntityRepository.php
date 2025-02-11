<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Provides workspace-specific mechanisms for retrieving entities.
 */
class WorkspacesEntityRepository implements EntityRepositoryInterface {

  public function __construct(
    protected EntityRepositoryInterface $inner,
    protected WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    return $this->inner->loadEntityByUuid($entity_type_id, $uuid);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByConfigTarget($entity_type_id, $target) {
    return $this->inner->loadEntityByConfigTarget($entity_type_id, $target);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = []) {
    return $this->inner->getTranslationFromContext($entity, $langcode, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getActive($entity_type_id, $entity_id, ?array $contexts = NULL) {
    // When there's no active workspace, the active entity variant is the
    // canonical one.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return $this->inner->getCanonical($entity_type_id, $entity_id, $contexts);
    }
    return $this->inner->getActive($entity_type_id, $entity_id, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveMultiple($entity_type_id, array $entity_ids, ?array $contexts = NULL) {
    // When there's no active workspace, the active entity variant is the
    // canonical one.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return $this->inner->getCanonicalMultiple($entity_type_id, $entity_ids, $contexts);
    }
    return $this->inner->getActiveMultiple($entity_type_id, $entity_ids, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonical($entity_type_id, $entity_id, ?array $contexts = NULL) {
    return $this->inner->getCanonical($entity_type_id, $entity_id, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonicalMultiple($entity_type_id, array $entity_ids, ?array $contexts = NULL) {
    return $this->inner->getCanonicalMultiple($entity_type_id, $entity_ids, $contexts);
  }

}
