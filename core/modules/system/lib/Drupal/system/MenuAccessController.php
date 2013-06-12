<?php

/**
 * @file
 * Contains \Drupal\system\MenuAccessController.
 */

namespace Drupal\system;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the menu entity.
 *
 * @see \Drupal\system\Plugin\Core\Entity\Menu
 */
class MenuAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'delete') {
      // System-defined menus may not be deleted.
      // @todo Refactor https://drupal.org/node/1882552
      $system_menus = menu_list_system_menus();
      return !isset($system_menus[$entity->id()]);
    }
    return user_access('administer menu', $account);
  }

}
