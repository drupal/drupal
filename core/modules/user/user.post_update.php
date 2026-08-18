<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\user\RoleInterface;

/**
 * Add an empty description to existing user roles.
 */
function user_post_update_add_role_description(array &$sandbox): void {
  $config_factory = \Drupal::configFactory();
  /** @var \Drupal\Core\Config\Entity\ConfigEntityUpdater $config_updater */
  $config_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_updater->update($sandbox, 'user_role', function (RoleInterface $role) use ($config_factory): bool {
    if ($config_factory->get('user.role.' . $role->id())->get('description') === NULL) {
      $role->setDescription('');
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates(): array {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
    'user_post_update_update_roles' => '10.0.0',
    'user_post_update_sort_permissions' => '11.0.0',
    'user_post_update_sort_permissions_again' => '11.0.0',
  ];
}
