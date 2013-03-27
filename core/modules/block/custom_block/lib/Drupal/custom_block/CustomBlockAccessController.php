<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockAccessController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;
use Drupal\Core\Entity\EntityAccessController;

/**
 * Defines the access controller for the custom block entity type.
 */
class CustomBlockAccessController extends EntityAccessController {

  /**
   * Implements EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return TRUE;
  }

  /**
   * Implements EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer blocks', $account);
  }

  /**
   * Implements EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer blocks', $account);
  }

  /**
   * Implements EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer blocks', $account);
  }

}
