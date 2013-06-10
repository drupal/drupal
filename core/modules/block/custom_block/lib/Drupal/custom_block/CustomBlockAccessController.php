<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockAccessController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityAccessController;

/**
 * Defines the access controller for the custom block entity type.
 */
class CustomBlockAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, UserInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    elseif (in_array($operation, array('create', 'update', 'delete'))) {
      return user_access('administer blocks', $account);
    }
  }

}
