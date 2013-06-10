<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessController
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Plugin\Core\Entity\Comment.
 */
class CommentAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, UserInterface $account) {
    switch ($operation) {
      case 'view':
        return user_access('access comments', $account);
        break;

      case 'create':
        return user_access('post comments', $account);
        break;

      case 'update':
        return ($account->uid && $account->uid == $entity->uid->value && $entity->status->value == COMMENT_PUBLISHED && user_access('edit own comments', $account)) || user_access('administer comments', $account);
        break;

      case 'delete':
        return user_access('administer comments', $account);
        break;

      case 'approve':
        return user_access('administer comments', $account);
        break;
    }
  }

}
