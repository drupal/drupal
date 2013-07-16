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
    if ($entity->isFallbackFormat()) {
      return TRUE;
    }

    if (user_access('administer filters', $account)) {
      return TRUE;
    }

    // Check the permission if one exists; otherwise, we have a non-existent
    // format so we return FALSE.
    $permission = filter_permission_name($entity);
    return !empty($permission) && user_access($permission, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer filters', $account);
  }

}
