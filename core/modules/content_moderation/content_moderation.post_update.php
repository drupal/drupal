<?php

/**
 * @file
 * Post update functions for the Content Moderation module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function content_moderation_removed_post_updates() {
  return [
    'content_moderation_post_update_update_cms_default_revisions' => '9.0.0',
    'content_moderation_post_update_set_default_moderation_state' => '9.0.0',
    'content_moderation_post_update_set_views_filter_latest_translation_affected_revision' => '9.0.0',
    'content_moderation_post_update_entity_display_dependencies' => '9.0.0',
    'content_moderation_post_update_views_field_plugin_id' => '9.0.0',
  ];
}
