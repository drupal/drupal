<?php

/**
 * @file
 * Post update functions for Taxonomy.
 */

/**
 * Implements hook_removed_post_updates().
 */
function taxonomy_removed_post_updates() {
  return [
    'taxonomy_post_update_clear_views_data_cache' => '9.0.0',
    'taxonomy_post_update_clear_entity_bundle_field_definitions_cache' => '9.0.0',
    'taxonomy_post_update_handle_publishing_status_addition_in_views' => '9.0.0',
    'taxonomy_post_update_remove_hierarchy_from_vocabularies' => '9.0.0',
    'taxonomy_post_update_make_taxonomy_term_revisionable' => '9.0.0',
    'taxonomy_post_update_configure_status_field_widget' => '9.0.0',
    'taxonomy_post_update_clear_views_argument_validator_plugins_cache' => '10.0.0',
  ];
}
