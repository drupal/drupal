<?php

/**
 * @file
 * Contains \Drupal\user\RoleAccessController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the user_role entity type.
 */
class RoleAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        if ($entity->id() == DRUPAL_ANONYMOUS_RID || $entity->id() == DRUPAL_AUTHENTICATED_RID) {
          return FALSE;
        }

      default:
        return parent::checkAccess($entity, $operation, $langcode, $account);
    }
  }

}
