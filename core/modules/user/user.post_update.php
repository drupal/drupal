<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\user\Entity\Role;

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates() {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
    'user_post_update_update_roles' => '10.0.0',
  ];
}

/**
 * No-op update.
 */
function user_post_update_sort_permissions(&$sandbox = NULL) {
}

/**
 * Ensure permissions stored in role configuration are sorted using the schema.
 */
function user_post_update_sort_permissions_again(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (Role $role) {
    $permissions = $role->getPermissions();
    sort($permissions);
    return $permissions !== $role->getPermissions();
  });
}
