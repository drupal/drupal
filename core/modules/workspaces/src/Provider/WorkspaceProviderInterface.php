<?php

declare(strict_types=1);

namespace Drupal\workspaces\Provider;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workspaces\WorkspaceInterface;

/**
 * Provides the interface for workspace providers.
 */
interface WorkspaceProviderInterface {

  /**
   * Gets the ID of the workspace provider.
   *
   * @return string
   *   The workspace provider ID.
   */
  public static function getId(): string;

  /**
   * Checks access for a given workspace.
   *
   * It is strongly recommended to inherit this method from the base provider
   * class, and call the parent method before or after any custom logic.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(WorkspaceInterface $workspace, string $operation, AccountInterface $account): AccessResultInterface;

  /**
   * Acts before an entity is created.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being created.
   */
  public function entityCreate(EntityInterface $entity): void;

  /**
   * Acts before an entity is loaded.
   *
   * @param array $ids
   *   An array of entity IDs.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   The modified array of entity IDs.
   */
  public function entityPreload(array $ids, string $entity_type_id): array;

  /**
   * Acts before an entity is saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @throws \RuntimeException
   *   Thrown when trying to save an unsupported entity type in a workspace.
   */
  public function entityPresave(EntityInterface $entity): void;

  /**
   * Acts after an entity has been added.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was inserted.
   */
  public function entityInsert(EntityInterface $entity): void;

  /**
   * Acts after an entity has been updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was updated.
   */
  public function entityUpdate(EntityInterface $entity): void;

  /**
   * Acts after an entity translation has been added.
   *
   * @param \Drupal\Core\Entity\EntityInterface $translation
   *   The entity translation that was inserted.
   */
  public function entityTranslationInsert(EntityInterface $translation): void;

  /**
   * Acts before an entity is deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   *
   * @throws \RuntimeException
   *   Thrown when trying to delete an entity that can only be deleted in Live.
   */
  public function entityPredelete(EntityInterface $entity): void;

  /**
   * Acts after an entity has been deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was deleted.
   */
  public function entityDelete(EntityInterface $entity): void;

  /**
   * Acts after an entity revision has been deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity revision that was deleted.
   */
  public function entityRevisionDelete(EntityInterface $entity): void;

}
