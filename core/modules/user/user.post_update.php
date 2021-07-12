<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\user\Entity\Role;

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates() {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
  ];
}

/**
 * Calculate role dependencies and remove non-existent permissions.
 */
function user_post_update_update_roles(&$sandbox = NULL) {
  $cleaned_roles = [];
  $existing_permissions = array_keys(\Drupal::service('user.permissions')->getPermissions());
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (Role $role) use ($existing_permissions, &$cleaned_roles) {
    $removed_permissions = array_diff($role->getPermissions(), $existing_permissions);
    if (!empty($removed_permissions)) {
      $cleaned_roles[] = $role->label();
      \Drupal::logger('update')->notice(
        'The role %role has had the following non-existent permission(s) removed: %permissions.',
        ['%role' => $role->label(), '%permissions' => implode(', ', $removed_permissions)]
      );
    }
    $permissions = array_intersect($role->getPermissions(), $existing_permissions);
    $role->set('permissions', $permissions);
    return TRUE;
  });

  if (!empty($cleaned_roles)) {
    return new PluralTranslatableMarkup(
      count($cleaned_roles),
      'The role %role_list has had non-existent permissions removed. Check the logs for details.',
      'The roles %role_list have had non-existent permissions removed. Check the logs for details.',
      ['%role_list' => implode(', ', $cleaned_roles)]
    );
  }
}
