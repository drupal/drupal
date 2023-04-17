<?php

/**
 * @file
 * Post update functions for Content Block.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

/**
 * Implements hook_removed_post_updates().
 */
function block_content_removed_post_updates() {
  return [
    'block_content_post_update_add_views_reusable_filter' => '9.0.0',
  ];
}

/**
 * Clear the entity type cache.
 */
function block_content_post_update_entity_changed_constraint() {
  // Empty post_update hook.
}

/**
 * Moves the custom block library to Content.
 */
function block_content_post_update_move_custom_block_library() {

  if (!\Drupal::service('module_handler')->moduleExists('views')) {
    return;
  }
  if (!$view = View::load('block_content')) {
    return;
  }

  $display =& $view->getDisplay('page_1');
  if (empty($display) || $display['display_options']['path'] !== 'admin/structure/block/block-content') {
    return;
  }

  $display['display_options']['path'] = 'admin/content/block';
  $menu =& $display['display_options']['menu'];
  $menu['title'] = 'Blocks';
  $menu['description'] = 'Create and edit block content.';
  $menu['expanded'] = FALSE;
  $menu['parent'] = 'system.admin_content';
  $view->set('label', 'Content blocks');

  $view->save();
}

/**
 * Update block_content 'block library' view permission.
 */
function block_content_post_update_block_library_view_permission() {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('views.view.block_content');
  $current_perm = $config->get('display.default.display_options.access.options.perm');
  if ($current_perm === 'administer blocks') {
    $config->set('display.default.display_options.access.options.perm', 'access block library')
      ->save(TRUE);
  }
}

/**
 * Update permissions for users with "administer blocks" permission.
 */
function block_content_post_update_sort_permissions(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (Role $role) {
    if ($role->hasPermission('administer blocks')) {
      $role->grantPermission('administer block content');
      $role->grantPermission('access block library');
      $role->grantPermission('administer block types');
      return TRUE;
    }
    return FALSE;
  });
}
