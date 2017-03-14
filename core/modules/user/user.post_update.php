<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\user\Entity\Role;

/**
 * Enforce order of role permissions.
 */
function user_post_update_enforce_order_of_permissions() {
  $entity_save = function (Role $role) {
    $permissions = $role->getPermissions();
    sort($permissions);
    if ($permissions !== $role->getPermissions()) {
      $role->save();
    }
  };
  array_map($entity_save, Role::loadMultiple());
}
