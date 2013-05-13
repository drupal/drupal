<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines an access controller for the taxonomy term entity.
 *
 * @see \Drupal\taxonomy\Plugin\Core\Entity\Term
 */
class TermAccessController extends EntityAccessController {

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('access content', $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer taxonomy', $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access("edit terms in {$entity->bundle()}", $account) || user_access('administer taxonomy', $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access("delete terms in {$entity->bundle()}", $account) || user_access('administer taxonomy', $account);
  }

}
