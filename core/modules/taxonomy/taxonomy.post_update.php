<?php

/**
 * @file
 * Post update functions for Taxonomy.
 */

/**
 * Clear caches due to updated taxonomy entity views data.
 */
function taxonomy_post_update_clear_views_data_cache() {
  // An empty update will flush caches.
}

/**
 * Clear entity_bundle_field_definitions cache for new parent field settings.
 */
function taxonomy_post_update_clear_entity_bundle_field_definitions_cache() {
  // An empty update will flush caches.
}
