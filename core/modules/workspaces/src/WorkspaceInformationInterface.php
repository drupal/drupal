<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for workspace-support information.
 */
interface WorkspaceInformationInterface {

  /**
   * Determines whether an entity can belong to a workspace.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity can belong to a workspace, FALSE otherwise.
   */
  public function isEntitySupported(EntityInterface $entity): bool;

  /**
   * Determines whether an entity type can belong to a workspace.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   TRUE if the entity type can belong to a workspace, FALSE otherwise.
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type): bool;

  /**
   * Returns an array of entity types that can belong to workspaces.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity type definition objects.
   */
  public function getSupportedEntityTypes(): array;

  /**
   * Determines whether CRUD operations for an entity are allowed.
   *
   * CRUD operations for an ignored entity are allowed in a workspace, but their
   * revisions are not tracked.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if CRUD operations of an entity type can safely be done inside a
   *   workspace, without impacting the Live site, FALSE otherwise.
   */
  public function isEntityIgnored(EntityInterface $entity): bool;

  /**
   * Determines whether CRUD operations for an entity type are allowed.
   *
   * CRUD operations for an ignored entity type are allowed in a workspace, but
   * their revisions are not tracked.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   TRUE if CRUD operations of an entity type can safely be done inside a
   *   workspace, without impacting the Live site, FALSE otherwise.
   */
  public function isEntityTypeIgnored(EntityTypeInterface $entity_type): bool;

  /**
   * Determines whether an entity can be deleted in the given workspace.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object which needs to be checked.
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace in which the entity needs to be checked.
   *
   * @return bool
   *   TRUE if the entity can be deleted, FALSE otherwise.
   */
  public function isEntityDeletable(EntityInterface $entity, WorkspaceInterface $workspace): bool;

}
