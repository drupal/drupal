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
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    if ($operation === 'view') {
      return $entity->getPlugin()->access();
    }
  }

}
