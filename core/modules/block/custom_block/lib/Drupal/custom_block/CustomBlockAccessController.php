<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockAccessController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the custom block entity type.
 */
class CustomBlockAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
