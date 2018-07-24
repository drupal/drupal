<?php

namespace Drupal\workspaces;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the workspace entity type.
 *
 * @see \Drupal\workspaces\Entity\Workspace
 */
class WorkspaceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\workspaces\WorkspaceInterface $entity */
    if ($operation === 'delete' && $entity->isDefaultWorkspace()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($account->hasPermission('administer workspaces')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // The default workspace is always viewable, no matter what.
    if ($operation == 'view' && $entity->isDefaultWorkspace()) {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }

    $permission_operation = $operation === 'update' ? 'edit' : $operation;

    // Check if the user has permission to access all workspaces.
    $access_result = AccessResult::allowedIfHasPermission($account, $permission_operation . ' any workspace');

    // Check if it's their own workspace, and they have permission to access
    // their own workspace.
    if ($access_result->isNeutral() && $account->isAuthenticated() && $account->id() === $entity->getOwnerId()) {
      $access_result = AccessResult::allowedIfHasPermission($account, $permission_operation . ' own workspace')
        ->cachePerUser()
        ->addCacheableDependency($entity);
    }

    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create workspace');
  }

}
