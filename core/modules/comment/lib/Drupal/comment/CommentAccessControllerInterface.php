<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessControllerInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityAccessControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines an interface for comment access controller.
 *
 * Additional to EntityAccessControllerInterface this adds an access method for
 * approving a comment.
 */
interface CommentAccessControllerInterface extends EntityAccessControllerInterface {

  /**
   * Checks 'approve' access for a given entity or entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check 'delete' access.
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LANGUAGE_DEFAULT.
   * @param \Drupal\user\Plugin\Core\Entity\User $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function approveAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL);

}
