<?php

/**
 * @file
 * Contains \Drupal\picture\PictureMappingAccessController.
 */

namespace Drupal\picture;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the picture mapping entity type.
 */
class PictureMappingAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    elseif (in_array($operation, array('create', 'update', 'delete'))) {
      return user_access('administer pictures', $account);
    }
  }

}
