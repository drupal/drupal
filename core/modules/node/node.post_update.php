<?php

/**
 * @file
 * Post update functions for Node.
 */

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates(): array {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
    'node_post_update_glossary_view_published' => '10.0.0',
    'node_post_update_rebuild_node_revision_routes' => '10.0.0',
    'node_post_update_modify_base_field_author_override' => '10.0.0',
    'node_post_update_set_node_type_description_and_help_to_null' => '11.0.0',
  ];
}
