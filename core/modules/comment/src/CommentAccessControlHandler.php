<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessControlHandler.
 */

namespace Drupal\comment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 */
class CommentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\user\EntityOwnerInterface $entity */

    if ($account->hasPermission('administer comments')) {
      $access = AccessResult::allowed()->cachePerRole();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('access comments') && $entity->isPublished())->cachePerRole()->cacheUntilEntityChanges($entity)
          ->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));

      case 'update':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $account->hasPermission('edit own comments'))->cachePerRole()->cachePerUser()->cacheUntilEntityChanges($entity);

      default:
        // No opinion.
        return AccessResult::create()->cachePerRole();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'post comments');
  }

}
