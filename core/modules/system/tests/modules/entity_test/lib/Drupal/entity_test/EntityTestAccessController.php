<?php

/**
 * @file
 * Contains Drupal\entity_test\EntityTestAccessController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControllerInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines the access controller for the test entity type.
 */
class EntityTestAccessController implements EntityAccessControllerInterface {

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if ($langcode != LANGUAGE_DEFAULT) {
      return user_access('view test entity translations', $account);
    }
    return user_access('view test entity', $account);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return TRUE;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return TRUE;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return TRUE;
  }

}
