<?php

/**
 * @file
 * Contains \Drupal\block\BlockAccessControlHandler.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the block entity type.
 *
 * @see \Drupal\block\Entity\Block
 */
class BlockAccessControlHandler extends EntityAccessControlHandler {

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
