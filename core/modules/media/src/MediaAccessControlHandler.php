<?php

namespace Drupal\media;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the media entity.
 */
class MediaAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer media')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
    switch ($operation) {
      case 'view':
        $access_result = AccessResult::allowedIf($account->hasPermission('view media') && $entity->isPublished())
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
        if (!$access_result->isAllowed()) {
          $access_result->setReason("The 'view media' permission is required and the media item must be published.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission('update any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIf($account->hasPermission('update media') && $is_owner)
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      case 'delete':
        if ($account->hasPermission('delete any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIf($account->hasPermission('delete media') && $is_owner)
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['administer media', 'create media'], 'OR');
  }

}
