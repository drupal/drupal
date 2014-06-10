<?php

/**
 * @file
 * Contains \Drupal\views\ViewAccessController.
 */

namespace Drupal\views;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the view entity type.
 */
class ViewAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return $operation == 'view' || parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
