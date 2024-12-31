<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Defines a class for reacting to entity access control hooks.
 */
class EntityAccess {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceInformationInterface $workspaceInfo,
  ) {}

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Workspaces themselves are handled by their own access handler and we
    // should not try to do any access checks for entity types that can not
    // belong to a workspace.
    if (!$this->workspaceInfo->isEntitySupported($entity) || !$this->workspaceManager->hasActiveWorkspace()) {
      return AccessResult::neutral();
    }

    // Prevent the deletion of entities with a published default revision.
    if ($operation === 'delete') {
      $active_workspace = $this->workspaceManager->getActiveWorkspace();
      $is_deletable = $this->workspaceInfo->isEntityDeletable($entity, $active_workspace);

      return AccessResult::forbiddenIf(!$is_deletable)
        ->addCacheableDependency($entity)
        ->addCacheableDependency($active_workspace);
    }

    return $this->bypassAccessResult($account);
  }

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, $entity_bundle): AccessResultInterface {
    // Workspaces themselves are handled by their own access handler and we
    // should not try to do any access checks for entity types that can not
    // belong to a workspace.
    $entity_type = $this->entityTypeManager->getDefinition($context['entity_type_id']);
    if (!$this->workspaceInfo->isEntityTypeSupported($entity_type) || !$this->workspaceManager->hasActiveWorkspace()) {
      return AccessResult::neutral();
    }

    return $this->bypassAccessResult($account);
  }

  /**
   * Checks the 'bypass' permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account making the to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  protected function bypassAccessResult(AccountInterface $account): AccessResultInterface {
    // This approach assumes that the current "global" active workspace is
    // correct, i.e. if you're "in" a given workspace then you get ALL THE PERMS
    // to ALL THE THINGS! That's why this is a dangerous permission.
    $active_workspace = $this->workspaceManager->getActiveWorkspace();

    return AccessResult::allowedIf($active_workspace->getOwnerId() == $account->id())->cachePerUser()->addCacheableDependency($active_workspace)
      ->andIf(AccessResult::allowedIfHasPermission($account, 'bypass entity access own workspace'));
  }

}
