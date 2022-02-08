<?php

/**
 * @file
 * Post update functions for the Workspaces module.
 */

use Drupal\Core\Field\Entity\BaseFieldOverride;

/**
 * Implements hook_removed_post_updates().
 */
function workspaces_removed_post_updates() {
  return [
    'workspaces_post_update_access_clear_caches' => '9.0.0',
    'workspaces_post_update_remove_default_workspace' => '9.0.0',
    'workspaces_post_update_move_association_data' => '9.0.0',
    'workspaces_post_update_update_deploy_form_display' => '9.0.0',
    'workspaces_post_update_remove_association_schema_data' => '10.0.0',
  ];
}

/**
 * Updates stale references to Drupal\workspaces\Entity\Workspace::getCurrentUserId.
 */
function workspaces_post_update_modify_base_field_author_override() {
  $uid_fields = \Drupal::entityTypeManager()
    ->getStorage('base_field_override')
    ->getQuery()
    ->condition('entity_type', 'workspace')
    ->condition('field_name', 'uid')
    ->condition('default_value_callback', 'Drupal\workspaces\Entity\Workspace::getCurrentUserId')
    ->execute();
  foreach (BaseFieldOverride::loadMultiple($uid_fields) as $base_field_override) {
    $base_field_override->setDefaultValueCallback('Drupal\workspaces\Entity\Workspace::getDefaultEntityOwner')->save();
  }
}
