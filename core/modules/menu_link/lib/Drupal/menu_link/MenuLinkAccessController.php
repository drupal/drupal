<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkAccessController.
 */

namespace Drupal\menu_link;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines an access controller for the menu link entity.
 *
 * @see \Drupal\menu_link\Plugin\Core\Entity\MenuLink
 */
class MenuLinkAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    $access = user_access('administer menu', $account);
    if ($access && $operation == 'delete') {
      // Only items created by the menu module can be deleted.
      return $entity->module == 'menu';
    }
    return $access;
  }

}
