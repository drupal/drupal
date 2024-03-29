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
    if ($operation === 'publish' && $entity->hasParent()) {
      $message = $this->t('Only top-level workspaces can be published.');
      return AccessResult::forbidden((string) $message)->addCacheableDependency($entity);
    }

    if ($account->hasPermission('administer workspaces')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // @todo Consider adding explicit "publish any|own workspace" permissions in
    //   https://www.drupal.org/project/drupal/issues/3084260.
    switch ($operation) {
      case 'update':
      case 'publish':
        $permission_operation = 'edit';
        break;

      case 'view all revisions':
        $permission_operation = 'view';
        break;

      default:
        $permission_operation = $operation;
        break;
    }

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
    return AccessResult::allowedIfHasPermissions($account, ['administer workspaces', 'create workspace'], 'OR');
  }

}
