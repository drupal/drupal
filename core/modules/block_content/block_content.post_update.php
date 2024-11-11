<?php

/**
 * @file
 * Post update functions for Content Block.
 */

/**
 * Implements hook_removed_post_updates().
 */
function block_content_removed_post_updates(): array {
  return [
    'block_content_post_update_add_views_reusable_filter' => '9.0.0',
    'block_content_post_update_entity_changed_constraint' => '11.0.0',
    'block_content_post_update_move_custom_block_library' => '11.0.0',
    'block_content_post_update_block_library_view_permission' => '11.0.0',
    'block_content_post_update_sort_permissions' => '11.0.0',
    'block_content_post_update_revision_type' => '11.0.0',
  ];
}
