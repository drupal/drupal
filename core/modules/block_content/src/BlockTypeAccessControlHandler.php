<?php

namespace Drupal\block_content;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the "Block Type" entity type.
 *
 * @see \Drupal\block_content\Entity\BlockContentType
 */
class BlockTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      return AccessResult::allowedIfHasPermission($account, 'access block library')
        ->orIf(parent::checkAccess($entity, $operation, $account));
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
