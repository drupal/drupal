<?php

/**
 * @file
 * Post update functions for the Workspaces module.
 */

/**
 * Removes the workspace association entity and field schema data.
 */
function workspaces_post_update_remove_association_schema_data() {
  // Delete the entity and field schema data.
  $keys = [
    'workspace_association.entity_schema_data',
    'workspace_association.field_schema_data.id',
    'workspace_association.field_schema_data.revision_id',
    'workspace_association.field_schema_data.uuid',
    'workspace_association.field_schema_data.revision_default',
    'workspace_association.field_schema_data.target_entity_id',
    'workspace_association.field_schema_data.target_entity_revision_id',
    'workspace_association.field_schema_data.target_entity_type_id',
    'workspace_association.field_schema_data.workspace',
  ];
  \Drupal::keyValue('entity.storage_schema.sql')->deleteMultiple($keys);
}

/**
 * Implements hook_removed_post_updates().
 */
function workspaces_removed_post_updates() {
  return [
    'workspaces_post_update_access_clear_caches' => '9.0.0',
    'workspaces_post_update_remove_default_workspace' => '9.0.0',
    'workspaces_post_update_move_association_data' => '9.0.0',
    'workspaces_post_update_update_deploy_form_display' => '9.0.0',
  ];
}
