<?php

namespace Drupal\media;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for media items.
 */
class MediaAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer media')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $type = $entity->bundle();
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
        if ($account->hasPermission('edit any ' . $type . ' media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('edit own ' . $type . ' media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        // @todo Deprecate this permission in
        // https://www.drupal.org/project/drupal/issues/2925459.
        if ($account->hasPermission('update any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('update media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'update any media' OR 'update own media' OR '$type: edit any media' OR '$type: edit own media'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission('delete any ' . $type . ' media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete own ' . $type . ' media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        // @todo Deprecate this permission in
        // https://www.drupal.org/project/drupal/issues/2925459.
        if ($account->hasPermission('delete any media')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete media') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'delete any media' OR 'delete own media' OR '$type: delete any media' OR '$type: delete own media'.")->cachePerPermissions();

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      'administer media',
      'create media',
      'create ' . $entity_bundle . ' media',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
