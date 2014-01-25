<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatAccessController.
 */

namespace Drupal\filter;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the filter format entity type.
 */
class FilterFormatAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    // Handle special cases up front. All users have access to the fallback
    // format.
    if ($operation == 'view' && $entity->isFallbackFormat()) {
      return TRUE;
    }
    // We do not allow filter formats to be deleted through the UI, because that
    // would render any content that uses them unusable.
    if ($operation == 'delete') {
      return FALSE;
    }

    if ($operation != 'view' && parent::checkAccess($entity, $operation, $langcode, $account)) {
      return TRUE;
    }

    // Check the permission if one exists; otherwise, we have a non-existent
    // format so we return FALSE.
    $permission = $entity->getPermissionName();
    return !empty($permission) && $account->hasPermission($permission);
  }

}
