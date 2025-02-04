<?php

/**
 * @file
 * Post update functions for the Navigation module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\user\RoleInterface;

/**
 * Grants navigation specific permission to roles with access to any layout.
 */
function navigation_post_update_update_permissions(array &$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (RoleInterface $role) {
    $needs_save = FALSE;
    if ($role->hasPermission('configure any layout')) {
      $role->grantPermission('configure navigation layout');
      $needs_save = TRUE;
    }
    if ($role->hasPermission('administer navigation_block')) {
      $role->revokePermission('administer navigation_block');
      $role->grantPermission('configure navigation layout');
      $needs_save = TRUE;
    }
    return $needs_save;
  });
}

/**
 * Defines the values for the default logo dimensions.
 */
function navigation_post_update_set_logo_dimensions_default(array &$sandbox) {
  // Empty post_update hook.
}

/**
 * Creates the Navigation user links menu.
 */
function navigation_post_update_navigation_user_links_menu(array &$sandbox): void {
  $menu_storage = \Drupal::entityTypeManager()->getStorage('menu');

  // Do not create the new menu if already exists.
  if ($menu_storage->load('navigation-user-links')) {
    return;
  }

  $menu_storage
    ->create([
      'id' => 'navigation-user-links',
      'label' => 'Navigation user links',
      'description' => 'User links to be used in Navigation',
      'dependencies' => [
        'enforced' => [
          'module' => [
            'navigation',
          ],
        ],
      ],
      'locked' => TRUE,
    ])->save();
}
