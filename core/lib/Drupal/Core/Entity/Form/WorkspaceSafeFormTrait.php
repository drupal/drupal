<?php

namespace Drupal\Core\Entity\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspaces\WorkspaceInformationInterface;

/**
 * Provides helpers for checking whether objects in forms are workspace-safe.
 */
trait WorkspaceSafeFormTrait {

  /**
   * The workspace information service.
   */
  protected ?WorkspaceInformationInterface $workspaceInfo = NULL;

  /**
   * Determines whether an entity used in a form is workspace-safe.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return bool
   *   TRUE if the entity is workspace-safe, FALSE otherwise.
   */
  protected function isWorkspaceSafeEntity(EntityInterface $entity): bool {
    if (!\Drupal::hasService('workspaces.information')) {
      return FALSE;
    }

    $is_supported = $this->getWorkspaceInfo()->isEntitySupported($entity);
    $is_ignored = $this->getWorkspaceInfo()->isEntityIgnored($entity);

    return $is_supported || $is_ignored;
  }

  /**
   * Determines whether an entity type used in a form is workspace-safe.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type object.
   *
   * @return bool
   *   TRUE if the entity type is workspace-safe, FALSE otherwise.
   */
  protected function isWorkspaceSafeEntityType(EntityTypeInterface $entity_type): bool {
    if (!\Drupal::hasService('workspaces.information')) {
      return FALSE;
    }

    $is_supported = $this->getWorkspaceInfo()->isEntityTypeSupported($entity_type);
    $is_ignored = $this->getWorkspaceInfo()->isEntityTypeIgnored($entity_type);

    return $is_supported || $is_ignored;
  }

  /**
   * Retrieves the workspace information service.
   *
   * @return \Drupal\workspaces\WorkspaceInformationInterface
   *   The workspace information service.
   */
  protected function getWorkspaceInfo(): WorkspaceInformationInterface {
    if (!$this->workspaceInfo) {
      $this->workspaceInfo = \Drupal::service('workspaces.information');
    }

    return $this->workspaceInfo;
  }

}
