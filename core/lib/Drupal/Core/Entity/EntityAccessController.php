<?php

/**
 * @file
 * Contains Drupal\Core\Entity\EntityAccessController.
 */

namespace Drupal\Core\Entity;

use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines a base class for entity access controllers.
 *
 * Defaults to FALSE (access denied) for 'view', 'create', 'update' and 'delete'
 * access checks.
 */
class EntityAccessController implements EntityAccessControllerInterface {

  /**
   * Implements EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return FALSE;
  }

  /**
   * Implements EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return FALSE;
  }

  /**
   * Implements EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return FALSE;
  }

  /**
   * Implements EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return FALSE;
  }

}
