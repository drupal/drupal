<?php

/**
 * @file
 * Contains \Drupal\block\BlockAccessController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Block access controller.
 */
class BlockAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var $entity \Drupal\block\BlockInterface */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $langcode, $account);
    }

    // Deny access to disabled blocks.
    if (!$entity->status()) {
      return FALSE;
    }

    // Delegate to the plugin.
    return $entity->getPlugin()->access($account);
  }

}
