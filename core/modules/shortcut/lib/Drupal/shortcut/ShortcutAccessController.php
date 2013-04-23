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
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (!user_access('administer shortcuts')) {
      return FALSE;
    }

    return $entity->id() != 'default';
  }

}
