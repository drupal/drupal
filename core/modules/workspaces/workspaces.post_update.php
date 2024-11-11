<?php

/**
 * @file
 * Post update functions for the Workspaces module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function workspaces_removed_post_updates(): array {
  return [
    'workspaces_post_update_access_clear_caches' => '9.0.0',
    'workspaces_post_update_remove_default_workspace' => '9.0.0',
    'workspaces_post_update_move_association_data' => '9.0.0',
    'workspaces_post_update_update_deploy_form_display' => '9.0.0',
    'workspaces_post_update_remove_association_schema_data' => '10.0.0',
    'workspaces_post_update_modify_base_field_author_override' => '10.0.0',
  ];
}
