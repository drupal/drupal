<?php

/**
 * @file
 * Contains \Drupal\views\ViewAccessControlHandler.
 */

namespace Drupal\views;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the view entity type.
 *
 * @see \Drupal\views\Entity\View
 */
class ViewAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    else {
      return parent::checkAccess($entity, $operation, $langcode, $account);
    }
  }

}
