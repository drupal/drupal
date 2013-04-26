<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutAccessController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines the access controller for the shortcut entity type.
 */
class ShortcutAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    if ($operation == 'delete') {
      if (!user_access('administer shortcuts', $account)) {
        return FALSE;
      }
      return $entity->id() != 'default';
    }
  }

}
