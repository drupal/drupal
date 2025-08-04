<?php

/**
 * @file
 * Post update functions for the Content Moderation module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function content_moderation_removed_post_updates(): array {
  return [
    'content_moderation_post_update_update_cms_default_revisions' => '9.0.0',
    'content_moderation_post_update_set_default_moderation_state' => '9.0.0',
    'content_moderation_post_update_set_views_filter_latest_translation_affected_revision' => '9.0.0',
    'content_moderation_post_update_entity_display_dependencies' => '9.0.0',
    'content_moderation_post_update_views_field_plugin_id' => '9.0.0',
  ];
}

/**
 * Add moderation_state index to content_moderation_state tables.
 */
function content_moderation_post_update_add_index_content_moderation_state_field_revision_moderation_state(): void {
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::service('entity.definition_update_manager');
  $entity_type = $update_manager->getEntityType('content_moderation_state');
  $update_manager->updateEntityType($entity_type);
}
