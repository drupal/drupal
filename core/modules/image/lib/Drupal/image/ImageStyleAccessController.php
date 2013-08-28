<?php

/**
 * @file
 * Contains \Drupal\image\ImageStyleAccessController.
 */

namespace Drupal\image;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the image style entity.
 *
 * @see \Drupal\image\Entity\ImageStyle
 */
class ImageStyleAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'create':
      case 'update':
      case 'delete':
        return $account->hasPermission('administer image styles');
        break;
    }
  }

}
