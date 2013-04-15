<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessController
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Plugin\Core\Entity\Comment.
 */
class CommentAccessController extends EntityAccessController implements CommentAccessControllerInterface {

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('access comments', $account);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('post comments', $account);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    // If no user is specified fill in the current one.
    if (!isset($account)) {
      $account = $GLOBALS['user'];
    }
    return ($account->uid && $account->uid == $entity->uid->value && $entity->status->value == COMMENT_PUBLISHED && user_access('edit own comments', $account)) || user_access('administer comments', $account);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer comments', $account);
  }

  /**
   * Implements \Drupal\comment\CommentAccessControllerInterface::approveAccess().
   */
  public function approveAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer comments', $account);
  }

}
