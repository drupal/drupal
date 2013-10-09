<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkAccessController.
 */

namespace Drupal\menu_link;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the menu link entity.
 *
 * @see \Drupal\menu_link\Entity\MenuLink
 */
class MenuLinkAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    $access = $account->hasPermission('administer menu');
    if ($access) {
      switch ($operation) {
        case 'reset':
          // Reset allowed for items defined via hook_menu() and customized.
          return $entity->module == 'system' && $entity->customized;

        case 'delete':
          // Only items created by the menu module can be deleted.
          return $entity->module == 'menu' || $entity->updated == 1;

      }
    }
    return $access;
  }

}
