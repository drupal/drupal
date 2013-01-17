<?php

/**
 * @file
 * Contains \Drupal\block\BlockAccessController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Provides a Block access controller.
 */
class BlockAccessController extends EntityAccessController {

  /**
   * Overrides \Drupal\Core\Entity\EntityAccessController::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return $entity->getPlugin()->access();
  }

}
