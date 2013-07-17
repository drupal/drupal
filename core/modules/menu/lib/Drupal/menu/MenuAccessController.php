<?php

/**
 * @file
 * Contains \Drupal\menu\MenuAccessController.
 */

namespace Drupal\menu;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the menu entity type.
 */
class MenuAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    elseif ($operation == 'delete') {
      // System menus could not be deleted.
      $system_menus = menu_list_system_menus();
      if (isset($system_menus[$entity->id()])) {
        return FALSE;
      }
    }

    if (in_array($operation, array('update', 'delete'))) {
      return user_access('administer menu', $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer menu', $account);
  }

}
